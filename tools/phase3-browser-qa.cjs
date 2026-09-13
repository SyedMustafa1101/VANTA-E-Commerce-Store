const { mkdir } = require('node:fs/promises');
const { execFileSync } = require('node:child_process');
const { chromium } = require('C:/Users/ALPHA/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');

const base = 'http://127.0.0.1:8000';
const mysql = 'C:/xampp/mysql/bin/mysql.exe';
const email = 'phase3-browser@example.test';
const password = 'BrowserTest123';
const failures = [];
const checks = [];
let originalStock = 0;

function assert(condition, label, detail = '') {
    checks.push({ label, passed: Boolean(condition), detail });
    if (!condition) failures.push(detail ? label + ': ' + detail : label);
}

function sql(statement) {
    return execFileSync(mysql, [
        '--host=localhost', '--user=root', '--skip-column-names', 'vanta',
        '--execute=' + statement,
    ], { encoding: 'utf8' }).trim();
}

function cleanup() {
    sql(
        "DELETE cu FROM coupon_usage cu JOIN orders o ON o.id=cu.order_id WHERE o.customer_email='" + email + "';" +
        "DELETE FROM orders WHERE customer_email='" + email + "';" +
        "DELETE FROM login_attempts WHERE email='" + email + "';" +
        "DELETE FROM newsletter_subscribers WHERE email='" + email + "';" +
        "DELETE FROM users WHERE email='" + email + "';"
    );
    if (originalStock > 0) {
        sql('UPDATE product_variants SET stock_quantity=' + originalStock + ' WHERE id=1011');
    }
}

async function waitReady(page) {
    await page.waitForSelector('html.is-ready', { timeout: 10000 });
}

async function assertHealthy(page, label, errors) {
    await page.waitForTimeout(150);
    assert(errors.console.length === 0, label + ' console', errors.console.join(' | '));
    assert(errors.runtime.length === 0, label + ' runtime', errors.runtime.join(' | '));
    assert(errors.responses.length === 0, label + ' resources', errors.responses.join(' | '));
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    assert(overflow <= 1, label + ' horizontal overflow', String(overflow));
}

function observe(page) {
    const errors = { console: [], runtime: [], responses: [] };
    page.on('console', (message) => {
        if (
            message.type() === 'error'
            && !message.text().includes('cdn.tailwindcss.com')
            && !message.text().includes('ERR_NETWORK_ACCESS_DENIED')
        ) errors.console.push(message.text());
    });
    page.on('pageerror', (error) => errors.runtime.push(error.message));
    page.on('response', (response) => {
        if (response.url().startsWith(base) && response.status() >= 400) {
            errors.responses.push(response.status() + ' ' + response.url());
        }
    });
    return errors;
}

async function run() {
    await mkdir('tools/qa-artifacts', { recursive: true });
    originalStock = Number(sql('SELECT stock_quantity FROM product_variants WHERE id=1011'));
    cleanup();

    const browser = await chromium.launch({
        headless: true,
        executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
    });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const page = await context.newPage();
    const errors = observe(page);

    try {
        await page.goto(base + '/', { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        await page.locator('[data-wishlist-toggle]').first().click();
        await page.waitForFunction(() => document.querySelector('[data-wishlist-count]')?.textContent === '1');
        await page.locator('[data-quick-add]').first().click();
        await page.waitForFunction(() => document.querySelector('[data-cart-count]')?.textContent === '1');
        await page.keyboard.press('Escape');
        await page.reload({ waitUntil: 'domcontentloaded' });
        await waitReady(page);
        await page.waitForFunction(() => document.querySelector('[data-cart-count]')?.textContent === '1');
        assert(await page.locator('[data-wishlist-count]').first().innerText() === '1', 'guest wishlist survives reload');
        assert(await page.locator('[data-cart-count]').first().innerText() === '1', 'guest cart survives reload');

        const csrfResponse = await context.request.post(base + '/api/cart/add.php', {
            data: { product_id: 101, quantity: 1 },
        });
        assert(csrfResponse.status() === 419, 'state-changing API rejects missing CSRF token');

        await page.goto(base + '/register.php', { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        await page.locator('[name="first_name"]').fill('Browser');
        await page.locator('[name="last_name"]').fill('Tester');
        await page.locator('[name="email"]').fill(email);
        await page.locator('[name="password"]').fill(password);
        await page.locator('[name="confirm_password"]').fill(password);
        await Promise.all([
            page.waitForURL(/account\/index\.php$/),
            page.locator('.auth-form button[type="submit"]').click(),
        ]);
        await waitReady(page);
        assert(await page.locator('.account-hero h1').innerText() === 'WELCOME BACK,\nBROWSER.', 'registration auto-signs in to account');
        await page.waitForFunction(() => document.querySelector('[data-cart-count]')?.textContent === '1');
        assert(await page.locator('[data-wishlist-count]').first().innerText() === '1', 'guest wishlist merged after registration');
        assert(await page.locator('[data-cart-count]').first().innerText() === '1', 'guest cart merged after registration');
        await page.screenshot({ path: 'tools/qa-artifacts/account-desktop.jpg', type: 'jpeg', quality: 70, fullPage: false });
        await assertHealthy(page, 'desktop account', errors);

        await page.goto(base + '/account/addresses.php', { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        await page.locator('[name="label"]').fill('Studio');
        await page.locator('[name="recipient_name"]').fill('Browser Tester');
        await page.locator('[name="phone"]').fill('+92 300 0000000');
        await page.locator('[name="address_line_1"]').fill('10 Test Avenue');
        await page.locator('[name="city"]').fill('Karachi');
        await page.locator('[name="province"]').fill('Sindh');
        await page.locator('[name="postal_code"]').fill('74000');
        await page.locator('[name="country"]').fill('Pakistan');
        await page.locator('[name="is_default"]').check();
        await Promise.all([
            page.waitForURL(/account\/addresses\.php$/),
            page.locator('.vanta-form button[type="submit"]').click(),
        ]);
        await waitReady(page);
        assert(await page.locator('.address-card').count() === 1, 'customer address persists');
        assert(await page.locator('.address-card.is-default').count() === 1, 'default address persists');

        await page.goto(base + '/account/wishlist.php', { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        assert(await page.locator('.account-product-grid [data-product-card]').count() === 1, 'account wishlist is database-backed');

        await page.goto(base + '/cart.php', { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        assert(await page.locator('[data-cart-page-lines] > article').count() === 1, 'full cart renders server-backed variant line');
        assert((await page.locator('[data-cart-page-lines] > article').innerText()).includes('VNT-ESS-BLK-S'), 'cart identifies exact SKU');

        await page.goto(base + '/checkout.php', { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        assert(await page.locator('[name="address_line_1"]').inputValue() === '10 Test Avenue', 'checkout prefills default owned address');
        await page.locator('[data-coupon-code]').fill('NOTREAL');
        await page.locator('[data-coupon-apply]').click();
        await page.waitForFunction(() => document.querySelector('[data-coupon-feedback]')?.textContent.includes('not valid'));
        assert((await page.locator('[data-coupon-feedback]').innerText()).includes('not valid'), 'invalid coupon shows inline error');
        errors.responses = errors.responses.filter((entry) => !entry.includes('422 ' + base + '/api/coupons/apply.php'));
        errors.console = errors.console.filter((entry) => !entry.includes('status of 422'));
        await page.locator('[data-coupon-code]').fill('VANTA10');
        await page.locator('[data-coupon-apply]').click();
        await page.waitForFunction(() => document.querySelector('[data-coupon-feedback]')?.textContent.includes('applied'));
        assert((await page.locator('[data-quote-discount]').innerText()).includes('680'), 'valid coupon updates server quote');
        await page.locator('[name="payment_method"][value="demo_card"]').check();
        await page.screenshot({ path: 'tools/qa-artifacts/checkout-desktop.jpg', type: 'jpeg', quality: 70, fullPage: false });
        await assertHealthy(page, 'desktop checkout', errors);

        await Promise.all([
            page.waitForURL(/order-confirmation\.php\?order=VNT-/),
            page.locator('.checkout-summary > button[type="submit"]').click(),
        ]);
        await waitReady(page);
        const orderNumber = (await page.locator('.confirmation-hero strong').innerText()).trim();
        assert(/^VNT-\d{4}-\d{6}$/.test(orderNumber), 'confirmation displays readable order number');
        assert((await page.locator('.order-detail').innerText()).includes('Demo Card'), 'confirmation displays simulated payment method');
        const mailNotice = await page.locator('.confirmation-email-note').innerText();
        assert(
            ['sent to', 'could not be sent', 'being prepared'].some((message) => mailNotice.includes(message)),
            'email delivery status remains a non-blocking confirmation notice'
        );
        await page.reload({ waitUntil: 'domcontentloaded' });
        assert((await page.locator('.confirmation-hero strong').innerText()).trim() === orderNumber, 'confirmation refresh reads the same order without resending');
        await page.screenshot({ path: 'tools/qa-artifacts/confirmation-desktop.jpg', type: 'jpeg', quality: 70, fullPage: false });
        await assertHealthy(page, 'desktop confirmation', errors);

        await page.goto(base + '/account/orders.php', { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        assert((await page.locator('.order-list').innerText()).includes(orderNumber), 'account order history contains placed order');
        await page.locator('.order-list a').first().click();
        await waitReady(page);
        assert((await page.locator('.account-hero h1').innerText()).includes(orderNumber), 'owned order detail opens');

        await page.goto(base + '/product.php?slug=vanta-oversized-essential-tee#reviews', { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        await page.locator('[data-review-form] [name="rating"]').selectOption('5');
        await page.locator('[data-review-form] [name="content"]').fill('Excellent weight, structured fit, and careful finishing throughout.');
        await page.locator('[data-review-form] button[type="submit"]').click();
        await page.waitForFunction(() => document.querySelector('[data-review-feedback]')?.textContent.includes('moderation'));
        assert((await page.locator('[data-review-feedback]').innerText()).includes('moderation'), 'verified purchaser review enters moderation');
        assert(await page.locator('.review-list article').count() === 0, 'pending review remains hidden publicly');

        await page.goto(base + '/account/index.php', { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        await Promise.all([
            page.waitForURL(/\/index\.php$/),
            page.locator('.account-nav form button').click(),
        ]);
        await waitReady(page);
        await page.goto(base + '/account/index.php', { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        assert(page.url().includes('/login.php?return='), 'protected account redirects signed-out customer');
        await page.locator('[name="email"]').fill(email);
        await page.locator('[name="password"]').fill('WrongPassword123');
        await page.locator('.auth-form button[type="submit"]').click();
        assert((await page.locator('.form-banner--error').innerText()) === 'Invalid email or password.', 'wrong password uses generic error');
        await page.locator('[name="password"]').fill(password);
        await Promise.all([
            page.waitForURL(/account\/index\.php$/),
            page.locator('.auth-form button[type="submit"]').click(),
        ]);
        await waitReady(page);
        assert(await page.locator('.account-hero').count() === 1, 'existing customer can sign in');

        await page.setViewportSize({ width: 390, height: 844 });
        for (const route of [
            '/account/index.php',
            '/account/wishlist.php',
            '/cart.php',
            '/checkout.php',
            '/order-confirmation.php?order=' + encodeURIComponent(orderNumber),
        ]) {
            await page.goto(base + route, { waitUntil: 'domcontentloaded' });
            await waitReady(page);
            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
            assert(overflow <= 1, 'mobile ' + route.split('?')[0] + ' horizontal overflow', String(overflow));
        }
        await page.screenshot({ path: 'tools/qa-artifacts/confirmation-mobile.jpg', type: 'jpeg', quality: 70, fullPage: false });

        await page.goto(base + '/account/index.php', { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        await Promise.all([
            page.waitForURL(/\/index\.php$/),
            page.locator('.account-nav form button').click(),
        ]);
        await page.goto(base + '/login.php', { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        assert((await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)) <= 1, 'mobile login horizontal overflow');
        await page.screenshot({ path: 'tools/qa-artifacts/login-mobile.jpg', type: 'jpeg', quality: 70, fullPage: false });
        await page.goto(base + '/register.php', { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        assert((await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)) <= 1, 'mobile registration horizontal overflow');
        await assertHealthy(page, 'mobile commerce', errors);
    } finally {
        await context.close();
        await browser.close();
        cleanup();
    }
}

run().then(() => {
    console.log(JSON.stringify({ passed: failures.length === 0, checkCount: checks.length, failures, checks }, null, 2));
    if (failures.length) process.exitCode = 1;
}).catch((error) => {
    console.error(error);
    try { cleanup(); } catch {}
    process.exitCode = 1;
});
