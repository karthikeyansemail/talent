import { readFileSync, existsSync, readdirSync, statSync } from 'fs';
import { resolve, join } from 'path';

/**
 * Find the most recent Claude Code transcript JSONL file.
 * Claude Code stores transcripts in ~/.claude/projects/<project>/
 */
export function findLatestTranscript() {
    const home = process.env.USERPROFILE || process.env.HOME;
    const claudeProjectsDir = resolve(home, '.claude', 'projects');

    if (!existsSync(claudeProjectsDir)) return null;

    // Find most recent JSONL file across all project dirs
    let latestFile = null;
    let latestTime = 0;

    try {
        const dirs = readdirSync(claudeProjectsDir);
        for (const dir of dirs) {
            const dirPath = join(claudeProjectsDir, dir);
            if (!statSync(dirPath).isDirectory()) continue;

            const files = readdirSync(dirPath).filter(f => f.endsWith('.jsonl'));
            for (const file of files) {
                const filePath = join(dirPath, file);
                const mtime = statSync(filePath).mtimeMs;
                if (mtime > latestTime) {
                    latestTime = mtime;
                    latestFile = filePath;
                }
            }
        }
    } catch {
        return null;
    }

    return latestFile;
}

/**
 * Parse a JSONL transcript and extract a summary of recent actions.
 * Returns a markdown-formatted summary string.
 */
export function extractSummary(transcriptPath, maxLines = 50) {
    if (!transcriptPath || !existsSync(transcriptPath)) {
        return 'No transcript available.';
    }

    try {
        const content = readFileSync(transcriptPath, 'utf-8');
        const lines = content.trim().split('\n').slice(-maxLines);
        const entries = [];

        for (const line of lines) {
            try {
                const entry = JSON.parse(line);
                entries.push(entry);
            } catch {
                // Skip unparseable lines
            }
        }

        // Extract key actions
        const actions = [];
        const filesModified = new Set();

        for (const entry of entries) {
            // Tool use entries
            if (entry.type === 'tool_use' || entry.tool_name) {
                const tool = entry.tool_name || entry.name || 'unknown';
                if (tool === 'Write' || tool === 'Edit') {
                    const path = entry.input?.file_path || entry.input?.path || '';
                    if (path) filesModified.add(path.split(/[/\\]/).pop());
                }
                if (tool === 'Bash') {
                    const cmd = entry.input?.command || '';
                    if (cmd && !cmd.startsWith('echo')) {
                        actions.push(`Ran: \`${cmd.substring(0, 80)}\``);
                    }
                }
            }

            // Assistant text
            if (entry.type === 'assistant' && entry.content) {
                const text = typeof entry.content === 'string' ? entry.content :
                    Array.isArray(entry.content) ? entry.content.filter(b => b.type === 'text').map(b => b.text).join(' ') : '';
                if (text.length > 20) {
                    // Get the last meaningful text block
                    actions.push(text.substring(0, 200));
                }
            }
        }

        let summary = '';
        if (filesModified.size > 0) {
            summary += `*Files modified:* ${[...filesModified].join(', ')}\n\n`;
        }
        if (actions.length > 0) {
            summary += `*Recent actions:*\n${actions.slice(-5).map(a => `- ${a}`).join('\n')}`;
        }

        return summary || 'Session activity detected (no details extracted).';
    } catch (err) {
        return `Error parsing transcript: ${err.message}`;
    }
}
