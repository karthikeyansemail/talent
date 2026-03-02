import { test, expect } from '@playwright/test';
import { loginAs } from '../helpers/auth.mjs';

test.describe('Candidates', () => {
    test('candidates index loads', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('candidates');
        await expect(page.locator('body')).toContainText('Candidates');
        await expect(page.locator('a:has-text("New Candidate")')).toBeVisible();
        await expect(page.locator('body')).toContainText('John Smith');
        await expect(page.locator('body')).toContainText('Emily Chen');
        await expect(page.locator('body')).toContainText('Priya Patel');
    });

    test('candidates can be searched', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('candidates');
        await page.fill('input[name="search"]', 'John');
        await page.click('button:has-text("Filter")');
        await expect(page.locator('body')).toContainText('John Smith');
        await expect(page.locator('body')).not.toContainText('Emily Chen');
    });

    test('create candidate page loads', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('candidates/create');
        await expect(page.locator('body')).toContainText('Add Candidate');
        await expect(page.locator('input[name="first_name"]')).toBeVisible();
        await expect(page.locator('input[name="last_name"]')).toBeVisible();
        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('input[name="phone"]')).toBeVisible();
        await expect(page.locator('select[name="source"]')).toBeVisible();
    });

    test('can create candidate manually', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('candidates/create');
        await page.fill('input[name="first_name"]', 'Playwright');
        await page.fill('input[name="last_name"]', 'Tester');
        await page.fill('input[name="email"]', `pw.tester.${Date.now()}@example.com`);
        await page.fill('input[name="phone"]', '555-9999');
        await page.fill('input[name="current_company"]', 'Test Corp');
        await page.fill('input[name="current_title"]', 'QA Lead');
        await page.fill('input[name="experience_years"]', '5');
        await page.selectOption('select[name="source"]', 'direct');
        await page.fill('textarea[name="notes"]', 'Created by Playwright automated testing');
        await page.click('button:has-text("Create Candidate")');
        await expect(page.locator('body')).toContainText('Playwright Tester', { timeout: 10000 });
    });

    test('can view candidate details', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('candidates');
        await page.click('a:has-text("John Smith")');
        await expect(page.locator('body')).toContainText('John Smith');
        await expect(page.locator('body')).toContainText('Google');
        await expect(page.locator('body')).toContainText('Senior Developer');
    });

    test('can edit candidate', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('candidates');
        await page.click('a:has-text("Emily Chen")');
        await page.click('a:has-text("Edit")');
        await expect(page.locator('body')).toContainText('Edit Candidate');
        await page.fill('input[name="phone"]', '555-9999');
        await page.click('button:has-text("Update Candidate")');
        await expect(page.locator('body')).toContainText('Emily Chen', { timeout: 10000 });
        await expect(page.locator('body')).toContainText('555-9999');
    });

    test('candidate show displays resumes', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('candidates');
        await page.click('a:has-text("John Smith")');
        await expect(page.locator('body')).toContainText('Resumes');
    });

    test('candidate show displays applications', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('candidates');
        await page.click('a:has-text("John Smith")');
        await expect(page.locator('body')).toContainText('Applications');
        await expect(page.locator('body')).toContainText('Senior Backend Developer');
    });
});
