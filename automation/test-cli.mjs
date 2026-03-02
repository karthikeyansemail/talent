import { spawn } from 'child_process';
import { writeFileSync, appendFileSync } from 'fs';

const LOG = 'c:\\xampp\\htdocs\\talent\\automation\\.bridge\\cli-test.log';
const claudePath = 'C:\\Users\\DELL\\.vscode\\extensions\\anthropic.claude-code-2.1.45-win32-x64\\resources\\native-binary\\claude.exe';

writeFileSync(LOG, '');
function log(msg) {
    const line = `${msg}\n`;
    process.stdout.write(line);
    appendFileSync(LOG, line);
}

const cleanEnv = { ...process.env };
for (const key of Object.keys(cleanEnv)) {
    if (/^CLAUDE/i.test(key)) delete cleanEnv[key];
}

log('=== Testing with --dangerously-skip-permissions ===');
const proc = spawn(claudePath, [
    '-p', 'list all route files in this project',
    '--output-format', 'stream-json',
    '--verbose',
    '--dangerously-skip-permissions'
], {
    cwd: 'c:\\xampp\\htdocs\\talent',
    env: cleanEnv,
    stdio: ['pipe', 'pipe', 'pipe'],
});
proc.stdin.end();

log(`PID: ${proc.pid}`);

proc.stdout.on('data', (d) => {
    const lines = d.toString().split('\n').filter(l => l.trim());
    for (const line of lines) {
        try {
            const evt = JSON.parse(line);
            if (evt.type === 'assistant' && evt.message?.content) {
                for (const b of evt.message.content) {
                    if (b.type === 'tool_use') log(`ACTION: ${b.name} ${JSON.stringify(b.input).substring(0, 100)}`);
                    if (b.type === 'text') log(`TEXT: ${b.text.substring(0, 200)}`);
                }
            } else if (evt.type === 'result') {
                log(`RESULT: ${evt.result?.substring(0, 500)}`);
                log(`COST: $${evt.total_cost_usd}`);
            } else {
                log(`EVENT: ${evt.type} ${evt.subtype || ''}`);
            }
        } catch {
            log(`RAW: ${line.substring(0, 200)}`);
        }
    }
});

proc.stderr.on('data', (d) => log(`STDERR: ${d.toString().substring(0, 500)}`));
proc.on('close', (code) => { log(`EXIT: code=${code}`); process.exit(0); });
proc.on('error', (err) => { log(`ERROR: ${err.message}`); process.exit(1); });

setTimeout(() => { log('TIMEOUT 120s'); proc.kill(); process.exit(1); }, 120000);
