import express from 'express';
import { config } from './config.mjs';
import { initBot } from './telegram-bot.mjs';
import { startTaskRunner } from './task-runner.mjs';
import eventsRouter from './routes/events.mjs';
import testsRouter from './routes/tests.mjs';
import screenshotsRouter from './routes/screenshots.mjs';
import instructionsRouter from './routes/instructions.mjs';
import statusRouter from './routes/status.mjs';

const app = express();

// Middleware
app.use(express.json({ limit: '1mb' }));

// Routes
app.use('/api/events', eventsRouter);
app.use('/api/test', testsRouter);
app.use('/api/screenshot', screenshotsRouter);
app.use('/api/instruct', instructionsRouter);
app.use('/api/status', statusRouter);
app.get('/api/status/history', statusRouter);

// Health check
app.get('/', (req, res) => {
    res.json({ service: 'Talent Automation Bridge', status: 'running' });
});

// Start server
const { host, port } = config.bridge;
app.listen(port, host, () => {
    console.log(`[Bridge] Server running at http://${host}:${port}`);
    console.log(`[Bridge] API endpoints:`);
    console.log(`  POST /api/events        — Submit hook events`);
    console.log(`  GET  /api/events/:id     — Poll event status`);
    console.log(`  POST /api/screenshot     — Capture page screenshot`);
    console.log(`  POST /api/instruct       — Queue instruction`);
    console.log(`  GET  /api/status         — Bridge health`);

    // Initialize Telegram bot and task runner
    initBot();
    startTaskRunner();
});
