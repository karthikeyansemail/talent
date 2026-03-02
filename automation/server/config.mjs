import { readFileSync, existsSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');

// Load .env file manually (no dotenv dependency)
const envPath = resolve(ROOT, '.env');
if (existsSync(envPath)) {
    const lines = readFileSync(envPath, 'utf-8').split('\n');
    for (const line of lines) {
        const trimmed = line.trim();
        if (!trimmed || trimmed.startsWith('#')) continue;
        const eqIdx = trimmed.indexOf('=');
        if (eqIdx === -1) continue;
        const key = trimmed.slice(0, eqIdx).trim();
        const val = trimmed.slice(eqIdx + 1).trim();
        if (!process.env[key]) {
            process.env[key] = val;
        }
    }
}

// Project root — the directory Claude CLI will work in
const PROJECT_ROOT = process.env.PROJECT_ROOT || resolve(ROOT, '..');

// Resolve Node.js path — needed because node isn't always in system PATH on Windows
const NODE_PATH = process.env.NODE_PATH_OVERRIDE || process.execPath; // use the node that's running us

export const config = {
    node: {
        path: NODE_PATH,
    },
    telegram: {
        token: process.env.TELEGRAM_BOT_TOKEN || '',
        chatId: process.env.TELEGRAM_CHAT_ID || '',
    },
    bridge: {
        port: parseInt(process.env.BRIDGE_PORT || '3377', 10),
        host: process.env.BRIDGE_HOST || '127.0.0.1',
    },
    app: {
        baseUrl: process.env.APP_BASE_URL || 'http://localhost',
    },
    test: {
        email: process.env.TEST_EMAIL || 'admin@acme.com',
        password: process.env.TEST_PASSWORD || 'password',
    },
    claude: {
        cliPath: process.env.CLAUDE_CLI_PATH || 'claude',
        projectRoot: PROJECT_ROOT,
        taskTimeout: parseInt(process.env.CLAUDE_TASK_TIMEOUT || '600000', 10), // 10 min default
    },
    paths: {
        root: ROOT,
        project: PROJECT_ROOT,
        bridge: resolve(ROOT, '.bridge'),
        responses: resolve(ROOT, '.bridge', 'responses'),
        db: resolve(ROOT, '.bridge', 'bridge.db'),
        screenshots: resolve(ROOT, 'playwright', 'screenshots'),
    },
};
