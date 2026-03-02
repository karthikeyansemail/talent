/**
 * Auth helpers for Playwright tests.
 * Mirrors the DuskTestCase loginAs()/logout() pattern.
 */

const TEST_EMAIL = process.env.TEST_EMAIL || 'admin@acme.com';
const TEST_PASSWORD = process.env.TEST_PASSWORD || 'password';

/**
 * Login as a specific user by email.
 * @param {import('@playwright/test').Page} page
 * @param {string} email
 * @param {string} password
 */
export async function loginAs(page, email = TEST_EMAIL, password = TEST_PASSWORD) {
    // Clear cookies to ensure clean state
    await page.context().clearCookies();

    await page.goto('login');
    await page.waitForSelector('input[name="email"]', { timeout: 10000 });
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('button:has-text("Sign in")');
    await page.waitForURL('**/dashboard', { timeout: 15000 });
}

/**
 * Logout the current user.
 * @param {import('@playwright/test').Page} page
 */
export async function logout(page) {
    await page.context().clearCookies();
    await page.goto('login');
}
