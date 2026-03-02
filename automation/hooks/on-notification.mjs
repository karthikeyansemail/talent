/**
 * Claude Code Hook: Notification
 * Forwards permission prompts and idle notifications to Telegram.
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
        return;
    }

    const message = data.message || data.text || JSON.stringify(data);

    await postEvent('/api/events', {
        type: 'notification',
        summary: message.substring(0, 500),
        data,
    });
}

main().catch(console.error);
