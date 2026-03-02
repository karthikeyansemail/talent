/**
 * Claude Code Hook: SessionEnd (SubprocessExit)
 * Notifies Telegram when a Claude Code session ends.
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
        // No input
    }

    await postEvent('/api/events', {
        type: 'session_end',
        summary: 'Claude Code session ended.',
        data,
    });
}

main().catch(console.error);
