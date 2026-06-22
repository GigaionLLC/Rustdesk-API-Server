import { defineConfig } from '@playwright/test';

// Full-stack E2E against a running server (php artisan serve or the Docker app).
export default defineConfig({
    testDir: './e2e',
    timeout: 30_000,
    fullyParallel: true,
    reporter: process.env.CI ? 'list' : 'line',
    use: {
        baseURL: process.env.E2E_BASE_URL || 'http://127.0.0.1:8088',
        headless: true,
        viewport: { width: 1440, height: 900 },
    },
});
