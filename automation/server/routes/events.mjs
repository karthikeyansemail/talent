import { Router } from 'express';
import { v4 as uuid } from 'uuid';
import { writeFileSync } from 'fs';
import { resolve } from 'path';
import { queries } from '../db.mjs';
import { config } from '../config.mjs';
import { sendApprovalMessage, sendNotification } from '../telegram-bot.mjs';

const router = Router();

// POST /api/events — Hook scripts submit events here
router.post('/', (req, res) => {
    const { type, summary, data } = req.body;
    if (!type) {
        return res.status(400).json({ error: 'type is required' });
    }

    const id = uuid();
    queries.insertEvent(id, type, summary || '', data || {});

    const mode = queries.getApprovalMode();

    if (type === 'stop' || type === 'plan_approval') {
        if (mode === 'approve') {
            // Approval mode: send with buttons, wait for user response
            sendApprovalMessage(id, summary || 'Claude Code stopped — awaiting approval', type);
        } else {
            // Notify mode: send summary, auto-approve immediately
            const icon = type === 'stop' ? '✅' : 'ℹ️';
            sendNotification(`${icon} *Claude Code — Task Completed*\n\n${summary || 'No summary'}\n\n_Auto-approved (notify mode). Reply with /instruct <text> to send a new task._`);

            // Auto-approve: write response file + update store
            queries.updateEventResponse(id, 'approved', 'Auto-approved (notify mode)', null);
            const responseFile = resolve(config.paths.responses, `${id}.json`);
            writeFileSync(responseFile, JSON.stringify({
                decision: 'allow',
                reason: 'Auto-approved (notify mode)',
                timestamp: new Date().toISOString(),
            }));
        }
    }

    res.json({ id, status: mode === 'approve' ? 'pending' : 'approved' });
});

// GET /api/events/:id/status — Hook scripts poll this for responses
router.get('/:id/status', (req, res) => {
    const evt = queries.getEvent(req.params.id);
    if (!evt) {
        return res.status(404).json({ error: 'Event not found' });
    }
    res.json({
        id: evt.id,
        status: evt.status,
        response: evt.response,
        respondedAt: evt.responded_at,
    });
});

export default router;
