import { test, expect } from '@playwright/test';
import { loginAs } from '../helpers/auth.mjs';

test.describe('Projects', () => {
    test('projects index loads', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('projects');
        await expect(page.locator('body')).toContainText('Projects');
        await expect(page.locator('a:has-text("New Project")')).toBeVisible();
        await expect(page.locator('body')).toContainText('Customer Portal Redesign');
        await expect(page.locator('body')).toContainText('ML Recommendation Engine');
    });

    test('projects can be filtered by status', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('projects');
        await page.selectOption('select[name="status"]', 'planning');
        await page.waitForTimeout(2000);
        await expect(page.locator('body')).toContainText('Customer Portal Redesign');
    });

    test('create project page loads', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('projects/create');
        await expect(page.locator('body')).toContainText('Create Project');
        await expect(page.locator('input[name="name"]')).toBeVisible();
        await expect(page.locator('textarea[name="description"]')).toBeVisible();
        await expect(page.locator('select[name="complexity_level"]')).toBeVisible();
        await expect(page.locator('select[name="status"]')).toBeVisible();
        await expect(page.locator('input[name="start_date"]')).toBeVisible();
        await expect(page.locator('input[name="end_date"]')).toBeVisible();
    });

    test('can create project', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('projects/create');
        await page.fill('input[name="name"]', 'Playwright Test Project');
        await page.fill('textarea[name="description"]', 'A project created by Playwright automated testing');
        await page.selectOption('select[name="complexity_level"]', 'medium');
        await page.selectOption('select[name="status"]', 'planning');
        await page.fill('textarea[name="domain_context"]', 'Internal testing framework');

        // Set date inputs via JS (more reliable for HTML date inputs)
        await page.evaluate(() => {
            document.querySelector('input[name="start_date"]').value = '2026-03-01';
            document.querySelector('input[name="end_date"]').value = '2026-06-30';
        });

        await page.click('button:has-text("Create Project")');
        await expect(page.locator('body')).toContainText('Playwright Test Project', { timeout: 15000 });
    });

    test('can view project details', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('projects');
        await page.click('a:has-text("Customer Portal Redesign")');
        await expect(page.locator('body')).toContainText('Customer Portal Redesign');
        await expect(page.locator('body')).toContainText('Project Details');
        await expect(page.locator('body')).toContainText('Requirements');
    });

    test('can edit project', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('projects');
        await page.click('a:has-text("Customer Portal Redesign")');
        await page.click('a:has-text("Edit Project")');
        await page.waitForSelector('input[name="name"]', { timeout: 10000 });
        await expect(page.locator('body')).toContainText('Edit Project');

        await page.selectOption('select[name="complexity_level"]', 'critical');
        await page.click('button:has-text("Update Project")');
        await expect(page.locator('body')).toContainText('Critical', { timeout: 10000 });

        // Restore
        await page.click('a:has-text("Edit Project")');
        await page.waitForSelector('input[name="name"]', { timeout: 10000 });
        await page.selectOption('select[name="complexity_level"]', 'high');
        await page.click('button:has-text("Update Project")');
        await page.waitForTimeout(2000);
    });

    test('project show has find resources button', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('projects');
        await page.click('a:has-text("Customer Portal Redesign")');
        await expect(page.locator('body')).toContainText('Find Best Resources');
    });
});
