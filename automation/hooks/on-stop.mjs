/**
 * Claude Code Hook: Stop
 *
 * This is the CORE approval hook. When Claude finishes a task:
 * 1. Extracts a summary from the transcript
 * 2. POSTs to the bridge server
 * 3. Bridge sends Telegram message with Approve/Reject buttons
 * 4. Polls for response file (up to 5 minutes)
 * 5. Returns decision to Claude Code
 *
 * Output JSON: { "decision": "allow"|"block", "reason": "..." }
 */
import { postEvent, pollResponseFile } from './lib/bridge-client.mjs';
import { findLatestTranscript, extractSummary } from './lib/transcript-parser.mjs';

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

    // Extract summary from the transcript
    const transcriptPath = findLatestTranscript();
    const summary = extractSummary(transcriptPath);

    // Build the stop message from Claude's last output
    const stopReason = data.stopReason || 'Task completed';
    const fullSummary = `*Stop Reason:* ${stopReason}\n\n${summary}`;

    // POST to bridge server
    const result = await postEvent('/api/events', {
        type: 'stop',
        summary: fullSummary,
        data: { stopReason, transcriptPath },
    });

    if (!result || !result.id) {
        // Bridge not running — auto-approve
        const output = JSON.stringify({ decision: 'allow', reason: 'Bridge not running — auto-approved' });
        process.stdout.write(output);
        return;
    }

    // Poll for response (5 minutes timeout)
    const response = await pollResponseFile(result.id, 2000, 300000);

    const output = JSON.stringify({
        decision: response.decision || 'allow',
        reason: response.reason || 'No response received',
    });
    process.stdout.write(output);
}

main().catch((err) => {
    console.error(`[on-stop] Error: ${err.message}`);
    process.stdout.write(JSON.stringify({ decision: 'allow', reason: `Hook error: ${err.message}` }));
});
