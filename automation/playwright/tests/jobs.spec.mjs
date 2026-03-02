import { test, expect } from '@playwright/test';
import { loginAs } from '../helpers/auth.mjs';

test.describe('Jobs', () => {
    test('jobs index loads', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('jobs');
        await expect(page.locator('body')).toContainText('Job Postings');
        await expect(page.locator('a:has-text("New Job")')).toBeVisible();
        await expect(page.locator('body')).toContainText('Senior Backend Developer');
        await expect(page.locator('body')).toContainText('React Frontend Developer');
        await expect(page.locator('body')).toContainText('ML Engineer');
    });

    test('jobs can be filtered by status', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('jobs');
        await page.selectOption('select[name="status"]', 'open');
        await page.waitForTimeout(2000);
        await expect(page.locator('body')).toContainText('Senior Backend Developer');
    });

    test('create job page loads', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('jobs/create');
        await expect(page.locator('body')).toContainText('Create Job Posting');
        await expect(page.locator('input[name="title"]')).toBeVisible();
        await expect(page.locator('select[name="department_id"]')).toBeVisible();
        await expect(page.locator('textarea[name="description"]')).toBeVisible();
    });

    test('can create a new job', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('jobs/create');
        await page.fill('input[name="title"]', 'Playwright Test - QA Engineer');
        await page.fill('textarea[name="description"]', 'This is a test job created by Playwright automated testing.');
        await page.fill('textarea[name="requirements"]', 'Testing experience, Selenium, PHP');
        await page.fill('input[name="min_experience"]', '2');
        await page.fill('input[name="max_experience"]', '5');
        await page.selectOption('select[name="employment_type"]', 'full_time');
        await page.fill('input[name="location"]', 'Remote');
        await page.selectOption('select[name="status"]', 'draft');
        await page.click('button:has-text("Create Job")');
        await expect(page.locator('body')).toContainText('Playwright Test - QA Engineer', { timeout: 10000 });
    });

    test('can view job details', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('jobs');
        await page.click('a:has-text("Senior Backend Developer")');
        await expect(page.locator('body')).toContainText('Senior Backend Developer');
        await expect(page.locator('body')).toContainText('Description');
        await expect(page.locator('body')).toContainText('Applications');
    });

    test('can edit a job', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('jobs');
        await page.click('a:has-text("Senior Backend Developer")');
        await page.click('a:has-text("Edit Job")');
        await page.waitForSelector('input[name="title"]', { timeout: 10000 });
        await expect(page.locator('body')).toContainText('Edit Job');

        await page.fill('input[name="title"]', 'Senior Backend Developer (Updated)');
        await page.click('button:has-text("Update Job")');
        await expect(page.locator('body')).toContainText('Senior Backend Developer (Updated)', { timeout: 10000 });

        // Restore original title
        await page.click('a:has-text("Edit Job")');
        await page.fill('input[name="title"]', 'Senior Backend Developer');
        await page.click('button:has-text("Update Job")');
        await expect(page.locator('body')).toContainText('Senior Backend Developer', { timeout: 10000 });
    });

    test('job show displays applications', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('jobs');
        await page.click('a:has-text("Senior Backend Developer")');
        await expect(page.locator('body')).toContainText('Applications');
        await expect(page.locator('body')).toContainText('John Smith');
    });

    test('resource manager cannot access jobs', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('jobs');
        await expect(page.locator('body')).not.toContainText('Job Postings');
    });
});
