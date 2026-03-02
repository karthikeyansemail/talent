import { test, expect } from '@playwright/test';
import { loginAs } from '../helpers/auth.mjs';

test.describe('Hiring Reports', () => {
    test('hiring reports page loads', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('hiring/reports');
        await expect(page.locator('body')).toContainText('Hiring Reports');
    });
});
