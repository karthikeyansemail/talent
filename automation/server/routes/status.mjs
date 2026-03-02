import { Router } from 'express';
import { queries } from '../db.mjs';
import { getRunnerStatus } from '../task-runner.mjs';

const router = Router();

// GET /api/status — Bridge health check
router.get('/', (req, res) => {
    const pending = queries.getPendingEvents();
    const lastTest = queries.getLastTestRun();
    const runner = getRunnerStatus();

    res.json({
        status: 'running',
        mode: queries.getApprovalMode(),
        pendingApprovals: pending.length,
        taskRunner: runner,
        lastTestRun: lastTest ? {
            id: lastTest.id,
            status: lastTest.status,
            passed: lastTest.passed,
            total: lastTest.total,
        } : null,
        timestamp: new Date().toISOString(),
    });
});

// GET /api/history — Recent events
router.get('/history', (req, res) => {
    const limit = parseInt(req.query.limit || '20', 10);
    const events = queries.getRecentEvents(limit);
    res.json(events);
});

export default router;
