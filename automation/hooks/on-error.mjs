/**
 * Claude Code Hook: PostToolUseFailure (Bash)
 * Sends error alerts when Bash commands fail.
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

    const command = data.input?.command || 'unknown command';
    const error = data.error || data.output || 'Unknown error';

    await postEvent('/api/events', {
        type: 'error',
        summary: `Command failed:\n\`${command.substring(0, 100)}\`\n\nError: ${String(error).substring(0, 300)}`,
        data: { command, error: String(error).substring(0, 500) },
    });
}

main().catch(console.error);
