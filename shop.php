<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Shop — VANTA';
$pageDescription = 'Shop VANTA Drop 001, essentials, after-dark layers, and limited streetwear.';
$currentPage = 'shop';
$bodyClass = 'shop-page';
$headerTheme = 'solid';
$products = catalog_products();

require __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="shop-main" data-shop-page>
    <section class="shop-intro">
        <div class="container-vanta">
            <div class="shop-intro__meta">
                <span>VANTA / STORE</span>
                <span>DROP 001 / PKR</span>
            </div>
            <div class="shop-intro__title">
                <h1>Shop all.</h1>
                <p><span data-result-count><?= count($products) ?></span> pieces engineered for after dark.</p>
            </div>
        </div>
    </section>

    <section class="shop-catalog">
        <div class="shop-toolbar">
            <label class="shop-search">
                <span class="sr-only">Search this catalog</span>
                <?= icon('search', 'h-4 w-4') ?>
                <input type="search" data-shop-search placeholder="SEARCH THE STORE" autocomplete="off">
            </label>
            <button class="shop-filter-toggle" type="button" data-filter-open aria-controls="shop-filters">
                Filters <span aria-hidden="true">+</span>
            </button>
            <label class="shop-sort">
                <span>Sort</span>
                <select data-shop-sort>
                    <option value="newest">Newest</option>
                    <option value="price-low">Price Low → High</option>
                    <option value="price-high">Price High → Low</option>
                    <option value="popular">Popular</option>
                </select>
            </label>
        </div>

        <div class="container-vanta shop-layout">
            <aside class="filter-panel" id="shop-filters" data-filter-panel aria-label="Product filters" aria-hidden="false">
                <div class="filter-panel__top">
                    <div><span>FILTER</span><strong>Narrow the edit.</strong></div>
                    <button type="button" data-filter-close aria-label="Close filters"><?= icon('close', 'h-5 w-5') ?></button>
                </div>

                <fieldset class="filter-group" data-filter-group="category">
                    <legend>Category</legend>
                    <?php foreach (['Tees', 'Hoodies', 'Bottoms', 'Outerwear', 'Accessories'] as $category): ?>
                        <label><input type="checkbox" value="<?= e(strtolower($category)) ?>"><span><?= e($category) ?></span></label>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset class="filter-group" data-filter-group="collection">
                    <legend>Collection</legend>
                    <?php foreach (array_keys(catalog_collections()) as $collection): ?>
                        <label><input type="checkbox" value="<?= e(strtolower($collection)) ?>"><span><?= e($collection) ?></span></label>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset class="filter-group" data-filter-group="size">
                    <legend>Size</legend>
                    <div class="filter-sizes">
                        <?php foreach (['S', 'M', 'L', 'XL', 'OS'] as $size): ?>
                            <label><input type="checkbox" value="<?= e(strtolower($size)) ?>"><span><?= e($size) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="filter-group" data-filter-group="color">
                    <legend>Color</legend>
                    <?php foreach ([
                        ['black', 'Black', '#0A0A0A'],
                        ['charcoal', 'Charcoal', '#393939'],
                        ['graphite', 'Graphite', '#555555'],
                        ['bone', 'Bone', '#E7E2D8'],
                        ['white', 'White', '#F1EFE8'],
                    ] as [$value, $label, $hex]): ?>
                        <label class="filter-color"><input type="checkbox" value="<?= e($value) ?>"><i style="--swatch:<?= e($hex) ?>"></i><span><?= e($label) ?></span></label>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset class="filter-group">
                    <legend>Price</legend>
                    <div class="price-filter">
                        <span>Up to</span>
                        <strong data-price-output>PKR 30,000</strong>
                        <input type="range" data-price-range min="4000" max="30000" step="500" value="30000" aria-label="Maximum price">
                    </div>
                </fieldset>

                <fieldset class="filter-group">
                    <legend>Availability</legend>
                    <label><input type="checkbox" data-availability value="in-stock"><span>In stock only</span></label>
                </fieldset>

                <button class="filter-clear" type="button" data-filter-clear>Clear all filters</button>
            </aside>

            <div class="shop-results">
                <div class="product-grid product-grid--shop" data-shop-grid>
                    <?php foreach ($products as $index => $product): ?>
                        <?php render_product_card($product, $index); ?>
                    <?php endforeach; ?>
                </div>
                <div class="shop-empty" data-shop-empty hidden>
                    <p>No pieces match.</p>
                    <span>Clear a filter or search a different term.</span>
                    <button class="text-link" type="button" data-filter-clear>Reset the edit <?= icon('arrow-right', 'h-4 w-4') ?></button>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
