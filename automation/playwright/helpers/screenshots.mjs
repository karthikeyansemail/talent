import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const SCREENSHOTS_DIR = resolve(__dirname, '..', 'screenshots');

/**
 * Capture a named screenshot.
 * @param {import('@playwright/test').Page} page
 * @param {string} name
 * @returns {Promise<string>} Path to the screenshot
 */
export async function captureScreenshot(page, name) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').substring(0, 19);
    const filename = `${name}-${timestamp}.png`;
    const path = resolve(SCREENSHOTS_DIR, filename);
    await page.screenshot({ path, fullPage: true });
    return path;
}
