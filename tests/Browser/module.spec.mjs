import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

// Manual acceptance lane. fixtures/bootstrap.php refuses an unmarked application.
const enabled = process.env.DBCMS_BROWSER_MODULE === '1';
const fixture = enabled ? JSON.parse(readFileSync(process.env.DBCMS_BROWSER_FIXTURES, 'utf8')) : {};
const brands = enabled ? JSON.parse(process.env.DBCMS_BROWSER_BRANDS) : {};
const diagnostics = new WeakMap();
if (!enabled) throw new Error('Requires the marked disposable module application and both brand URLs.');
test.use({ storageState: { cookies: [], origins: [] } });

test.beforeAll(() => {
    for (const brand of ['dbcms', 'termocms']) {
        expect(brands[brand], `Supply the disposable ${brand} URL`).toBeTruthy();
        expect(fixture[brand]).toBeTruthy();
    }
});

test.beforeEach(async ({ page }) => {
    const errors = [];
    diagnostics.set(page, errors);
    page.on('pageerror', error => errors.push(error.message));
    page.on('response', response => { if (response.status() >= 500) errors.push(`${response.status()} ${response.url()}`); });
    await page.addInitScript(() => { if (window.top === window) localStorage.setItem('dbcms_tour_completed', 'true'); });
});

test.afterEach(async ({ page }, testInfo) => {
    await testInfo.attach('browser-errors', { body: JSON.stringify(diagnostics.get(page)), contentType: 'application/json' });
    expect(diagnostics.get(page)).toEqual([]);
});

async function login(page, base) {
    await page.goto(`${base}/admin/login`);
    await page.locator('input[name=email]').fill(process.env.DBCMS_BROWSER_EMAIL || 'browser@example.test');
    await page.locator('input[name=password]').fill(process.env.DBCMS_BROWSER_PASSWORD || 'browser-only-password');
    await Promise.all([page.waitForNavigation(), page.getByRole('button', { name: /Iniciar sesión/ }).click()]);
    await expect(page).not.toHaveURL(/\/login/);
    await expect(page.locator('main').first()).toBeVisible();
}

const titleInput = (page, locale) => page.locator(`input[name="translations[${locale}][title]"]`).filter({ visible: true });
const localeTab = (page, code) => page.getByRole('button', { name: new RegExp(`\\(${code.toUpperCase()}\\)`) }).first();
const snapshot = (page, testInfo, name) => page.screenshot({ path: testInfo.outputPath(`${name}.png`), fullPage: true });

for (const brand of ['dbcms', 'termocms']) {
    test.describe(brand, () => {
        test('Gallery saves localized metadata while retaining its image', async ({ page }, testInfo) => {
            const base = brands[brand], data = fixture[brand];
            await login(page, base);
            await page.goto(`${base}/admin/galleries`);
            await expect(page.locator('main')).toContainText(`Browser gallery ${brand}`);
            await page.goto(`${base}/admin/galleries/${data.gallery}/edit`);
            await titleInput(page, 'es').fill(`Browser gallery ${brand} updated`);
            await localeTab(page, 'en').click();
            await expect(titleInput(page, 'en')).toHaveValue(`Browser gallery English ${brand}`);
            await localeTab(page, 'es').click();
            await Promise.all([page.waitForNavigation(), page.getByRole('button', { name: /Guardar Cambios/ }).click()]);
            await page.goto(`${base}/admin/galleries/${data.gallery}/edit`);
            await expect(titleInput(page, 'es')).toHaveValue(`Browser gallery ${brand} updated`);
            await expect(page.locator('img[src$="browser-fixture.svg"]').first()).toBeVisible();
            await snapshot(page, testInfo, `${brand}-gallery`);
            await page.goto(`${base}${fixture.publicGallery}`);
            await expect(page.getByRole('heading', { name: /Browser regional gallery/ })).toBeVisible();
            await expect(page.locator('main').first()).toContainText('Inherited gallery description');
            expect(await page.locator('link[hreflang="es-ES"]').getAttribute('href')).toContain('/es/es/galeria/browser-regional');
            const missing = await page.goto(`${base}${fixture.publicForeignSlug}`);
            expect(missing.status()).toBe(404);
        });

        test('module admin listing fits mobile', async ({ page }, testInfo) => {
            await login(page, brands[brand]);
            await page.setViewportSize({ width: 390, height: 844 });
            await page.goto(`${brands[brand]}/admin/galleries`);
            await expect(page.locator('main').first()).toBeVisible();
            expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false);
            await snapshot(page, testInfo, `${brand}-gallery-mobile`);
        });
    });
}
