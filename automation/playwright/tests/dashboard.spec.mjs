import { test, expect } from '@playwright/test';
import { loginAs } from '../helpers/auth.mjs';

test.describe('Dashboard', () => {
    test('dashboard loads for admin', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await expect(page).toHaveURL(/\/dashboard/);
        await expect(page.locator('body')).toContainText('Dashboard');
        await expect(page.locator('body')).toContainText('Total Jobs');
        await expect(page.locator('body')).toContainText('Total Candidates');
        await expect(page.locator('body')).toContainText('Applications');
        await expect(page.locator('body')).toContainText('Employees');
        await expect(page.locator('body')).toContainText('Projects');
    });

    test('dashboard shows stat cards', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await expect(page.locator('.stat-card').first()).toBeVisible();
        await expect(page.locator('body')).toContainText('Hiring Pipeline');
    });

    test('dashboard shows recent applications', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await expect(page.locator('body')).toContainText('Recent Applications');
    });

    test('sidebar navigation links present', async ({ page }) => {
        await loginAs(page, 'admin@acme.com');
        await expect(page.locator('a:has-text("Dashboard")')).toBeVisible();
        await expect(page.locator('a:has-text("Candidates")')).toBeVisible();
        await expect(page.locator('a:has-text("Employees")')).toBeVisible();
        await expect(page.locator('a:has-text("Projects")')).toBeVisible();
    });
});
