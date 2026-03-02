import { Router } from 'express';
import { queries } from '../db.mjs';

const router = Router();

// POST /api/instruct — Queue a new instruction
router.post('/', (req, res) => {
    const { text, source } = req.body;
    if (!text) {
        return res.status(400).json({ error: 'text is required' });
    }
    queries.insertInstruction(text, source || 'api');
    res.json({ status: 'queued' });
});

// GET /api/instruct/pending — Get unconsumed instructions
router.get('/pending', (req, res) => {
    const instructions = queries.getUnconsumedInstructions();
    res.json(instructions);
});

// POST /api/instruct/:id/consume — Mark instruction as consumed
router.post('/:id/consume', (req, res) => {
    queries.markInstructionConsumed(req.params.id);
    res.json({ status: 'consumed' });
});

export default router;
