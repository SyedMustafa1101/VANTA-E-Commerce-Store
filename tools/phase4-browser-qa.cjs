'use strict';

const { chromium } = require('C:/Users/ALPHA/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const { execFileSync } = require('node:child_process');
const { mkdir, writeFile, unlink } = require('node:fs/promises');
const { existsSync, unlinkSync } = require('node:fs');
const { join } = require('node:path');

const base = 'http://127.0.0.1:8000';
const mysql = 'C:/xampp/mysql/bin/mysql.exe';
const php = 'C:/xampp/php/php.exe';
const adminEmail = 'phase4-browser@example.test';
const operatorEmail = 'phase4-operator@example.test';
const password = 'Browser-QA-Admin-149!';
const checks = [];
const failures = [];
let uploadedPath = '';

function assert(condition, label, detail = '') {
    checks.push({ label, passed: Boolean(condition), detail });
    if (!condition) failures.push(detail ? `${label}: ${detail}` : label);
}

function sql(statement) {
    return execFileSync(mysql, ['--host=localhost', '--user=root', '--skip-column-names', 'vanta', `--execute=${statement}`], { encoding: 'utf8' }).trim();
}

function cleanup() {
    try {
        if (uploadedPath) {
            sql(`DELETE FROM product_images WHERE path='${uploadedPath.replaceAll("'", "''")}';`);
            const full = join(process.cwd(), uploadedPath.replaceAll('/', '\\'));
            if (existsSync(full)) unlinkSync(full);
        }
        sql(`DELETE FROM admin_login_attempts WHERE email IN ('${adminEmail}','${operatorEmail}');DELETE FROM admin_audit_logs WHERE admin_id IN (SELECT id FROM admins WHERE email IN ('${adminEmail}','${operatorEmail}'));DELETE FROM admins WHERE email IN ('${adminEmail}','${operatorEmail}');`);
    } catch (error) {
        console.error('Cleanup warning:', error.message);
    }
}

function seedAdmins() {
    cleanup();
    const hash = execFileSync(php, ['-r', `echo password_hash('${password}', PASSWORD_DEFAULT);`], { encoding: 'utf8' }).trim();
    sql(`INSERT INTO admins (name,email,password_hash,role,active) VALUES ('Browser QA','${adminEmail}','${hash}','SUPER_ADMIN',1),('Browser Operator','${operatorEmail}','${hash}','ADMIN',1);`);
}

async function healthy(page, label, errors) {
    await page.waitForLoadState('domcontentloaded');
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
    const overflowers = overflow > 1 ? await page.evaluate(() => [...document.querySelectorAll('body *')].map((el) => {
        const rect = el.getBoundingClientRect(); return { tag: el.tagName, className: String(el.className || '').slice(0, 80), right: Math.round(rect.right), width: Math.round(rect.width) };
    }).filter((item) => item.right > innerWidth + 1).slice(0, 8)) : [];
    assert(overflow <= 1, `${label} has no page overflow`, `${overflow} ${JSON.stringify(overflowers)}`);
    assert(errors.page.length === 0, `${label} has no page errors`, errors.page.join(' | '));
    assert(errors.console.length === 0, `${label} has no console errors`, errors.console.join(' | '));
    assert(errors.responses.length === 0, `${label} has no broken local resources`, errors.responses.join(' | '));
    errors.page.length = 0; errors.console.length = 0; errors.responses.length = 0;
}

async function run() {
    seedAdmins();
    const artifacts = join(process.cwd(), 'tools', 'qa-artifacts');
    await mkdir(artifacts, { recursive: true });
    const invalidUpload = join(artifacts, 'invalid-upload.php');
    await writeFile(invalidUpload, '<?php echo "not an image";');
    const browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const page = await context.newPage();
    const errors = { console: [], page: [], responses: [] };
    page.on('console', (message) => { if (message.type() === 'error' && !/fonts\.googleapis|fonts\.gstatic|cdn\.tailwindcss|ERR_NETWORK_ACCESS_DENIED/i.test(message.text())) errors.console.push(message.text()); });
    page.on('pageerror', (error) => errors.page.push(error.message));
    page.on('response', (response) => { if (response.url().startsWith(base) && response.status() >= 400) errors.responses.push(`${response.status()} ${response.url()}`); });
    try {
        await page.goto(`${base}/admin/index.php`);
        assert(page.url().includes('/admin/login.php?return='), 'protected dashboard redirects to admin login');
        await page.locator('[name=email]').fill(adminEmail); await page.locator('[name=password]').fill('wrong-password'); await page.locator('button[type=submit]').click();
        assert((await page.locator('.login-error').innerText()) === 'Invalid email or password.', 'invalid admin login stays generic');
        await page.locator('[name=password]').fill(password); await Promise.all([page.waitForURL(/admin\/index\.php$/), page.locator('button[type=submit]').click()]);
        assert(await page.locator('.kpi').count() === 8, 'dashboard renders eight live KPIs');
        assert(await page.locator('canvas[data-chart-source]').count() === 1, 'dashboard renders real revenue chart');
        await healthy(page, 'desktop dashboard', errors); await page.screenshot({ path: join(artifacts, 'phase4-dashboard-desktop.jpg'), type: 'jpeg', quality: 75, fullPage: false });

        const routes = [
            ['/admin/products.php', '.data-table'], ['/admin/product-edit.php?id=101', '.form-grid'], ['/admin/product-edit.php?id=101&tab=variants', '.data-table'],
            ['/admin/inventory.php', '.data-table'], ['/admin/orders.php', '.data-table'], ['/admin/customers.php', '.data-table'], ['/admin/coupons.php', '.data-table'],
            ['/admin/reviews.php?status=', '.data-table'], ['/admin/newsletter.php', '.data-table'], ['/admin/analytics.php', 'canvas'], ['/admin/settings.php', '.form-grid'], ['/admin/admins.php', '.data-table'],
        ];
        for (const [route, selector] of routes) {
            await page.goto(base + route); await page.waitForLoadState('domcontentloaded');
            assert(await page.locator(selector).count() > 0, `${route} renders its primary surface`);
            await healthy(page, `desktop ${route}`, errors);
        }

        await page.goto(`${base}/admin/product-edit.php?id=101&tab=images`);
        await page.locator('[data-image-input]').setInputFiles(invalidUpload); await page.locator('input[name=alt_text]').first().fill('Rejected Phase 4 upload'); await page.locator('button', { hasText: 'Upload images' }).click();
        assert((await page.locator('.admin-toast--error').innerText()).includes('Only valid JPG, PNG, or WebP'), 'server rejects executable upload by MIME');
        await page.goto(`${base}/admin/product-edit.php?id=101&tab=images`);
        await page.locator('[data-image-input]').setInputFiles(join(process.cwd(), 'assets', 'images', 'catalog', 'vanta-essential-look.jpg'));
        await page.locator('input[name=alt_text]').first().fill('Phase 4 QA upload'); await page.locator('button', { hasText: 'Upload images' }).click();
        assert((await page.locator('.admin-toast--success').innerText()).includes('1 image uploaded'), 'valid product image uploads through admin');
        uploadedPath = sql("SELECT path FROM product_images WHERE product_id=101 AND alt_text='Phase 4 QA upload' ORDER BY id DESC LIMIT 1");
        assert(uploadedPath.startsWith('uploads/products/'), 'uploaded image uses runtime product directory', uploadedPath);
        assert(existsSync(join(process.cwd(), uploadedPath.replaceAll('/', '\\'))), 'uploaded image exists on disk');
        const imageId = sql("SELECT id FROM product_images WHERE product_id=101 AND alt_text='Phase 4 QA upload' ORDER BY id DESC LIMIT 1");
        const uploadedCard = page.locator('.image-card').filter({ has: page.locator('input[name="alt_text"][value="Phase 4 QA upload"]') });
        page.once('dialog', (dialog) => dialog.accept()); await uploadedCard.locator('form[data-confirm] button').click();
        assert(sql(`SELECT COUNT(*) FROM product_images WHERE id=${Number(imageId)}`) === '0', 'product image deletes through confirmed admin action');
        assert(!existsSync(join(process.cwd(), uploadedPath.replaceAll('/', '\\'))), 'deleted runtime image is removed from disk'); uploadedPath = '';

        const csv = await context.request.get(`${base}/admin/export.php?type=inventory`);
        assert(csv.status() === 200 && (csv.headers()['content-type'] || '').includes('text/csv'), 'authorized inventory CSV export');
        const csvText = await csv.text(); assert(csvText.includes('sku,product,color,size,stock,status'), 'CSV export contains expected safe headings');

        await page.setViewportSize({ width: 390, height: 844 });
        for (const route of ['/admin/index.php', '/admin/products.php', '/admin/product-edit.php?id=101&tab=variants', '/admin/inventory.php', '/admin/orders.php', '/admin/analytics.php', '/admin/settings.php']) {
            await page.goto(base + route); await healthy(page, `mobile ${route}`, errors);
        }
        await page.goto(`${base}/admin/index.php`); await page.locator('[data-sidebar-toggle]').click();
        assert(await page.locator('body').evaluate((body) => body.classList.contains('sidebar-open')), 'mobile sidebar opens');
        assert(await page.locator('[data-sidebar]').isVisible(), 'mobile sidebar remains usable');
        await page.screenshot({ path: join(artifacts, 'phase4-dashboard-mobile.jpg'), type: 'jpeg', quality: 75, fullPage: false });

        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.goto(`${base}/admin/index.php`); await page.locator('.admin-sidebar__footer button').click(); await page.waitForURL(/admin\/login\.php$/);
        await page.goto(`${base}/admin/index.php`); assert(page.url().includes('/admin/login.php?return='), 'logout protects subsequent direct access');

        const operator = await browser.newContext({ viewport: { width: 1440, height: 900 } }); const operatorPage = await operator.newPage();
        await operatorPage.goto(`${base}/admin/login.php`); await operatorPage.locator('[name=email]').fill(operatorEmail); await operatorPage.locator('[name=password]').fill(password); await Promise.all([operatorPage.waitForURL(/admin\/index\.php$/), operatorPage.locator('button[type=submit]').click()]);
        const restricted = await operatorPage.goto(`${base}/admin/admins.php`); assert(restricted.status() === 403, 'ADMIN role receives 403 on super-admin route'); assert(await operatorPage.locator('text=Access denied.').count() === 1, 'role restriction renders safe access-denied state'); await operator.close();
    } finally {
        await context.close(); await browser.close(); await unlink(invalidUpload).catch(() => {}); cleanup();
    }
    console.log(JSON.stringify({ passed: failures.length === 0, checkCount: checks.length, failures, checks }, null, 2));
    if (failures.length) process.exitCode = 1;
}

run().catch((error) => { console.error(error); cleanup(); process.exitCode = 1; });
