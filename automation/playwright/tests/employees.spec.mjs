import { test, expect } from '@playwright/test';
import { loginAs } from '../helpers/auth.mjs';

test.describe('Employees', () => {
    test('employees index loads', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('employees');
        await expect(page.locator('body')).toContainText('Employees');
        await expect(page.locator('a:has-text("New Employee")')).toBeVisible();
        await expect(page.locator('body')).toContainText('Alice Wang');
        await expect(page.locator('body')).toContainText('Bob Martinez');
        await expect(page.locator('body')).toContainText('Carol Davis');
    });

    test('employees can be searched', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('employees');
        await page.fill('input[name="search"]', 'Alice');
        await page.click('button:has-text("Filter")');
        await expect(page.locator('body')).toContainText('Alice Wang');
        await expect(page.locator('body')).not.toContainText('Bob Martinez');
    });

    test('employees can be filtered by department', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('employees');
        await expect(page.locator('select[name="department_id"]')).toBeVisible();
    });

    test('create employee page loads', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('employees/create');
        await expect(page.locator('body')).toContainText('Add Employee');
        await expect(page.locator('input[name="first_name"]')).toBeVisible();
        await expect(page.locator('input[name="last_name"]')).toBeVisible();
        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('input[name="designation"]')).toBeVisible();
        await expect(page.locator('select[name="department_id"]')).toBeVisible();
    });

    test('can create employee', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('employees/create');
        await page.fill('input[name="first_name"]', 'Playwright');
        await page.fill('input[name="last_name"]', 'Employee');
        await page.fill('input[name="email"]', `pw.employee.${Date.now()}@acme.com`);
        await page.fill('input[name="designation"]', 'Test Engineer');
        await page.click('button:has-text("Create Employee")');
        await expect(page.locator('body')).toContainText('Playwright Employee', { timeout: 10000 });
    });

    test('can view employee details', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('employees');
        await page.click('a:has-text("Alice Wang")');
        await expect(page.locator('body')).toContainText('Alice Wang', { timeout: 10000 });
        await expect(page.locator('body')).toContainText('Engineering');
    });

    test('can edit employee', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('employees');
        await page.click('a:has-text("Alice Wang")');
        await page.waitForSelector('text=Alice Wang', { timeout: 10000 });
        await page.click('a:has-text("Edit")');
        await expect(page.locator('body')).toContainText('Edit Employee');

        await page.fill('input[name="designation"]', 'Staff Engineer');
        await page.click('button:has-text("Update Employee")');
        await expect(page.locator('body')).toContainText('Staff Engineer', { timeout: 10000 });

        // Restore
        await page.click('a:has-text("Edit")');
        await page.fill('input[name="designation"]', 'Senior Engineer');
        await page.click('button:has-text("Update Employee")');
        await expect(page.locator('body')).toContainText('Senior Engineer', { timeout: 10000 });
    });

    test('import page loads', async ({ page }) => {
        await loginAs(page, 'rm@acme.com');
        await page.goto('employees/import');
        await expect(page.locator('body')).toContainText('Import Employees');
    });

    test('HR manager cannot access employees', async ({ page }) => {
        await loginAs(page, 'hr@acme.com');
        await page.goto('employees');
        await expect(page.locator('a:has-text("New Employee")')).not.toBeVisible();
    });
});
