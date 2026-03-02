/**
 * Task Runner — two-phase plan→execute flow via Claude CLI.
 *
 * Phase 1 (Plan): Claude reads the codebase and creates an implementation plan.
 *   - Read-only: prompt instructs Claude to only analyze, not modify.
 *   - Plan is sent to Telegram with Approve / Reject buttons.
 *
 * Phase 2 (Execute): After approval, Claude executes the plan with full permissions.
 *   - Uses --dangerously-skip-permissions for unattended execution.
 *   - Streams progress to Telegram in real-time.
 *
 * Direct mode (/run): Skips planning, executes immediately with full permissions.
 */
import { spawn } from 'child_process';
import { readFileSync, writeFileSync, existsSync, unlinkSync } from 'fs';
import { resolve } from 'path';
import { config } from './config.mjs';
import { queries } from './db.mjs';
import { sendNotification, sendNotificationWithButtons, editMessage, sendPlanApproval, captureAndSendScreenshot } from './telegram-bot.mjs';

// Load project context for --append-system-prompt (reduces token usage)
const CONTEXT_PATH = resolve(config.paths.bridge, 'project-context.md');
const SESSION_PATH = resolve(config.paths.bridge, 'session-id.txt');
let projectContext = '';
let sessionId = null;

try {
    if (existsSync(CONTEXT_PATH)) {
        projectContext = readFileSync(CONTEXT_PATH, 'utf-8');
        console.log(`[TaskRunner] Loaded project context (${projectContext.length} chars)`);
    }
} catch { /* ignore */ }

// Load persisted session ID — enables shared conversation context across tasks
try {
    if (existsSync(SESSION_PATH)) {
        sessionId = readFileSync(SESSION_PATH, 'utf-8').trim();
        if (sessionId) console.log(`[TaskRunner] Resuming session: ${sessionId}`);
    }
} catch { /* ignore */ }

function saveSessionId(id) {
    if (!id) return;
    sessionId = id;
    try { writeFileSync(SESSION_PATH, id); } catch { /* ignore */ }
    log(`Session saved: ${id}`);
}

function clearSession() {
    sessionId = null;
    try { if (existsSync(SESSION_PATH)) unlinkSync(SESSION_PATH); } catch { /* ignore */ }
    log('Session cleared — next task starts fresh');
}

/** Check if a failure was caused by a stale --resume session. If so, clear it. */
function handleResumeFailure(code, stderr) {
    if (code !== 0 && sessionId && /session|resume|conversation|not found/i.test(stderr)) {
        log(`Detected resume failure — clearing stale session ${sessionId}`);
        clearSession();
        return true;
    }
    return false;
}

let currentTask = null;
let pendingPlan = null;
let pollInterval = null;

function log(msg) {
    console.log(`[TaskRunner] ${msg}`);
}

// ── Public API ──────────────────────────────────────────

/** Reset conversation session — next task starts a fresh Claude session */
export function resetSession() {
    const old = sessionId;
    clearSession();
    return { cleared: true, previousSession: old };
}

/** Get current session ID */
export function getSessionId() {
    return sessionId;
}

/** Reload project context from disk (e.g. after regenerating it) */
export function reloadContext() {
    try {
        if (existsSync(CONTEXT_PATH)) {
            projectContext = readFileSync(CONTEXT_PATH, 'utf-8');
            log(`Context reloaded (${projectContext.length} chars)`);
            return { loaded: true, chars: projectContext.length };
        }
        return { loaded: false, reason: 'File not found' };
    } catch (err) {
        return { loaded: false, reason: err.message };
    }
}

export function startTaskRunner() {
    log(`Started — polling every 5s`);
    log(`Claude CLI: ${config.claude.cliPath}`);
    log(`Project dir: ${config.claude.projectRoot}`);
    pollInterval = setInterval(checkForTasks, 5000);
}

export function stopTaskRunner() {
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    if (currentTask) cleanup();
    if (pendingPlan?.timeout) clearTimeout(pendingPlan.timeout);
    pendingPlan = null;
}

export function cancelCurrentTask() {
    if (!currentTask) return { cancelled: false };
    const instruction = currentTask.instruction;
    currentTask.process.kill();
    cleanup();
    return { cancelled: true, instruction };
}

export function getRunnerStatus() {
    const queued = queries.getUnconsumedInstructions();
    return {
        busy: !!currentTask,
        phase: currentTask?.phase || null,
        sessionId: sessionId || null,
        pendingPlan: pendingPlan ? {
            instruction: pendingPlan.instruction,
            planPreview: pendingPlan.plan.substring(0, 200),
        } : null,
        currentTask: currentTask ? {
            instruction: currentTask.instruction,
            phase: currentTask.phase,
            startedAt: currentTask.startedAt,
            elapsed: Date.now() - currentTask.startedAt,
            actions: currentTask.actions?.slice(-5) || [],
        } : null,
        queuedCount: queued.length,
        queued: queued.map(i => ({ text: i.text, mode: i.mode || 'plan' })),
    };
}

/** Called when user approves a plan from Telegram */
export function approvePlan() {
    if (!pendingPlan) return { approved: false, reason: 'No plan pending' };
    const { instruction, instructionId, plan } = pendingPlan;
    if (pendingPlan.timeout) clearTimeout(pendingPlan.timeout);
    pendingPlan = null;
    log(`Plan approved for: "${instruction.substring(0, 80)}"`);
    executePhase(instruction, instructionId, plan).catch(err => {
        log(`Execute phase error: ${err.message}`);
        currentTask = null;
        sendNotification(`*Execute error:* ${err.message}`).catch(() => {});
    });
    return { approved: true, instruction };
}

/** Called when user rejects a plan from Telegram */
export function rejectPlan() {
    if (!pendingPlan) return { rejected: false, reason: 'No plan pending' };
    const instruction = pendingPlan.instruction;
    if (pendingPlan.timeout) clearTimeout(pendingPlan.timeout);
    pendingPlan = null;
    log(`Plan rejected for: "${instruction.substring(0, 80)}"`);
    return { rejected: true, instruction };
}

// ── Internals ───────────────────────────────────────────

function cleanup() {
    if (currentTask?.progressInterval) clearInterval(currentTask.progressInterval);
    if (currentTask?.process && !currentTask.process.killed) currentTask.process.kill();
    currentTask = null;
}

function checkForTasks() {
    if (currentTask) return;
    if (pendingPlan) return; // waiting for plan approval
    const instructions = queries.getUnconsumedInstructions();
    if (instructions.length === 0) return;
    const instruction = instructions[0];
    log(`Found instruction #${instruction.id}: "${instruction.text.substring(0, 80)}" mode=${instruction.mode || 'plan'}`);
    queries.markInstructionConsumed(instruction.id);

    const mode = instruction.mode || 'plan';

    if (mode === 'direct') {
        // Skip planning — execute immediately with full permissions
        executePhase(instruction.text, instruction.id, null).catch(err => {
            log(`Direct execute error: ${err.message}`);
            currentTask = null;
            sendNotification(`*Task error:* ${err.message}`).catch(() => {});
        });
    } else {
        // Plan first, then wait for approval
        planPhase(instruction.text, instruction.id).catch(err => {
            log(`Plan phase error: ${err.message}`);
            currentTask = null;
            sendNotification(`*Planning error:* ${err.message}`).catch(() => {});
        });
    }
}

// ── Phase 1: Planning ───────────────────────────────────

const PLAN_PROMPT_PREFIX = `You are in PLANNING MODE. Your job is to analyze the codebase and create a clear implementation plan.

RULES:
- Read and explore code to understand what needs to change
- Output a structured plan with:
  1. Summary of what you'll do
  2. Files to modify or create (with specific changes)
  3. Commands to run (migrations, tests, etc.) if any
- Do NOT make any changes — only plan
- Be specific: mention function names, line numbers, exact changes
- Keep the plan concise but complete

TASK: `;

async function planPhase(prompt, instructionId) {
    const claudePath = config.claude.cliPath;
    const cwd = config.claude.projectRoot;
    const timeout = config.claude.taskTimeout;
    const planPrompt = PLAN_PROMPT_PREFIX + prompt;

    log(`Planning: "${prompt.substring(0, 80)}" cwd=${cwd}`);

    let progressMsgId = null;
    try {
        const startMsg = await sendNotification(
            `*Planning:*\n_${escapeMd(trunc(prompt, 300))}_\n\n⏳ Claude is analyzing the codebase...\n_/cancel to abort_`
        );
        progressMsgId = startMsg?.message_id || null;
    } catch (err) {
        log(`Failed to send plan start notification: ${err.message}`);
    }

    const { proc, cleanEnv } = spawnClaude(claudePath, planPrompt, cwd, timeout);

    const startedAt = Date.now();
    const state = createTaskState(proc, prompt, instructionId, 'planning', startedAt, progressMsgId);
    currentTask = state;

    setupStreamParser(proc, state);

    state.progressInterval = setInterval(() => {
        if (!currentTask) return;
        sendProgressUpdate(state, 'planning');
    }, 15000);

    proc.on('close', (code) => {
        log(`Plan phase closed, code=${code}, result=${state.finalResult.length}chars`);
        if (currentTask?.progressInterval) clearInterval(currentTask.progressInterval);
        currentTask = null;

        const elapsed = Math.round((Date.now() - startedAt) / 1000);
        const costStr = state.costUsd ? ` | $${state.costUsd.toFixed(4)}` : '';

        if (code === 0 && state.finalResult.trim()) {
            const plan = state.finalResult.trim();
            log(`Plan ready (${plan.length} chars), sending for approval`);

            // Store pending plan with 10-minute timeout
            pendingPlan = {
                instruction: prompt,
                instructionId,
                plan,
                timeout: setTimeout(() => {
                    if (pendingPlan) {
                        log(`Plan approval timed out for: "${prompt.substring(0, 80)}"`);
                        pendingPlan = null;
                        sendNotification(`*Plan expired* (no response in 10min):\n_${escapeMd(trunc(prompt, 200))}_`).catch(() => {});
                    }
                }, 600000),
            };

            // Send plan to Telegram with approval buttons
            sendPlanApproval(prompt, plan, elapsed, costStr).catch(err => {
                log(`Failed to send plan approval: ${err.message}`);
                // Fallback to plain text
                const plain = `PLAN (${elapsed}s${costStr}):\n${trunc(prompt, 150)}\n\n${trunc(plan, 3000)}\n\nReply /approve or /reject`;
                sendNotification(plain, '').catch(() => {});
            });
        } else if (code === null) {
            sendNotification(`*Planning cancelled* (${elapsed}s):\n_${escapeMd(trunc(prompt, 200))}_`).catch(() => {});
        } else {
            const errOut = state.stderr.trim() || state.finalResult.trim() || `Exit code ${code}`;
            log(`Plan phase failed: ${errOut.substring(0, 200)}`);
            // If resume failed, clear session so next task starts fresh
            if (handleResumeFailure(code, errOut)) {
                sendNotification(`Session expired — will retry with fresh context.\nRe-queue with /instruct`).catch(() => {});
            } else {
                const plain = `Planning failed (${elapsed}s, code ${code}):\n${trunc(prompt, 200)}\n\n${trunc(errOut, 2000)}`;
                sendNotification(plain, '').catch(() => {});
            }
        }

        const remaining = queries.getUnconsumedInstructions();
        if (remaining.length > 0) {
            sendNotification(`_${remaining.length} more task(s) queued..._`).catch(() => {});
        }
    });

    proc.on('error', (err) => {
        log(`Plan process error: ${err.message}`);
        if (currentTask?.progressInterval) clearInterval(currentTask.progressInterval);
        currentTask = null;
        sendNotification(`Plan error: ${err.message}\n\nCheck CLAUDE_CLI_PATH in .env`).catch(() => {});
    });
}

// ── Phase 2: Execution ──────────────────────────────────

async function executePhase(prompt, instructionId, plan) {
    const claudePath = config.claude.cliPath;
    const cwd = config.claude.projectRoot;
    const timeout = config.claude.taskTimeout;

    // Build execution prompt
    let execPrompt;
    if (plan) {
        execPrompt = `Execute the following approved plan. Make all changes as described.\n\nORIGINAL REQUEST: ${prompt}\n\nAPPROVED PLAN:\n${plan}\n\nExecute this plan now. Implement all the changes described above.`;
    } else {
        execPrompt = prompt; // direct mode — no plan context
    }

    const phase = plan ? 'executing' : 'direct';
    log(`${phase}: "${prompt.substring(0, 80)}" cwd=${cwd}`);

    let progressMsgId = null;
    try {
        const label = plan ? 'Executing approved plan' : 'Running task (direct)';
        const startMsg = await sendNotification(
            `*${label}:*\n_${escapeMd(trunc(prompt, 300))}_\n\n⏳ Claude is working...\n_/cancel to abort_`
        );
        progressMsgId = startMsg?.message_id || null;
    } catch (err) {
        log(`Failed to send execute start notification: ${err.message}`);
    }

    const { proc } = spawnClaude(claudePath, execPrompt, cwd, timeout);

    const startedAt = Date.now();
    const state = createTaskState(proc, prompt, instructionId, phase, startedAt, progressMsgId);
    currentTask = state;

    setupStreamParser(proc, state);

    state.progressInterval = setInterval(() => {
        if (!currentTask) return;
        sendProgressUpdate(state, phase);
    }, 15000);

    proc.on('close', (code) => {
        log(`Execute phase closed, code=${code}, actions=${state.actions.length}, result=${state.finalResult.length}chars`);
        if (currentTask?.progressInterval) clearInterval(currentTask.progressInterval);
        currentTask = null;

        const elapsed = Math.round((Date.now() - startedAt) / 1000);
        const costStr = state.costUsd ? ` | $${state.costUsd.toFixed(4)}` : '';

        try {
            if (code === 0) {
                const output = state.finalResult.trim() || 'Done (no text output)';
                let msg = `✅ *Task done* (${elapsed}s${costStr}):\n_${escapeMd(trunc(prompt, 150))}_\n\n`;

                if (state.actions.length > 0) {
                    msg += `*Actions (${state.actions.length}):*\n`;
                    const show = state.actions.length <= 8 ? state.actions : [...state.actions.slice(0, 5), `... ${state.actions.length - 8} more ...`, ...state.actions.slice(-3)];
                    for (const a of show) msg += `${escapeMd(a)}\n`;
                    msg += `\n`;
                }

                const safeOutput = escapeMd(trunc(output, 2000));
                msg += `*Result:*\n${safeOutput}`;

                // Detect affected pages for screenshot buttons
                const affectedPages = detectAffectedPages(state.actions);
                const screenshotBtns = buildScreenshotButtons(affectedPages);

                log(`Sending success message (${msg.length} chars, ${affectedPages.length} affected pages)`);

                if (screenshotBtns.length > 0) {
                    // Send with View screenshot buttons
                    sendNotificationWithButtons(msg, screenshotBtns).catch(err => {
                        log(`Button send failed: ${err.message}, falling back`);
                        sendNotification(msg).catch(() => {});
                    });
                } else {
                    sendNotification(msg).catch(err => {
                        log(`Markdown send failed: ${err.message}, falling back to plain text`);
                        const plain = `Task done (${elapsed}s${costStr}):\n${trunc(prompt, 150)}\n\nResult:\n${trunc(output, 2000)}`;
                        sendNotification(plain, '').catch(e => log(`Plain text send also failed: ${e.message}`));
                    });
                }

                // Auto-capture screenshots of affected pages (runs in background)
                captureAffectedScreenshots(affectedPages);
            } else if (code === null) {
                sendNotification(`*Task cancelled* (${elapsed}s):\n_${escapeMd(trunc(prompt, 200))}_`).catch(() => {});
            } else {
                const errOut = state.stderr.trim() || state.finalResult.trim() || `Exit code ${code}`;
                log(`Task failed: ${errOut.substring(0, 200)}`);
                // If resume failed, clear session so next task starts fresh
                if (handleResumeFailure(code, errOut)) {
                    sendNotification(`Session expired — will retry with fresh context.\nRe-queue with /instruct or /run`).catch(() => {});
                } else {
                    const plain = `Task failed (${elapsed}s, code ${code}):\n${trunc(prompt, 200)}\n\n${trunc(errOut, 2000)}`;
                    sendNotification(plain, '').catch(err => {
                        log(`Failed to send error: ${err.message}`);
                    });
                }
            }
        } catch (err) {
            log(`Error in close handler: ${err.message}`);
            log(err.stack);
        }

        const remaining = queries.getUnconsumedInstructions();
        if (remaining.length > 0) {
            sendNotification(`_${remaining.length} more task(s) queued..._`).catch(() => {});
        }
    });

    proc.on('error', (err) => {
        log(`Execute process error: ${err.message}`);
        if (currentTask?.progressInterval) clearInterval(currentTask.progressInterval);
        currentTask = null;
        sendNotification(`Task error: ${err.message}\n\nCheck CLAUDE_CLI_PATH in .env`).catch(() => {});
    });
}

// ── Shared helpers ──────────────────────────────────────

function spawnClaude(claudePath, prompt, cwd, timeout) {
    const args = ['-p', prompt, '--output-format', 'stream-json', '--verbose', '--dangerously-skip-permissions'];

    // Resume existing session for shared context across tasks
    if (sessionId) {
        args.push('--resume', sessionId);
        log(`Resuming session: ${sessionId}`);
    }

    // Only inject system prompt on first run (no session yet) — avoids duplicate injection
    if (!sessionId && projectContext) {
        args.push('--append-system-prompt', projectContext);
    }

    const cleanEnv = { ...process.env };
    for (const key of Object.keys(cleanEnv)) {
        if (/^CLAUDE/i.test(key)) delete cleanEnv[key];
    }

    log(`Spawning: ${claudePath} -p "..." (${prompt.length} chars, session=${sessionId || 'new'}, context=${!sessionId && projectContext ? projectContext.length : 0} chars)`);

    const proc = spawn(claudePath, args, {
        cwd,
        env: cleanEnv,
        stdio: ['pipe', 'pipe', 'pipe'],
        timeout,
    });

    proc.stdin.end();
    log(`Process spawned, PID=${proc.pid}`);

    return { proc, cleanEnv };
}

function createTaskState(proc, instruction, instructionId, phase, startedAt, progressMsgId) {
    return {
        process: proc,
        instruction,
        instructionId,
        phase,
        startedAt,
        actions: [],
        lastSentActionIndex: 0, // track which actions were already sent (delta-only updates)
        progressInterval: null,
        progressMsgId,
        finalResult: '',
        lastText: '',
        stderr: '',
        costUsd: 0,
        lastProgressSent: 0,
    };
}

function setupStreamParser(proc, state) {
    let lineBuf = '';

    proc.stdout.on('data', (chunk) => {
        const raw = chunk.toString();
        log(`STDOUT chunk (${raw.length} bytes)`);
        lineBuf += raw;
        const lines = lineBuf.split('\n');
        lineBuf = lines.pop();

        for (const line of lines) {
            if (!line.trim()) continue;
            try {
                const evt = JSON.parse(line);
                processEvent(evt, state);
            } catch {
                log(`Non-JSON line: ${line.substring(0, 100)}`);
            }
        }
    });

    proc.stderr.on('data', (d) => {
        const s = d.toString();
        log(`STDERR: ${s.substring(0, 200)}`);
        state.stderr += s;
    });
}

function processEvent(evt, state) {
    if (evt.type === 'assistant' && evt.message?.content) {
        for (const block of evt.message.content) {
            if (block.type === 'tool_use') {
                const action = formatToolUse(block.name, block.input);
                log(`Action: ${action}`);
                state.actions.push(action);
                maybeSendProgress(state);
            } else if (block.type === 'text' && block.text) {
                state.lastText = block.text;
                log(`Text: ${block.text.substring(0, 100)}`);
            }
        }
    }

    if (evt.type === 'result') {
        state.finalResult = evt.result || state.lastText || '';
        state.costUsd = evt.total_cost_usd || 0;
        // Persist session ID so subsequent tasks share conversation context
        if (evt.session_id) {
            saveSessionId(evt.session_id);
        }
        log(`Result received, cost=$${state.costUsd}, length=${state.finalResult.length}, session=${evt.session_id || 'none'}`);
    }
}

function maybeSendProgress(state) {
    const now = Date.now();
    if (now - state.lastProgressSent < 5000) return;
    state.lastProgressSent = now;
    sendProgressUpdate(state, state.phase);
}

function sendProgressUpdate(state, phase) {
    const elapsed = Math.round((Date.now() - state.startedAt) / 1000);
    const phaseLabel = phase === 'planning' ? '📋 Planning' : '🔨 Executing';

    // Always update the header message in-place (elapsed time + action count)
    const header = `*${phaseLabel}:* _${escapeMd(trunc(state.instruction, 150))}_\n⏳ *${elapsed}s* | ${state.actions.length} actions\n_/cancel to abort_`;
    if (state.progressMsgId) {
        editMessage(state.progressMsgId, header).catch(() => {});
    }

    // Send only NEW actions since last update (delta-only — no repeats)
    const newActions = state.actions.slice(state.lastSentActionIndex);
    if (newActions.length > 0) {
        const batch = newActions.map(a => escapeMd(a)).join('\n');
        sendNotification(batch).catch(() => {});
        state.lastSentActionIndex = state.actions.length;
    }
}

// ── Auto-Screenshot Detection ────────────────────────────

const VIEW_PATTERNS = {
    'candidates': /candidate/i,
    'employees': /employee/i,
    'jobs': /job/i,
    'projects': /project/i,
    'dashboard': /dashboard/i,
    'settings': /setting|organization/i,
    'users': /user/i,
    'intelligence': /intelligence|signal/i,
    'hiring-reports': /hiring.*report/i,
};

/** Detect which app pages were affected based on the actions list */
function detectAffectedPages(actions) {
    const pages = new Set();
    for (const action of actions) {
        for (const [page, pattern] of Object.entries(VIEW_PATTERNS)) {
            if (pattern.test(action)) pages.add(page);
        }
    }
    return [...pages].slice(0, 3); // max 3 screenshots
}

/** Capture screenshots of affected pages and send them to Telegram */
function captureAffectedScreenshots(pages) {
    if (pages.length === 0) return;
    log(`Auto-capturing screenshots for: ${pages.join(', ')}`);
    // Stagger captures slightly to avoid overloading
    pages.forEach((page, i) => {
        setTimeout(() => captureAndSendScreenshot(page), i * 3000);
    });
}

/** Build inline keyboard buttons for screenshot capture */
function buildScreenshotButtons(pages) {
    if (pages.length === 0) return [];
    return pages.map(p => ({ text: `📸 ${p}`, callback_data: `screenshot:${p}` }));
}

function formatToolUse(name, input) {
    switch (name) {
        case 'Read': return `Read ${shortPath(input?.file_path)}`;
        case 'Write': return `Write ${shortPath(input?.file_path)}`;
        case 'Edit': return `Edit ${shortPath(input?.file_path)}`;
        case 'Bash': return `Run: ${trunc(input?.command || '', 80)}`;
        case 'Glob': return `Search: ${input?.pattern}`;
        case 'Grep': return `Grep: ${trunc(input?.pattern || '', 60)}`;
        case 'Task': return `Agent: ${trunc(input?.description || '', 60)}`;
        default: return name;
    }
}

function shortPath(p) {
    if (!p) return '?';
    const parts = p.replace(/\\/g, '/').split('/');
    return parts.slice(-3).join('/');
}

function trunc(str, max) {
    if (!str) return '';
    if (str.length <= max) return str;
    return str.substring(0, max) + '...';
}

function escapeMd(str) {
    if (!str) return '';
    return str.replace(/([_*`\[\]])/g, '\\$1');
}
