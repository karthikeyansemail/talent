/**
 * Standalone screenshot capture script.
 * Usage: node playwright/capture.mjs <page-name>
 *
 * Pages: dashboard, login, jobs, candidates, employees, projects,
 *        settings, users, integrations, llm, scoring-rules,
 *        hiring-reports, intelligence, platform-branding, organizations
 */
import { chromium } from '@playwright/test';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';
import { existsSync, readFileSync } from 'fs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');
const SCREENSHOTS_DIR = resolve(__dirname, 'screenshots');

// Load .env
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

const BASE_URL = env.APP_BASE_URL || process.env.APP_BASE_URL || 'http://localhost/talent/public';
const TEST_EMAIL = env.TEST_EMAIL || process.env.TEST_EMAIL || 'admin@acme.com';
const TEST_PASSWORD = env.TEST_PASSWORD || process.env.TEST_PASSWORD || 'password';

// Page routes map
const PAGE_ROUTES = {
    'dashboard': '/dashboard',
    'login': '/login',
    'jobs': '/jobs',
    'candidates': '/candidates',
    'employees': '/employees',
    'projects': '/projects',
    'settings': '/settings/organization',
    'users': '/settings/users',
    'integrations': '/settings/integrations',
    'llm': '/settings/llm',
    'scoring-rules': '/settings/scoring-rules',
    'hiring-reports': '/hiring/reports',
    'intelligence': '/intelligence',
    'platform-branding': '/settings/platform-branding',
    'organizations': '/settings/organizations',
};

const pageName = process.argv[2];
if (!pageName) {
    console.error('Usage: node playwright/capture.mjs <page-name|/route/path>');
    console.error('Available pages:', Object.keys(PAGE_ROUTES).join(', '));
    console.error('Or use a direct route: /jobs/1, /candidates/3, etc.');
    process.exit(1);
}

// Determine route: direct path (starts with /) or named page lookup
let route;
let screenshotLabel;

if (pageName.startsWith('/')) {
    route = pageName;
    screenshotLabel = pageName.replace(/^\//, '').replace(/\//g, '-');
} else {
    route = PAGE_ROUTES[pageName];
    screenshotLabel = pageName;
    if (!route) {
        console.error(`Unknown page: "${pageName}"`);
        console.error('Available pages:', Object.keys(PAGE_ROUTES).join(', '));
        console.error('Or use a direct route: /jobs/1, /candidates/3, etc.');
        process.exit(1);
    }
}

async function main() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
    const page = await context.newPage();

    try {
        // Login (skip for login page)
        if (pageName !== 'login') {
            await page.goto(`${BASE_URL}/login`);
            await page.waitForSelector('input[name="email"]', { timeout: 10000 });
            await page.fill('input[name="email"]', TEST_EMAIL);
            await page.fill('input[name="password"]', TEST_PASSWORD);
            await page.click('button:has-text("Sign in")');
            await page.waitForURL('**/dashboard', { timeout: 15000 });
        }

        // Navigate to the target page
        await page.goto(`${BASE_URL}${route}`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000); // Let animations settle

        // Capture screenshot
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-').substring(0, 19);
        const filename = `${screenshotLabel}-${timestamp}.png`;
        const screenshotPath = resolve(SCREENSHOTS_DIR, filename);
        await page.screenshot({ path: screenshotPath, fullPage: true });

        // Print path for bridge server to read
        console.log(screenshotPath);
    } catch (err) {
        console.error(`Error capturing screenshot: ${err.message}`);
        process.exit(1);
    } finally {
        await browser.close();
    }
}

main();
