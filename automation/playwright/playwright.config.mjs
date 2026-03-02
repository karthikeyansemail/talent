import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests',
    timeout: 30000,
    expect: { timeout: 10000 },
    fullyParallel: false,
    retries: 0,
    workers: 1,
    reporter: [
        ['list'],
        ['json', { outputFile: '../.bridge/test-results.json' }],
    ],
    use: {
        baseURL: (process.env.APP_BASE_URL || 'http://localhost/talent/public').replace(/\/?$/, '/'),
        headless: true,
        viewport: { width: 1920, height: 1080 },
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
        actionTimeout: 10000,
        navigationTimeout: 15000,
    },
    projects: [
        {
            name: 'chromium',
            use: { browserName: 'chromium' },
        },
    ],
    outputDir: '../.bridge/test-results',
});
