import { readFileSync, existsSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';
import http from 'http';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..', '..');

// Load config from .env
function loadConfig() {
    const envPath = resolve(ROOT, '.env');
    const env = {};
    if (existsSync(envPath)) {
        const lines = readFileSync(envPath, 'utf-8').split('\n');
        for (const line of lines) {
            const trimmed = line.trim();
            if (!trimmed || trimmed.startsWith('#')) continue;
            const eqIdx = trimmed.indexOf('=');
            if (eqIdx === -1) continue;
            env[trimmed.slice(0, eqIdx).trim()] = trimmed.slice(eqIdx + 1).trim();
        }
    }
    return {
        host: env.BRIDGE_HOST || '127.0.0.1',
        port: parseInt(env.BRIDGE_PORT || '3377', 10),
    };
}

const bridgeConfig = loadConfig();

/**
 * POST JSON to the bridge server.
 */
export function postEvent(path, data) {
    return new Promise((resolve, reject) => {
        const body = JSON.stringify(data);
        const req = http.request({
            hostname: bridgeConfig.host,
            port: bridgeConfig.port,
            path,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Content-Length': Buffer.byteLength(body),
            },
            timeout: 5000,
        }, (res) => {
            let data = '';
            res.on('data', (chunk) => data += chunk);
            res.on('end', () => {
                try {
                    resolve(JSON.parse(data));
                } catch {
                    resolve({ raw: data });
                }
            });
        });

        req.on('error', (err) => {
            // Bridge might not be running — that's OK, hook should still work
            console.error(`[Bridge] Connection failed: ${err.message}`);
            resolve(null);
        });

        req.on('timeout', () => {
            req.destroy();
            resolve(null);
        });

        req.write(body);
        req.end();
    });
}

/**
 * GET from the bridge server.
 */
export function getStatus(path) {
    return new Promise((resolve, reject) => {
        const req = http.request({
            hostname: bridgeConfig.host,
            port: bridgeConfig.port,
            path,
            method: 'GET',
            timeout: 3000,
        }, (res) => {
            let data = '';
            res.on('data', (chunk) => data += chunk);
            res.on('end', () => {
                try {
                    resolve(JSON.parse(data));
                } catch {
                    resolve({ raw: data });
                }
            });
        });

        req.on('error', () => resolve(null));
        req.on('timeout', () => { req.destroy(); resolve(null); });
        req.end();
    });
}

/**
 * Poll for event response (used by stop hook).
 * Checks the response file at .bridge/responses/{eventId}.json
 */
export function pollResponseFile(eventId, intervalMs = 2000, timeoutMs = 300000) {
    const responsePath = resolve(ROOT, '.bridge', 'responses', `${eventId}.json`);

    return new Promise((resolve) => {
        const startTime = Date.now();

        const timer = setInterval(() => {
            // Check timeout
            if (Date.now() - startTime > timeoutMs) {
                clearInterval(timer);
                resolve({ decision: 'allow', reason: 'Timeout — auto-approved after 5 minutes' });
                return;
            }

            // Check for response file
            if (existsSync(responsePath)) {
                clearInterval(timer);
                try {
                    const content = readFileSync(responsePath, 'utf-8');
                    resolve(JSON.parse(content));
                } catch {
                    resolve({ decision: 'allow', reason: 'Could not parse response file' });
                }
            }
        }, intervalMs);
    });
}
