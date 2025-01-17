import { defineConfig } from '@playwright/test'

export default defineConfig({
    testDir: 'tests/playwright',

    // Fail the build on CI if you accidentally left test.only in the source code.
    forbidOnly: !!process.env.CI,

    // Retry on CI only.
    retries: process.env.CI ? 0 : 0,

    // Opt out of parallel tests on CI.
    workers: process.env.CI ? 1 : undefined,

    // See: https://playwright.dev/docs/api/class-testoptions
    use: {
        // Base URL to use in actions like `await page.goto('/')`.
        baseURL: process.env.E2E_URL,

        // Only care about chromium
        browserName: 'chromium',

        // Collect trace when retrying the failed test.
        trace: 'on',

        // Whether to automatically capture a screenshot after each test. Defaults to 'off'.
        screenshot: 'off',
    },
})
