<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'VANTA';
$pageDescription = $pageDescription ?? 'Premium contemporary streetwear built for after dark.';
$currentPage = $currentPage ?? 'home';
$bodyClass = $bodyClass ?? '';
$headerTheme = $headerTheme ?? 'overlay';
$viewer = current_user();
$initialCart = cart_service()->summary();
$initialWishlistIds = wishlist_service()->productIds();
$catalogForClient = array_map(
    static function (array $product): array {
        $product['images'] = array_map(static fn (string $image): string => asset($image), $product['images']);
        $product['colors'] = array_map(
            static function (array $color): array {
                $color['images'] = array_map(static fn (string $image): string => asset($image), $color['images']);
                return $color;
            },
            $product['colors']
        );
        $product['url'] = url('product.php?slug=' . rawurlencode($product['slug']));
        return $product;
    },
    catalog_products()
);
$clientConfig = [
    'baseUrl' => (string) config('base_url', ''),
    'csrfToken' => csrf_token(),
    'authenticated' => $viewer !== null,
];
?>
<!doctype html>
<html lang="en" class="bg-vanta-black">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0A0A0A">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' fill='%230A0A0A'/%3E%3Cpath d='M11 13h12l9 28 9-28h12L38 53H26z' fill='%23B7FF2A'/%3E%3C/svg%3E">
    <script>document.documentElement.classList.add('js');</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,500;0,600;0,700;0,800;1,700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind Play CDN keeps Phase 1 zero-install. The pinned CLI build path is documented in README. -->
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?> bg-vanta-black text-vanta-white antialiased" data-page="<?= e($currentPage) ?>">
    <a class="skip-link" href="#main-content">Skip to content</a>

    <div class="site-loader" data-site-loader aria-hidden="true">
        <div class="site-loader__inner">
            <div class="site-loader__word" aria-label="VANTA">
                <?php foreach (str_split('VANTA') as $letter): ?>
                    <span><?= e($letter) ?></span>
                <?php endforeach; ?>
            </div>
            <div class="site-loader__track"><span data-loader-progress></span></div>
            <p>EST. 2026 <span data-loader-count>00</span></p>
        </div>
    </div>

    <div class="page-transition" data-page-transition aria-hidden="true">
        <p data-transition-label>VANTA</p>
    </div>

    <header class="site-header" data-site-header data-theme="<?= e($headerTheme) ?>">
        <div class="site-header__bar">
            <a class="wordmark magnetic" href="<?= e(url('index.php')) ?>" data-transition-link data-transition-name="HOME" aria-label="VANTA home">
                VANTA<span class="wordmark__dot">.</span>
            </a>

            <nav class="desktop-nav" aria-label="Primary navigation">
                <a href="<?= e(url('collection.php?collection=new-drop')) ?>" data-transition-link data-transition-name="NEW DROP">New Drop</a>
                <a href="<?= e(url('shop.php')) ?>" data-transition-link data-transition-name="SHOP">Shop</a>
                <a href="<?= e(url('index.php#collections')) ?>">Collections</a>
            </nav>

            <nav class="desktop-actions" aria-label="Utility navigation">
                <button type="button" data-search-open>Search</button>
                <a href="<?= e(url($viewer ? 'account/index.php' : 'login.php')) ?>"><?= $viewer ? 'Account' : 'Sign in' ?></a>
                <button class="icon-action wishlist-action" type="button" data-wishlist-open aria-label="Open wishlist">
                    <?= icon('heart', 'h-4 w-4') ?>
                    <span class="utility-count" data-wishlist-count><?= count($initialWishlistIds) ?></span>
                </button>
                <button class="bag-action" type="button" data-cart-open>
                    Bag <span class="bag-count" data-cart-count><?= (int) $initialCart['count'] ?></span>
                </button>
            </nav>

            <button class="menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-menu">
                <span class="sr-only">Open menu</span>
                <span class="menu-toggle__line"></span>
                <span class="menu-toggle__line"></span>
            </button>
        </div>
    </header>

    <div class="mobile-menu" id="mobile-menu" data-mobile-menu aria-hidden="true">
        <div class="mobile-menu__grain"></div>
        <div class="mobile-menu__top">
            <span>VANTA / MENU</span>
            <span>PK — 2026</span>
        </div>
        <nav class="mobile-menu__nav" aria-label="Mobile navigation">
            <a href="<?= e(url('collection.php?collection=new-drop')) ?>" data-transition-link data-transition-name="NEW DROP" data-menu-link><span>01</span> New Drop</a>
            <a href="<?= e(url('shop.php')) ?>" data-transition-link data-transition-name="SHOP" data-menu-link><span>02</span> Shop</a>
            <a href="<?= e(url('index.php#collections')) ?>" data-menu-link><span>03</span> Collections</a>
            <a href="<?= e(url($viewer ? 'account/index.php' : 'login.php')) ?>" data-menu-link><span>04</span> <?= $viewer ? 'Account' : 'Sign in' ?></a>
        </nav>
        <div class="mobile-menu__secondary">
            <button type="button" data-search-open>Search</button>
            <button type="button" data-wishlist-open>Wishlist (<span data-wishlist-count><?= count($initialWishlistIds) ?></span>)</button>
            <button type="button" data-cart-open>Bag (<span data-cart-count><?= (int) $initialCart['count'] ?></span>)</button>
        </div>
        <p class="mobile-menu__statement">Own the night.</p>
    </div>

    <section class="search-overlay" id="search-overlay" data-search-overlay role="dialog" aria-modal="true" aria-labelledby="search-title" aria-hidden="true">
        <div class="search-overlay__top">
            <p>VANTA / SEARCH</p>
            <button class="overlay-close" type="button" data-search-close>
                <span>Close</span><?= icon('close', 'h-5 w-5') ?>
            </button>
        </div>
        <div class="search-overlay__body">
            <div>
                <p class="eyebrow">FIND YOUR UNIFORM</p>
                <h2 id="search-title">Search the<br>collection.</h2>
            </div>
            <div class="search-overlay__work">
                <label class="search-field">
                    <span class="sr-only">Search products</span>
                    <?= icon('search', 'h-5 w-5') ?>
                    <input type="search" data-search-input autocomplete="off" placeholder="TYPE TO SEARCH" aria-describedby="search-status">
                </label>
                <p class="search-status" id="search-status" data-search-status aria-live="polite">Search by product, category, collection, or detail.</p>
                <div class="search-results" data-search-results></div>
                <div class="search-suggestions" data-search-suggestions>
                    <p>Popular searches</p>
                    <button type="button" data-search-suggestion="hoodie">Hoodies</button>
                    <button type="button" data-search-suggestion="after dark">After Dark</button>
                    <button type="button" data-search-suggestion="cargo">Cargo</button>
                    <button type="button" data-search-suggestion="limited">Limited</button>
                </div>
            </div>
        </div>
    </section>

    <div class="drawer-backdrop" data-drawer-backdrop aria-hidden="true"></div>

    <aside class="store-drawer" data-cart-drawer role="dialog" aria-modal="true" aria-labelledby="cart-title" aria-hidden="true">
        <div class="store-drawer__header">
            <div>
                <p class="eyebrow">YOUR SELECTION</p>
                <h2 id="cart-title">Bag <span data-cart-count><?= (int) $initialCart['count'] ?></span></h2>
            </div>
            <button class="overlay-close overlay-close--dark" type="button" data-cart-close aria-label="Close bag"><?= icon('close', 'h-5 w-5') ?></button>
        </div>
        <div class="store-drawer__content">
            <div class="drawer-empty" data-cart-empty>
                <p>Your bag is empty.</p>
                <span>The night is still young.</span>
                <a class="button button--outline" href="<?= e(url('shop.php')) ?>" data-transition-link data-transition-name="SHOP">Explore the store <?= icon('arrow-right', 'h-4 w-4') ?></a>
            </div>
            <div class="cart-lines" data-cart-lines></div>
        </div>
        <div class="store-drawer__footer" data-cart-footer hidden>
            <div><span>Subtotal</span><strong data-cart-subtotal>PKR 0</strong></div>
            <p>Shipping and taxes calculated at checkout.</p>
            <a class="button button--lime" href="<?= e(url('checkout.php')) ?>">Continue to checkout <?= icon('arrow-right', 'h-4 w-4') ?></a>
        </div>
    </aside>

    <aside class="store-drawer" data-wishlist-drawer role="dialog" aria-modal="true" aria-labelledby="wishlist-title" aria-hidden="true">
        <div class="store-drawer__header">
            <div>
                <p class="eyebrow"><?= $viewer ? 'SAVED TO YOUR ACCOUNT' : 'SAVED FOR THIS SESSION' ?></p>
                <h2 id="wishlist-title">Wishlist <span data-wishlist-count><?= count($initialWishlistIds) ?></span></h2>
            </div>
            <button class="overlay-close overlay-close--dark" type="button" data-wishlist-close aria-label="Close wishlist"><?= icon('close', 'h-5 w-5') ?></button>
        </div>
        <div class="store-drawer__content">
            <div class="drawer-empty" data-wishlist-empty>
                <p>Nothing saved yet.</p>
                <span>Tap the heart on any piece to keep it close.</span>
            </div>
            <div class="wishlist-lines" data-wishlist-lines></div>
        </div>
        <div class="store-drawer__footer drawer-account-note">
            <?php if ($viewer): ?>
                <p>Saved to <?= e($viewer['email']) ?> and available whenever you sign in.</p>
                <a class="text-link" href="<?= e(url('account/wishlist.php')) ?>">View account wishlist <?= icon('arrow-right', 'h-4 w-4') ?></a>
            <?php else: ?>
                <p>Your session wishlist will merge into your account when you sign in.</p>
                <a class="text-link" href="<?= e(url('login.php')) ?>">Sign in <?= icon('arrow-right', 'h-4 w-4') ?></a>
            <?php endif; ?>
        </div>
    </aside>

    <div class="toast" data-toast role="status" aria-live="polite" aria-atomic="true"></div>
    <script type="application/json" id="vanta-config"><?= json_encode($clientConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
    <script type="application/json" id="vanta-catalog"><?= json_encode($catalogForClient, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
