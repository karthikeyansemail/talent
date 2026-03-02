/**
 * Claude Code Hook: PostToolUse (Write/Edit)
 * Async notification when files are modified.
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
        return; // No input
    }

    const toolName = data.tool_name || data.name || 'unknown';
    const filePath = data.input?.file_path || data.input?.path || 'unknown file';
    const fileName = filePath.split(/[/\\]/).pop();

    await postEvent('/api/events', {
        type: 'file_change',
        summary: `File ${toolName === 'Write' ? 'created' : 'edited'}: \`${fileName}\``,
        data: { tool: toolName, file: filePath },
    });
}

main().catch(console.error);
