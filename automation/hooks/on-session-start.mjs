/**
 * Claude Code Hook: SessionStart
 * Notifies Telegram when a new Claude Code session starts.
 *
 * Runs async (non-blocking) — timeout 10s.
 */
import { postEvent } from './lib/bridge-client.mjs';

async function main() {
    let input = '';
    for await (const chunk of process.stdin) {
        input += chunk;
    }

    let data = {};
    try {
        data = JSON.parse(input);
    } catch {
        // No input or invalid JSON
    }

    await postEvent('/api/events', {
        type: 'session_start',
        summary: `Claude Code session started.\nWorking directory: ${data.cwd || process.cwd()}`,
        data: { cwd: data.cwd || process.cwd() },
    });
}

main().catch(console.error);
