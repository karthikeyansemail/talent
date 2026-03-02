import { test, expect } from '@playwright/test';
import { loginAs } from '../helpers/auth.mjs';

test.describe('Settings', () => {
    // --- Organization Settings ---
    test('organization settings loads', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await page.goto('settings/organization');
        await expect(page.locator('body')).toContainText('Organization Settings');
        await expect(page.locator('input[name="name"]')).toBeVisible();
    });

    test('can update organization name', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await page.goto('settings/organization');
        await page.fill('input[name="name"]', 'Acme Technologies Updated');
        await page.click('button:has-text("Save Changes")');
        await page.waitForTimeout(2000);
        await expect(page.locator('input[name="name"]')).toHaveValue('Acme Technologies Updated');

        // Restore
        await page.fill('input[name="name"]', 'Acme Technologies');
        await page.click('button:has-text("Save Changes")');
        await page.waitForTimeout(2000);
    });

    // --- User Management ---
    test('users index loads', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await page.goto('settings/users');
        await expect(page.locator('body')).toContainText('Users');
        await expect(page.locator('a:has-text("Add User")')).toBeVisible();
        await expect(page.locator('body')).toContainText('admin@acme.com');
        await expect(page.locator('body')).toContainText('hr@acme.com');
        await expect(page.locator('body')).toContainText('rm@acme.com');
    });

    test('create user page loads', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await page.goto('settings/users/create');
        await expect(page.locator('body')).toContainText('Add User');
        await expect(page.locator('input[name="name"]')).toBeVisible();
        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
        await expect(page.locator('select[name="role"]')).toBeVisible();
    });

    test('can create new user', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await page.goto('settings/users/create');
        await page.fill('input[name="name"]', 'Playwright Test User');
        await page.fill('input[name="email"]', `pw.user.${Date.now()}@acme.com`);
        await page.fill('input[name="password"]', 'password123');
        await page.selectOption('select[name="role"]', 'employee');
        await page.click('button:has-text("Create User")');
        await expect(page.locator('body')).toContainText('Playwright Test User', { timeout: 10000 });
    });

    // --- LLM Configuration ---
    test('LLM config page loads', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await page.goto('settings/llm');
        await expect(page.locator('body')).toContainText('LLM Configuration');
    });

    // --- Scoring Rules ---
    test('scoring rules page loads', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await page.goto('settings/scoring-rules');
        await expect(page.locator('body')).toContainText('Scoring Rules');
        await expect(page.locator('body')).toContainText('Signal Weights');
        await expect(page.locator('body')).toContainText('Core Signals');
        await expect(page.locator('body')).toContainText('Skill Match');
    });

    test('scoring rules shows version history', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await page.goto('settings/scoring-rules');
        await expect(page.locator('body')).toContainText('Version History');
    });

    // --- Integrations ---
    test('integrations page loads', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await page.goto('settings/integrations');
        await expect(page.locator('body')).toContainText('Integrations');
    });

    // --- Access Control ---
    test('HR manager cannot access settings', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('settings/organization');
        await expect(page.locator('body')).toContainText('Unauthorized access');
    });

    test('resource manager cannot access settings', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('settings/organization');
        await expect(page.locator('body')).toContainText('Unauthorized access');
    });
});
