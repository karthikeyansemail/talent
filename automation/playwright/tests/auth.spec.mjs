import { test, expect } from '@playwright/test';
import { loginAs, logout } from '../helpers/auth.mjs';

test.describe('Authentication', () => {
    test('login page loads', async ({ page }) => {
        await logout(page);
        await page.goto('login');
        await expect(page.locator('body')).toContainText('Welcome back');
        await expect(page.locator('body')).toContainText('Sign in');
        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
    });

    test('admin can login', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await expect(page).toHaveURL(/\/dashboard/);
        await expect(page.locator('body')).toContainText('Dashboard');
    });

    test('HR manager can login', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await expect(page).toHaveURL(/\/dashboard/);
        await expect(page.locator('body')).toContainText('Dashboard');
    });

    test('resource manager can login', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await expect(page).toHaveURL(/\/dashboard/);
        await expect(page.locator('body')).toContainText('Dashboard');
    });

    test('login fails with wrong password', async ({ page }) => {
        await logout(page);
        await page.goto('login');
        await page.fill('input[name="email"]', 'admin@acme.com');
        await page.fill('input[name="password"]', 'wrongpassword');
        await page.click('button:has-text("Sign in")');
        await expect(page.locator('body')).toContainText('Invalid credentials');
    });

    test('login fails with nonexistent email', async ({ page }) => {
        await logout(page);
        await page.goto('login');
        await page.fill('input[name="email"]', 'nobody@nowhere.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button:has-text("Sign in")');
        await expect(page.locator('body')).toContainText('Invalid credentials');
    });

    test('user can logout', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await expect(page).toHaveURL(/\/dashboard/);
        await page.evaluate(() => document.querySelector('form[action*="logout"]').submit());
        await page.waitForURL('**/login', { timeout: 10000 });
        await expect(page).toHaveURL(/\/login/);
    });

    test('register page loads', async ({ page }) => {
        await logout(page);
        await page.goto('register');
        await expect(page.locator('input[name="name"]')).toBeVisible();
        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
        await expect(page.locator('input[name="org_name"]')).toBeVisible();
    });

    test('unauthenticated user redirected to login', async ({ page }) => {
        await logout(page);
        await page.goto('dashboard');
        await expect(page).toHaveURL(/\/login/);
    });
});
