import TelegramBot from 'node-telegram-bot-api';
import { config } from './config.mjs';
import { queries } from './db.mjs';
import { cancelCurrentTask, getRunnerStatus, approvePlan, rejectPlan, reloadContext, resetSession, getSessionId } from './task-runner.mjs';
import { v4 as uuid } from 'uuid';
import { spawn } from 'child_process';
import { writeFileSync, readFileSync, existsSync, readdirSync } from 'fs';
import { resolve } from 'path';

let bot = null;

export function getBot() {
    return bot;
}

export function initBot() {
    if (!config.telegram.token || config.telegram.token === 'your_bot_token_here') {
        console.warn('[Telegram] No bot token configured — bot disabled. Set TELEGRAM_BOT_TOKEN in .env');
        return null;
    }

    bot = new TelegramBot(config.telegram.token, { polling: true });

    const chatId = config.telegram.chatId;

    // Middleware: only respond to authorized chat
    function isAuthorized(msg) {
        return String(msg.chat.id) === String(chatId);
    }

    // /start
    bot.onText(/\/start/, (msg) => {
        if (!isAuthorized(msg)) return;
        bot.sendMessage(chatId,
            `*Dev Bridge*\n\n` +
            `*Tasks (plan → approve → execute):*\n` +
            `/instruct <text> — Plan first, then approve\n` +
            `/run <text> — Execute immediately (no plan)\n` +
            `/approve — Approve pending plan\n` +
            `/reject — Reject pending plan\n` +
            `/cancel — Cancel running task\n` +
            `/queue — Show queued tasks\n\n` +
            `*Testing:*\n` +
            `/test [suite] — Run Playwright tests\n` +
            `/screenshot <page> — Capture screenshot\n` +
            `/pages — Available pages\n\n` +
            `*Info:*\n` +
            `/status — Bridge health\n` +
            `/history — Recent events\n` +
            `/context — Reload project context\n` +
            `/newsession — Reset conversation (fresh start)\n` +
            `/mode — Toggle hook approve/notify mode\n` +
            `/pending — Pending hook approvals`,
            { parse_mode: 'Markdown' }
        );
    });

    // /status
    bot.onText(/\/status/, (msg) => {
        if (!isAuthorized(msg)) return;
        const lastTest = queries.getLastTestRun();
        const runner = getRunnerStatus();

        let text = `*Bridge Status*\n\n`;
        if (runner.busy) {
            const elapsed = Math.round(runner.currentTask.elapsed / 1000);
            const phaseIcon = runner.currentTask.phase === 'planning' ? '📋' : '🔨';
            text += `${phaseIcon} *Claude is ${runner.currentTask.phase}:* ${elapsed}s\n`;
            text += `_${runner.currentTask.instruction.substring(0, 100)}_\n\n`;
        } else if (runner.pendingPlan) {
            text += `⏳ *Plan waiting for approval*\n`;
            text += `_${runner.pendingPlan.instruction.substring(0, 100)}_\n`;
            text += `Use /approve or /reject\n\n`;
        } else {
            text += `Claude: idle\n\n`;
        }
        text += `Queued tasks: ${runner.queuedCount}\n`;
        text += `Session: ${runner.sessionId ? runner.sessionId.substring(0, 12) + '...' : 'none (will start fresh)'}\n`;
        if (lastTest) {
            text += `Last test: ${lastTest.status} (${lastTest.passed}/${lastTest.total})\n`;
        }
        text += `Bridge: running`;
        bot.sendMessage(chatId, text, { parse_mode: 'Markdown' });
    });

    // /pending
    bot.onText(/\/pending/, (msg) => {
        if (!isAuthorized(msg)) return;
        const pending = queries.getPendingEvents();
        if (pending.length === 0) {
            bot.sendMessage(chatId, 'No pending approvals.');
            return;
        }
        for (const evt of pending) {
            sendApprovalMessage(evt.id, evt.summary || 'No summary', evt.type);
        }
    });

    // /mode — toggle between notify and approve
    bot.onText(/\/mode/, (msg) => {
        if (!isAuthorized(msg)) return;
        const current = queries.getApprovalMode();
        const next = current === 'notify' ? 'approve' : 'notify';
        queries.setApprovalMode(next);

        const desc = next === 'notify'
            ? 'You will receive task summaries automatically. No approval needed — Claude proceeds on its own.'
            : 'Claude will wait for your approval after each task. You can Approve, Reject, or send new instructions.';

        bot.sendMessage(chatId,
            `*Mode changed: ${next}*\n\n${desc}`,
            { parse_mode: 'Markdown' }
        );
    });

    // /test [suite]
    bot.onText(/\/test\s*(.*)/, (msg, match) => {
        if (!isAuthorized(msg)) return;
        const suite = match[1]?.trim() || '';
        bot.sendMessage(chatId, `Running Playwright tests${suite ? ` (${suite})` : ''}...`);

        const testId = uuid();
        queries.insertTestRun(testId, suite || 'all');

        const playwrightCli = resolve(config.paths.root, 'node_modules', '@playwright', 'test', 'cli.js');
        const args = [playwrightCli, 'test', '--reporter=json'];
        if (suite) args.push(`--grep=${suite}`);

        const proc = spawn(config.node.path, args, {
            cwd: config.paths.root,
            stdio: ['pipe', 'pipe', 'pipe'],
            env: { ...process.env, PLAYWRIGHT_JSON_OUTPUT_NAME: resolve(config.paths.bridge, 'test-results.json') }
        });

        let output = '';
        proc.stdout.on('data', (d) => output += d.toString());
        proc.stderr.on('data', (d) => output += d.toString());

        proc.on('close', (code) => {
            try {
                // Parse JSON results
                const resultsPath = resolve(config.paths.bridge, 'test-results.json');
                let results = { suites: [] };
                if (existsSync(resultsPath)) {
                    results = JSON.parse(readFileSync(resultsPath, 'utf-8'));
                }

                const stats = parseTestResults(results);
                queries.updateTestRun(testId, code === 0 ? 'passed' : 'failed', stats, stats.total, stats.passed, stats.failed);

                let text = code === 0
                    ? `*Tests PASSED*\n\n`
                    : `*Tests FAILED*\n\n`;
                text += `Total: ${stats.total} | Passed: ${stats.passed} | Failed: ${stats.failed}\n`;

                if (stats.failures.length > 0) {
                    text += `\n*Failures:*\n`;
                    for (const f of stats.failures.slice(0, 5)) {
                        text += `- ${f.name}: ${f.error.substring(0, 100)}\n`;
                    }
                }

                bot.sendMessage(chatId, text, { parse_mode: 'Markdown' });

                // Send failure screenshots if any
                sendFailureScreenshots(stats.failures);
            } catch (err) {
                const status = code === 0 ? 'passed' : 'failed';
                queries.updateTestRun(testId, status, {}, 0, 0, 0);
                bot.sendMessage(chatId, `Tests ${status}.\n\n\`\`\`\n${output.substring(0, 500)}\n\`\`\``, { parse_mode: 'Markdown' });
            }
        });
    });

    // /screenshot <page>
    bot.onText(/\/screenshot\s+(.+)/, (msg, match) => {
        if (!isAuthorized(msg)) return;
        const page = match[1].trim();
        bot.sendMessage(chatId, `Capturing screenshot of "${page}"...`);

        const proc = spawn(config.node.path, ['playwright/capture.mjs', page], {
            cwd: config.paths.root,
            stdio: ['pipe', 'pipe', 'pipe'],
        });

        let output = '';
        proc.stdout.on('data', (d) => output += d.toString());
        proc.stderr.on('data', (d) => output += d.toString());

        proc.on('close', (code) => {
            if (code === 0) {
                const screenshotPath = output.trim().split('\n').pop();
                if (existsSync(screenshotPath)) {
                    bot.sendPhoto(chatId, screenshotPath, { caption: `Screenshot: ${page}` });
                } else {
                    bot.sendMessage(chatId, `Screenshot captured but file not found at: ${screenshotPath}`);
                }
            } else {
                bot.sendMessage(chatId, `Screenshot failed:\n\`\`\`\n${output.substring(0, 500)}\n\`\`\``, { parse_mode: 'Markdown' });
            }
        });
    });

    // /pages
    bot.onText(/\/pages/, (msg) => {
        if (!isAuthorized(msg)) return;
        const pages = [
            'dashboard', 'login', 'jobs', 'candidates', 'employees',
            'projects', 'settings', 'users', 'integrations', 'llm',
            'scoring-rules', 'hiring-reports', 'intelligence',
            'platform-branding', 'organizations',
        ];
        bot.sendMessage(chatId,
            `*Available Pages*\n\n` +
            pages.map(p => `- \`${p}\``).join('\n') +
            `\n\n*Usage:*\n` +
            `\`/screenshot dashboard\` — named page\n` +
            `\`/screenshot /jobs/1\` — any route path\n` +
            `\`/screenshot /candidates/3\` — detail pages`,
            { parse_mode: 'Markdown' }
        );
    });

    // /instruct <text> — plan first, then approve
    bot.onText(/\/instruct\s+(.+)/s, (msg, match) => {
        if (!isAuthorized(msg)) return;
        const text = match[1].trim();
        queries.insertInstruction(text, 'telegram', 'plan');

        const runner = getRunnerStatus();
        if (runner.busy || runner.pendingPlan) {
            bot.sendMessage(chatId,
                `*Task queued* (plan mode, position ${runner.queuedCount + 1}):\n_${text}_\n\n_Will start when current work finishes._`,
                { parse_mode: 'Markdown' }
            );
        } else {
            bot.sendMessage(chatId, `*Task queued* (plan mode):\n_${text}_\n\n_Claude will create a plan for your approval._`, { parse_mode: 'Markdown' });
        }
    });

    // /run <text> — execute immediately, no planning
    bot.onText(/\/run\s+(.+)/s, (msg, match) => {
        if (!isAuthorized(msg)) return;
        const text = match[1].trim();
        queries.insertInstruction(text, 'telegram', 'direct');

        const runner = getRunnerStatus();
        if (runner.busy || runner.pendingPlan) {
            bot.sendMessage(chatId,
                `*Task queued* (direct mode, position ${runner.queuedCount + 1}):\n_${text}_\n\n_Will start when current work finishes._`,
                { parse_mode: 'Markdown' }
            );
        } else {
            bot.sendMessage(chatId, `*Task queued* (direct) — executing shortly:\n_${text}_`, { parse_mode: 'Markdown' });
        }
    });

    // /approve — approve pending plan
    bot.onText(/\/approve/, (msg) => {
        if (!isAuthorized(msg)) return;
        const result = approvePlan();
        if (result.approved) {
            bot.sendMessage(chatId, `*Plan approved* — executing now:\n_${result.instruction?.substring(0, 200)}_`, { parse_mode: 'Markdown' });
        } else {
            bot.sendMessage(chatId, 'No plan waiting for approval.');
        }
    });

    // /reject — reject pending plan
    bot.onText(/\/reject/, (msg) => {
        if (!isAuthorized(msg)) return;
        const result = rejectPlan();
        if (result.rejected) {
            bot.sendMessage(chatId, `*Plan rejected:*\n_${result.instruction?.substring(0, 200)}_`, { parse_mode: 'Markdown' });
        } else {
            bot.sendMessage(chatId, 'No plan waiting for approval.');
        }
    });

    // /context — reload project context (reduces token usage)
    bot.onText(/\/context/, (msg) => {
        if (!isAuthorized(msg)) return;
        const result = reloadContext();
        if (result.loaded) {
            bot.sendMessage(chatId, `*Context reloaded* (${result.chars} chars)\nClaude will use this to avoid re-exploring the codebase.`, { parse_mode: 'Markdown' });
        } else {
            bot.sendMessage(chatId, `Context not loaded: ${result.reason}\n\nGenerate it at: automation/.bridge/project-context.md`);
        }
    });

    // /newsession — reset conversation session (fresh start)
    bot.onText(/\/newsession/, (msg) => {
        if (!isAuthorized(msg)) return;
        const result = resetSession();
        if (result.previousSession) {
            bot.sendMessage(chatId,
                `*Session reset*\nPrevious: \`${result.previousSession.substring(0, 16)}...\`\n\nNext task will start a fresh conversation with full project context.`,
                { parse_mode: 'Markdown' }
            );
        } else {
            bot.sendMessage(chatId, 'No active session — already starting fresh.');
        }
    });

    // /cancel — cancel running task
    bot.onText(/\/cancel/, (msg) => {
        if (!isAuthorized(msg)) return;
        const result = cancelCurrentTask();
        if (result.cancelled) {
            bot.sendMessage(chatId, `*Task cancelled:*\n_${result.instruction?.substring(0, 200)}_`, { parse_mode: 'Markdown' });
        } else {
            bot.sendMessage(chatId, 'No task is currently running.');
        }
    });

    // /queue — show queued tasks
    bot.onText(/\/queue/, (msg) => {
        if (!isAuthorized(msg)) return;
        const runner = getRunnerStatus();

        let text = '';
        if (runner.busy) {
            const elapsed = Math.round(runner.currentTask.elapsed / 1000);
            const phaseIcon = runner.currentTask.phase === 'planning' ? '📋' : '🔨';
            text += `${phaseIcon} *${runner.currentTask.phase} (${elapsed}s):*\n_${runner.currentTask.instruction.substring(0, 200)}_\n\n`;
        }
        if (runner.pendingPlan) {
            text += `⏳ *Plan awaiting approval:*\n_${runner.pendingPlan.instruction.substring(0, 200)}_\nUse /approve or /reject\n\n`;
        }
        if (runner.queuedCount > 0) {
            text += `*Queued (${runner.queuedCount}):*\n`;
            for (const [i, task] of runner.queued.entries()) {
                const modeTag = task.mode === 'direct' ? '⚡' : '📋';
                text += `${i + 1}. ${modeTag} _${task.text.substring(0, 100)}_\n`;
            }
        }
        if (!text) {
            text = 'No tasks running or queued.\n\n/instruct <text> — plan first\n/run <text> — execute directly';
        }
        bot.sendMessage(chatId, text, { parse_mode: 'Markdown' });
    });

    // /history
    bot.onText(/\/history/, (msg) => {
        if (!isAuthorized(msg)) return;
        const events = queries.getRecentEvents(10);
        if (events.length === 0) {
            bot.sendMessage(chatId, 'No recent events.');
            return;
        }
        let text = `*Recent Events*\n\n`;
        for (const evt of events) {
            const status = evt.status === 'approved' ? '✓' : evt.status === 'rejected' ? '✗' : '⏳';
            text += `${status} [${evt.type}] ${evt.summary?.substring(0, 60) || 'No summary'}\n   _${evt.created_at}_\n\n`;
        }
        bot.sendMessage(chatId, text, { parse_mode: 'Markdown' });
    });

    // Handle callback queries (inline keyboard buttons)
    bot.on('callback_query', (callbackQuery) => {
        const data = callbackQuery.data;
        const chatIdCb = callbackQuery.message.chat.id;
        if (String(chatIdCb) !== String(chatId)) return;

        const [action, eventId] = data.split(':');

        // Plan approval/rejection via inline buttons
        if (action === 'plan_approve') {
            const result = approvePlan();
            if (result.approved) {
                bot.editMessageReplyMarkup({ inline_keyboard: [] }, {
                    chat_id: chatId,
                    message_id: callbackQuery.message.message_id,
                });
                bot.answerCallbackQuery(callbackQuery.id, { text: 'Plan approved! Executing...' });
            } else {
                bot.answerCallbackQuery(callbackQuery.id, { text: 'No plan pending' });
            }
            return;
        }

        if (action === 'plan_reject') {
            const result = rejectPlan();
            if (result.rejected) {
                bot.editMessageReplyMarkup({ inline_keyboard: [] }, {
                    chat_id: chatId,
                    message_id: callbackQuery.message.message_id,
                });
                bot.answerCallbackQuery(callbackQuery.id, { text: 'Plan rejected' });
            } else {
                bot.answerCallbackQuery(callbackQuery.id, { text: 'No plan pending' });
            }
            return;
        }

        // Screenshot capture via inline button
        if (action === 'screenshot') {
            const page = eventId; // part after "screenshot:"
            bot.answerCallbackQuery(callbackQuery.id, { text: `Capturing ${page}...` });
            captureAndSendScreenshot(page);
            return;
        }

        // Hook event approval/rejection
        if (action === 'approve' || action === 'reject') {
            const evt = queries.getEvent(eventId);
            if (!evt) {
                bot.answerCallbackQuery(callbackQuery.id, { text: 'Event not found' });
                return;
            }

            const response = action === 'approve' ? 'approved' : 'rejected';
            queries.updateEventResponse(eventId, response, response, callbackQuery.message.message_id);

            // Write response file for hook polling
            const responseFile = resolve(config.paths.responses, `${eventId}.json`);
            writeFileSync(responseFile, JSON.stringify({
                decision: action === 'approve' ? 'allow' : 'block',
                reason: action === 'approve' ? 'Approved via Telegram' : 'Rejected via Telegram',
                timestamp: new Date().toISOString(),
            }));

            // Update Telegram message to show result
            bot.editMessageText(
                `${evt.summary || 'Event'}\n\n*${action === 'approve' ? 'APPROVED ✓' : 'REJECTED ✗'}*`,
                {
                    chat_id: chatId,
                    message_id: callbackQuery.message.message_id,
                    parse_mode: 'Markdown',
                }
            );

            bot.answerCallbackQuery(callbackQuery.id, { text: `${action === 'approve' ? 'Approved' : 'Rejected'}!` });
        }
    });

    // Handle text messages that might be instructions during a pending approval
    bot.on('message', (msg) => {
        if (!isAuthorized(msg)) return;
        // Skip commands
        if (msg.text?.startsWith('/')) return;

        // If there's a pending event, treat plain text as instruction
        const pending = queries.getPendingEvents();
        if (pending.length > 0 && msg.text) {
            const evt = pending[0];
            queries.updateEventResponse(evt.id, 'instruction', msg.text, null);

            const responseFile = resolve(config.paths.responses, `${evt.id}.json`);
            writeFileSync(responseFile, JSON.stringify({
                decision: 'block',
                reason: `New instruction: ${msg.text}`,
                timestamp: new Date().toISOString(),
            }));

            bot.sendMessage(chatId, `Instruction sent to Claude:\n_${msg.text}_`, { parse_mode: 'Markdown' });
        }
    });

    console.log('[Telegram] Bot initialized and polling for messages');
    return bot;
}

// ── Message Utilities ────────────────────────────────────

const MSG_MAX = 4000; // Telegram limit is 4096; leave margin

/** Split text at paragraph (\n\n) or line (\n) boundaries, respecting MSG_MAX per chunk */
function splitAtBoundaries(text, max = MSG_MAX) {
    if (text.length <= max) return [text];
    const chunks = [];
    let remaining = text;
    while (remaining.length > max) {
        let cutAt = remaining.lastIndexOf('\n\n', max);
        if (cutAt < max * 0.3) cutAt = remaining.lastIndexOf('\n', max);
        if (cutAt < max * 0.3) cutAt = max; // hard cut
        chunks.push(remaining.substring(0, cutAt));
        remaining = remaining.substring(cutAt).replace(/^\n+/, '');
    }
    if (remaining.trim()) chunks.push(remaining);
    return chunks;
}

/** Send a long message, splitting across multiple Telegram messages if needed */
async function sendLongMessage(chatId, text, opts = {}) {
    if (!bot) return null;
    const chunks = splitAtBoundaries(text, MSG_MAX);
    let lastMsg;
    for (const chunk of chunks) {
        try {
            lastMsg = await bot.sendMessage(chatId, chunk, opts);
        } catch {
            // Markdown failed — retry without parse_mode
            const fallbackOpts = { ...opts };
            delete fallbackOpts.parse_mode;
            lastMsg = await bot.sendMessage(chatId, chunk, fallbackOpts).catch(() => null);
        }
    }
    return lastMsg;
}

/** Capture a page screenshot and send it as a Telegram photo */
export function captureAndSendScreenshot(page) {
    if (!bot) return;
    const proc = spawn(config.node.path, ['playwright/capture.mjs', page], {
        cwd: config.paths.root,
        stdio: ['pipe', 'pipe', 'pipe'],
    });
    let output = '';
    proc.stdout.on('data', (d) => output += d.toString());
    proc.stderr.on('data', (d) => output += d.toString());
    proc.on('close', (code) => {
        if (code === 0) {
            const screenshotPath = output.trim().split('\n').pop();
            if (existsSync(screenshotPath)) {
                bot.sendPhoto(config.telegram.chatId, screenshotPath, { caption: `📸 ${page}` });
            }
        } else {
            console.log(`[Telegram] Screenshot capture failed for ${page}: ${output.substring(0, 200)}`);
        }
    });
}

// ── Exported Message Functions ───────────────────────────

export function sendApprovalMessage(eventId, summary, type = 'stop') {
    if (!bot) return null;

    const keyboard = {
        reply_markup: {
            inline_keyboard: [
                [
                    { text: 'Approve ✓', callback_data: `approve:${eventId}` },
                    { text: 'Reject ✗', callback_data: `reject:${eventId}` },
                ],
            ],
        },
        parse_mode: 'Markdown',
    };

    // Truncate summary to stay within Telegram limits
    const safeSummary = summary.length > 3500 ? summary.substring(0, 3500) + '...' : summary;
    const icon = type === 'stop' ? '🔔' : type === 'error' ? '⚠️' : 'ℹ️';
    return bot.sendMessage(
        config.telegram.chatId,
        `${icon} *Claude Code — ${type.toUpperCase()}*\n\n${safeSummary}\n\n_Tap to approve or reject, or reply with a new instruction._`,
        keyboard
    );
}

export function sendNotification(text, parseMode = 'Markdown') {
    if (!bot) return null;
    const opts = parseMode ? { parse_mode: parseMode } : {};
    return sendLongMessage(config.telegram.chatId, text, opts);
}

/** Send a notification with inline keyboard buttons (not split — must fit in one message) */
export function sendNotificationWithButtons(text, buttons, parseMode = 'Markdown') {
    if (!bot) return null;
    const safeText = text.length > MSG_MAX ? text.substring(0, MSG_MAX - 50) + '...' : text;
    const opts = {
        reply_markup: { inline_keyboard: [buttons] },
    };
    if (parseMode) opts.parse_mode = parseMode;
    return bot.sendMessage(config.telegram.chatId, safeText, opts).catch(err => {
        // Fallback without Markdown
        console.log(`[Telegram] Button message Markdown failed: ${err.message}`);
        delete opts.parse_mode;
        return bot.sendMessage(config.telegram.chatId, safeText, opts);
    });
}

export function sendPlanApproval(instruction, plan, elapsed, costStr) {
    if (!bot) return null;

    const escapeMd = (s) => s ? s.replace(/([_*`\[\]])/g, '\\$1') : '';
    const buttons = [
        { text: 'Approve ✓', callback_data: 'plan_approve:1' },
        { text: 'Reject ✗', callback_data: 'plan_reject:1' },
    ];

    const header = `📋 *Plan ready* (${elapsed}s${costStr}):\n_${escapeMd(instruction.substring(0, 200))}_\n\n`;
    const planText = escapeMd(plan);

    // If plan fits in one message with header, send with buttons
    if ((header + planText).length <= MSG_MAX) {
        return sendNotificationWithButtons(header + planText, buttons);
    }

    // Plan is long — split: send plan chunks first, then buttons on last chunk
    const chunks = splitAtBoundaries(planText, MSG_MAX - 100);
    return (async () => {
        // Send header + first chunk
        await sendLongMessage(config.telegram.chatId, header + (chunks.shift() || ''), { parse_mode: 'Markdown' });
        // Send middle chunks
        for (const chunk of chunks.slice(0, -1)) {
            await sendLongMessage(config.telegram.chatId, chunk, { parse_mode: 'Markdown' });
        }
        // Send last chunk with buttons (or just buttons if no more chunks)
        const lastChunk = chunks.length > 0 ? chunks[chunks.length - 1] : '_End of plan_';
        return sendNotificationWithButtons(lastChunk, buttons);
    })().catch(err => {
        console.log(`[Telegram] Plan send failed: ${err.message}, sending plain`);
        const plain = `PLAN (${elapsed}s${costStr}):\n${instruction.substring(0, 200)}\n\n${plan.substring(0, 3500)}`;
        return sendNotificationWithButtons(plain, buttons, '');
    });
}

export function editMessage(messageId, text, parseMode = 'Markdown') {
    if (!bot) return null;
    // Telegram edit limit is also 4096
    const safeText = text.length > MSG_MAX ? text.substring(0, MSG_MAX - 10) + '...' : text;
    return bot.editMessageText(safeText, {
        chat_id: config.telegram.chatId,
        message_id: messageId,
        parse_mode: parseMode,
    }).catch(() => {
        // Edit can fail if message is too old or content unchanged — ignore
    });
}

export function sendPhoto(filePath, caption = '') {
    if (!bot) return null;
    return bot.sendPhoto(config.telegram.chatId, filePath, { caption });
}

function parseTestResults(results) {
    const stats = { total: 0, passed: 0, failed: 0, failures: [] };
    if (!results?.suites) return stats;

    function walkSuite(suite) {
        for (const spec of (suite.specs || [])) {
            for (const test of (spec.tests || [])) {
                stats.total++;
                const result = test.results?.[0];
                if (result?.status === 'passed') {
                    stats.passed++;
                } else {
                    stats.failed++;
                    stats.failures.push({
                        name: spec.title,
                        error: result?.error?.message || 'Unknown error',
                        screenshot: result?.attachments?.find(a => a.name === 'screenshot')?.path,
                    });
                }
            }
        }
        for (const child of (suite.suites || [])) {
            walkSuite(child);
        }
    }

    for (const suite of results.suites) {
        walkSuite(suite);
    }
    return stats;
}

async function sendFailureScreenshots(failures) {
    if (!bot) return;
    for (const f of failures.slice(0, 3)) {
        if (f.screenshot && existsSync(f.screenshot)) {
            await bot.sendPhoto(config.telegram.chatId, f.screenshot, { caption: `Failed: ${f.name}` });
        }
    }
}
