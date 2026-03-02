import { test, expect } from '@playwright/test';
import { loginAs } from '../helpers/auth.mjs';

/**
 * Navigate to the first application's detail page.
 */
async function navigateToFirstApplication(page) {
    await loginAs(page, 'hr@acme.com');
    await page.goto('jobs');
    await page.click('a:has-text("Senior Backend Developer")');
    await expect(page.locator('body')).toContainText('Applications', { timeout: 10000 });

    // Click the first "View" link — use JS click since the table may require scrolling
    await page.waitForSelector('.table-actions a[href*="applications"]', { state: 'attached', timeout: 10000 });
    await page.evaluate(() => document.querySelector('.table-actions a[href*="applications"]').click());
    await expect(page.locator('body')).toContainText('Application Details', { timeout: 15000 });
}

test.describe('Applications', () => {
    test('application show page loads', async ({ page }) => {
        await navigateToFirstApplication(page);
        await expect(page.locator('body')).toContainText('Application Details');
        await expect(page.locator('body')).toContainText('Application Info');
    });

    test('application shows candidate info', async ({ page }) => {
        await navigateToFirstApplication(page);
        await expect(page.locator('body')).toContainText('View Candidate');
        await expect(page.locator('body')).toContainText('View Job');
    });

    test('can update application stage', async ({ page }) => {
        await navigateToFirstApplication(page);
        await page.selectOption('select[name="stage"]', 'hr_screening');
        await page.click('button:has-text("Update")');
        await expect(page.locator('body')).toContainText('updated', { timeout: 10000 });
    });

    test('feedback modal opens', async ({ page }) => {
        await navigateToFirstApplication(page);
        await expect(page.locator('body')).toContainText('Interview Feedback');
        await page.click('button:has-text("Add Feedback")');
        await page.waitForSelector('#feedbackModal.active', { timeout: 5000 });
        await expect(page.locator('#feedbackModal')).toContainText('Submit Feedback');
        await expect(page.locator('#feedbackModal select[name="recommendation"]')).toBeVisible();
    });

    test('can submit feedback', async ({ page }) => {
        await navigateToFirstApplication(page);
        await page.click('button:has-text("Add Feedback")');
        await page.waitForSelector('#feedbackModal.active', { timeout: 5000 });

        // Fill in modal fields
        await page.selectOption('#feedbackModal select[name="stage"]', 'hr_screening');
        await page.click('#feedbackStarPicker button[data-value="4"]');
        await page.waitForTimeout(300);

        // Set recommendation via JS for reliability
        await page.evaluate(() => {
            const sel = document.querySelector('#feedbackModal select[name="recommendation"]');
            sel.value = 'yes';
            sel.dispatchEvent(new Event('change'));
        });

        await page.fill('#feedbackModal textarea[name="strengths"]', 'Strong technical skills');
        await page.fill('#feedbackModal textarea[name="notes"]', 'Playwright automated test feedback');
        await page.click('#feedbackModal button:has-text("Submit Feedback")');
        await expect(page.locator('body')).toContainText('Playwright automated test feedback', { timeout: 10000 });
    });

    test('AI analysis button present', async ({ page }) => {
        await navigateToFirstApplication(page);
        await expect(page.locator('body')).toContainText('Run AI Analysis');
    });
});
