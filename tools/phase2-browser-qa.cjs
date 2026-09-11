const { mkdir } = require('node:fs/promises');
const { chromium } = require('C:/Users/ALPHA/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');

const base = 'http://127.0.0.1:8000';
const failures = [];
const checks = [];

function assert(condition, label, detail = '') {
    checks.push({ label, passed: Boolean(condition), detail });
    if (!condition) failures.push(detail ? label + ': ' + detail : label);
}

async function observePage(page, label) {
    const consoleErrors = [];
    const pageErrors = [];
    const badResponses = [];

    page.on('console', (message) => {
        if (message.type() === 'error' && !message.text().includes('cdn.tailwindcss.com')) consoleErrors.push(message.text());
    });
    page.on('pageerror', (error) => pageErrors.push(error.message));
    page.on('response', (response) => {
        if (response.url().startsWith(base) && response.status() >= 400) badResponses.push(response.status() + ' ' + response.url());
    });

    return async () => {
        assert(consoleErrors.length === 0, label + ' console', consoleErrors.join(' | '));
        assert(pageErrors.length === 0, label + ' runtime', pageErrors.join(' | '));
        assert(badResponses.length === 0, label + ' resources', badResponses.join(' | '));
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        assert(overflow <= 1, label + ' horizontal overflow', String(overflow));
    };
}

async function waitReady(page) {
    await page.waitForSelector('html.is-ready', { timeout: 8000 });
}

async function runDesktop(browser) {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'no-preference' });
    const page = await context.newPage();
    const finishHome = await observePage(page, 'desktop home');
    await page.goto(base + '/', { waitUntil: 'domcontentloaded' });
    await waitReady(page);
    assert(await page.locator('h1').innerText() === 'WEAR\nTHE\nATTITUDE.', 'desktop hero heading');
    assert(await page.locator('[data-product-card]').count() === 8, 'desktop homepage product cards');
    await page.waitForTimeout(1800);
    await page.screenshot({ path: 'tools/qa-artifacts/home-desktop.jpg', type: 'jpeg', quality: 65, fullPage: false });

    const searchButton = page.locator('[data-search-open]').first();
    await searchButton.click();
    await page.waitForSelector('[data-search-overlay].is-open');
    await page.waitForFunction(() => document.activeElement?.matches('[data-search-input]'));
    assert(await page.evaluate(() => document.activeElement?.matches('[data-search-input]')), 'search autofocus');
    await page.locator('[data-search-input]').fill('hoodie');
    assert(await page.locator('.search-result').count() === 2, 'search result matching');
    await page.keyboard.press('Escape');
    await page.waitForFunction(() => document.querySelector('[data-search-overlay]')?.getAttribute('aria-hidden') === 'true');

    const firstWishlist = page.locator('[data-wishlist-toggle]').first();
    await firstWishlist.click();
    assert(await firstWishlist.getAttribute('aria-pressed') === 'true', 'wishlist selected state');
    assert(await page.locator('[data-wishlist-count]').first().innerText() === '1', 'wishlist count');
    await page.locator('[data-wishlist-open]').first().click();
    assert(await page.locator('.wishlist-line').count() === 1, 'wishlist drawer item');
    await page.keyboard.press('Escape');

    await page.locator('[data-quick-add]').first().click();
    await page.waitForSelector('[data-cart-drawer].is-open');
    assert(await page.locator('.cart-line').count() === 1, 'quick add cart line');
    assert(await page.locator('[data-cart-count]').first().innerText() === '1', 'cart count after add');
    await page.locator('[data-cart-action="increase"]').click();
    await page.waitForFunction(() => document.querySelector('[data-cart-count]')?.textContent === '2');
    assert(await page.locator('[data-cart-count]').first().innerText() === '2', 'cart quantity increase');
    await page.locator('[data-cart-action="remove"]').click();
    await page.waitForFunction(() => !document.querySelector('[data-cart-empty]')?.hidden);
    assert(await page.locator('[data-cart-empty]').isVisible(), 'empty cart state');
    await page.keyboard.press('Escape');

    const email = page.locator('[data-newsletter-form] input');
    await email.fill('invalid');
    await page.locator('[data-newsletter-form] button').click();
    assert(await email.getAttribute('aria-invalid') === 'true', 'newsletter invalid state');
    await email.fill('night@vanta.example');
    await page.locator('[data-newsletter-form] button').click();
    await page.waitForFunction(() => document.querySelector('[data-newsletter-feedback]')?.textContent.trim().length > 0);
    assert(/Welcome|already on the list/.test(await page.locator('[data-newsletter-feedback]').innerText()), 'newsletter success state');

    await Promise.all([
        page.waitForURL(/\/shop\.php$/),
        page.locator('.desktop-nav a[href$="shop.php"]').click(),
    ]);
    await waitReady(page);
    assert(new URL(page.url()).pathname.endsWith('/shop.php'), 'animated navigation reaches shop');
    await page.goBack({ waitUntil: 'domcontentloaded' });
    await waitReady(page);
    assert(new URL(page.url()).pathname.endsWith('/'), 'browser back restores home');
    await page.goForward({ waitUntil: 'domcontentloaded' });
    await waitReady(page);
    assert(new URL(page.url()).pathname.endsWith('/shop.php'), 'browser forward restores shop');
    await finishHome();

    const finishShop = await observePage(page, 'desktop shop');
    await page.goto(base + '/shop.php', { waitUntil: 'domcontentloaded' });
    await waitReady(page);
    await page.screenshot({ path: 'tools/qa-artifacts/shop-desktop.jpg', type: 'jpeg', quality: 65, fullPage: false });
    assert(await page.locator('[data-shop-grid] [data-product-card]').count() === 10, 'shop product count');
    await page.locator('[data-shop-search]').fill('hoodie');
    assert(await page.locator('[data-result-count]').innerText() === '2', 'shop search filter');
    await page.locator('[data-shop-search]').fill('');
    await page.locator('[data-filter-group="category"] input[value="outerwear"]').check();
    assert(await page.locator('[data-result-count]').innerText() === '2', 'category filter');
    await page.locator('[data-filter-group="category"] input[value="outerwear"]').uncheck();
    await page.locator('[data-shop-sort]').selectOption('price-low');
    assert(await page.locator('[data-shop-grid] [data-product-card]').first().getAttribute('data-product-id') === '109', 'price sort');
    await page.locator('[data-price-range]').evaluate((input) => {
        input.value = '7000';
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
    assert(await page.locator('[data-result-count]').innerText() === '2', 'price filter');
    await finishShop();

    const finishProduct = await observePage(page, 'desktop product');
    await page.goto(base + '/product.php?slug=vanta-oversized-essential-tee', { waitUntil: 'domcontentloaded' });
    await waitReady(page);
    await page.screenshot({ path: 'tools/qa-artifacts/product-desktop.jpg', type: 'jpeg', quality: 65, fullPage: false });
    assert(!(await page.locator('[data-add-to-bag]').isDisabled()), 'default product in stock');
    await page.locator('[name="size"][value="XL"]').check({ force: true });
    assert(await page.locator('[data-add-to-bag]').isDisabled(), 'black XL disables add');
    assert((await page.locator('[data-stock-state]').innerText()).includes('OUT OF STOCK'), 'black XL out-of-stock label');
    const leadBefore = await page.locator('[data-gallery-image]').first().getAttribute('src');
    await page.locator('[name="color"][value="bone"]').check({ force: true });
    await page.waitForTimeout(260);
    const leadAfter = await page.locator('[data-gallery-image]').first().getAttribute('src');
    assert(leadBefore !== leadAfter, 'color image crossfade source');
    assert(!(await page.locator('[data-add-to-bag]').isDisabled()), 'bone XL enables add');
    await page.locator('[data-size-guide-open]').click();
    assert(await page.locator('[data-size-guide]').evaluate((dialog) => dialog.open), 'size guide opens');
    await page.keyboard.press('Escape');
    assert(!(await page.locator('[data-size-guide]').evaluate((dialog) => dialog.open)), 'size guide Escape close');
    await page.locator('[data-add-to-bag]').click();
    await page.waitForFunction(() => document.querySelector('[data-cart-drawer]')?.getAttribute('aria-hidden') === 'false');
    assert(await page.locator('[data-cart-drawer]').getAttribute('aria-hidden') === 'false', 'product add opens bag');
    await page.keyboard.press('Escape');
    await finishProduct();

    for (const collection of ['new-drop', 'essentials', 'after-dark', 'limited']) {
        await page.goto(base + '/collection.php?collection=' + collection, { waitUntil: 'domcontentloaded' });
        await waitReady(page);
        assert(await page.locator('.collection-hero h1').count() === 1, collection + ' collection direct route');
        if (collection === 'essentials' || collection === 'after-dark') {
            await page.waitForTimeout(1400);
            await page.screenshot({ path: 'tools/qa-artifacts/collection-' + collection + '.jpg', type: 'jpeg', quality: 65, fullPage: false });
        }
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        assert(overflow <= 1, collection + ' desktop overflow', String(overflow));
    }

    await context.close();
}

async function runMobile(browser) {
    const context = await browser.newContext({ viewport: { width: 390, height: 844 }, reducedMotion: 'no-preference' });
    const page = await context.newPage();
    const finishHome = await observePage(page, 'mobile home');
    await page.goto(base + '/', { waitUntil: 'domcontentloaded' });
    await waitReady(page);
    await page.waitForTimeout(1800);
    await page.screenshot({ path: 'tools/qa-artifacts/home-mobile.jpg', type: 'jpeg', quality: 65, fullPage: false });
    assert(await page.locator('[data-menu-toggle]').isVisible(), 'mobile menu control visible');
    await page.locator('[data-menu-toggle]').click();
    assert(await page.locator('[data-menu-toggle]').getAttribute('aria-expanded') === 'true', 'mobile menu opens');
    await page.keyboard.press('Escape');
    await page.waitForFunction(() => document.querySelector('[data-menu-toggle]')?.getAttribute('aria-expanded') === 'false');
    assert(await page.locator('[data-menu-toggle]').getAttribute('aria-expanded') === 'false', 'mobile menu Escape close');
    await page.locator('[data-menu-toggle]').click();
    await page.waitForSelector('[data-mobile-menu].is-open');
    await page.waitForTimeout(1100);
    const mobileSearch = page.locator('.mobile-menu [data-search-open]');
    const mobileSearchState = await mobileSearch.evaluate((button) => {
        const rect = button.getBoundingClientRect();
        return {
            visible: Boolean(button.offsetWidth && button.offsetHeight && rect.bottom > 0 && rect.top < window.innerHeight),
            rect: { top: rect.top, bottom: rect.bottom, width: rect.width, height: rect.height },
            menuClass: button.closest('[data-mobile-menu]')?.className,
        };
    });
    assert(mobileSearchState.visible, 'mobile menu search visible', JSON.stringify(mobileSearchState));
    await mobileSearch.evaluate((button) => button.click());
    await page.waitForSelector('[data-search-overlay].is-open');
    await page.locator('[data-search-input]').fill('cargo');
    assert(await page.locator('.search-result').count() === 2, 'mobile search');
    await page.keyboard.press('Escape');
    await finishHome();

    const finishShop = await observePage(page, 'mobile shop');
    await page.goto(base + '/shop.php', { waitUntil: 'domcontentloaded' });
    await waitReady(page);
    await page.screenshot({ path: 'tools/qa-artifacts/shop-mobile.jpg', type: 'jpeg', quality: 65, fullPage: false });
    assert(await page.locator('[data-filter-open]').isVisible(), 'mobile filter trigger visible');
    await page.locator('[data-filter-open]').click();
    assert(await page.locator('[data-filter-panel]').getAttribute('aria-hidden') === 'false', 'mobile filter opens');
    await page.keyboard.press('Escape');
    await page.waitForFunction(() => document.querySelector('[data-filter-panel]')?.getAttribute('aria-hidden') === 'true');
    assert(await page.locator('[data-filter-panel]').getAttribute('aria-hidden') === 'true', 'mobile filter Escape close');
    await finishShop();

    const finishProduct = await observePage(page, 'mobile product');
    await page.goto(base + '/product.php?slug=nocturne-bomber', { waitUntil: 'domcontentloaded' });
    await waitReady(page);
    await page.screenshot({ path: 'tools/qa-artifacts/product-mobile.jpg', type: 'jpeg', quality: 65, fullPage: false });
    assert(await page.locator('.product-gallery__item').count() === 3, 'mobile product gallery');
    await page.locator('[name="size"][value="XL"]').check({ force: true });
    assert(await page.locator('[data-add-to-bag]').isDisabled(), 'mobile out-of-stock state');
    await finishProduct();
    await context.close();
}

async function runReducedMotion(browser) {
    const context = await browser.newContext({ viewport: { width: 1280, height: 800 }, reducedMotion: 'reduce' });
    const page = await context.newPage();
    const finish = await observePage(page, 'reduced motion');
    await page.goto(base + '/', { waitUntil: 'domcontentloaded' });
    await waitReady(page);
    assert(await page.locator('html').getAttribute('data-motion-preference') === 'reduced', 'reduced motion preference');
    assert(await page.locator('[data-site-loader]').count() === 0, 'reduced motion skips loader');
    await finish();
    await context.close();
}

(async () => {
    await mkdir('tools/qa-artifacts', { recursive: true });
    const browser = await chromium.launch({
        headless: true,
        executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
    });
    try {
        await runDesktop(browser);
        await runMobile(browser);
        await runReducedMotion(browser);
    } finally {
        await browser.close();
    }

    console.log(JSON.stringify({
        passed: failures.length === 0,
        checkCount: checks.length,
        failures,
        checks,
    }, null, 2));
    if (failures.length) process.exitCode = 1;
})().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
