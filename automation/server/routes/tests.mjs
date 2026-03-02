import { Router } from 'express';
import { queries } from '../db.mjs';

const router = Router();

// GET /api/test/:id/status — Check test run status
router.get('/:id/status', (req, res) => {
    const run = queries.getTestRun(req.params.id);
    if (!run) {
        return res.status(404).json({ error: 'Test run not found' });
    }
    res.json({
        id: run.id,
        suite: run.suite,
        status: run.status,
        total: run.total,
        passed: run.passed,
        failed: run.failed,
        results: run.results ? JSON.parse(run.results) : null,
        startedAt: run.started_at,
        finishedAt: run.finished_at,
    });
});

// GET /api/test/last — Get last test run
router.get('/last', (req, res) => {
    const run = queries.getLastTestRun();
    if (!run) {
        return res.json({ message: 'No test runs yet' });
    }
    res.json({
        id: run.id,
        suite: run.suite,
        status: run.status,
        total: run.total,
        passed: run.passed,
        failed: run.failed,
        startedAt: run.started_at,
        finishedAt: run.finished_at,
    });
});

export default router;
