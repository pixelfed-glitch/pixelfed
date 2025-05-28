import { test, expect } from '@playwright/test'

test('page should load without errors', async ({ page }) => {
    await page.goto('/')
    await page.waitForLoadState('domcontentloaded')
    await page.waitForLoadState('networkidle')

    expect(page.locator('a', { hasText: 'Sign up' })).toBeVisible()
    expect(page.locator('a', { hasText: 'Login' })).toBeVisible()
    expect(page.locator('a', { hasText: 'Powered by Pixelfed' })).toBeVisible()
    expect(page.locator('a.admin-email', { hasText: 'github@example.com' })).toBeVisible()

    expect(page).toHaveTitle("docker-pixelfed e2e")
})

test('/api/nodeinfo/2.0.json', async ({ request }) => {
    const response = await request.get('/api/nodeinfo/2.0.json')
    const json = JSON.parse(await response.text())

    expect(json.software.name).toBe("pixelfed")
    expect(json.usage.users.total).toBe(0)
    expect(json.metadata.nodeName).toBe("docker-pixelfed e2e")
})

test('/api/v1/instance', async ({ request }) => {
    const response = await request.get('/api/v1/instance')
    const json = JSON.parse(await response.text())

    expect(json.email).toBe("github@example.com")
})

