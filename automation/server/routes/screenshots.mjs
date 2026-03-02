import { Router } from 'express';
import { spawn } from 'child_process';
import { existsSync } from 'fs';
import { config } from '../config.mjs';
import { sendPhoto } from '../telegram-bot.mjs';

const router = Router();

// POST /api/screenshot — Capture a page screenshot
router.post('/', (req, res) => {
    const { page } = req.body;
    if (!page) {
        return res.status(400).json({ error: 'page is required' });
    }

    const proc = spawn('node', ['playwright/capture.mjs', page], {
        cwd: config.paths.root,
        shell: true,
    });

    let output = '';
    proc.stdout.on('data', (d) => output += d.toString());
    proc.stderr.on('data', (d) => output += d.toString());

    proc.on('close', (code) => {
        if (code === 0) {
            const screenshotPath = output.trim().split('\n').pop();
            if (existsSync(screenshotPath)) {
                sendPhoto(screenshotPath, `Screenshot: ${page}`);
                res.json({ status: 'ok', path: screenshotPath });
            } else {
                res.status(500).json({ error: 'Screenshot file not found', output });
            }
        } else {
            res.status(500).json({ error: 'Screenshot capture failed', output });
        }
    });
});

export default router;
