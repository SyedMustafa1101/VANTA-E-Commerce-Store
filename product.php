<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$slug = strtolower(trim((string) ($_GET['slug'] ?? 'vanta-oversized-essential-tee')));
$product = catalog_product_by_slug($slug);

if ($product === null) {
    http_response_code(404);
    $pageTitle = 'Product Not Found — VANTA';
    $pageDescription = 'The requested VANTA product could not be found.';
    $currentPage = 'product';
    $bodyClass = 'not-found-page';
    $headerTheme = 'solid';
    require __DIR__ . '/includes/header.php';
    ?>
    <main id="main-content" class="not-found">
        <div class="container-vanta">
            <p class="eyebrow">404 / PRODUCT</p>
            <h1>Gone without restock.</h1>
            <p>That piece is no longer in this edit.</p>
            <a class="button button--lime" href="<?= e(url('shop.php')) ?>" data-transition-link data-transition-name="SHOP">Return to shop <?= icon('arrow-right', 'h-4 w-4') ?></a>
        </div>
    </main>
    <?php
    require __DIR__ . '/includes/footer.php';
    return;
}

$pageTitle = $product['name'] . ' — VANTA';
$pageDescription = $product['description'];
$currentPage = 'product';
$bodyClass = 'product-page';
$headerTheme = 'solid';
$defaultColor = $product['colors'][0];
$sizes = product_sizes($product);
$defaultSize = in_array('M', $sizes, true) ? 'M' : $sizes[0];
$related = array_values(array_filter(catalog_products(), static fn (array $item): bool => $item['id'] !== $product['id']));
$related = array_slice($related, 0, 4);

require __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="product-main">
    <div class="product-layout">
        <section class="product-gallery" aria-label="<?= e($product['name']) ?> gallery">
            <?php foreach ($product['images'] as $index => $image): ?>
                <figure class="product-gallery__item<?= $index === 0 ? ' product-gallery__item--lead' : '' ?>">
                    <img data-gallery-image src="<?= e(asset($image)) ?>" alt="<?= $index === 0 ? e($product['name'] . ' in ' . $defaultColor['name']) : '' ?>" width="1122" height="1402" <?= $index > 0 ? 'loading="lazy"' : 'fetchpriority="high"' ?>>
                    <span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?> / <?= e(str_pad((string) count($product['images']), 2, '0', STR_PAD_LEFT)) ?></span>
                </figure>
            <?php endforeach; ?>
        </section>

        <aside class="product-buy">
            <div class="product-buy__inner">
                <div class="product-buy__meta">
                    <a href="<?= e(url('collection.php?collection=' . catalog_collections()[$product['collection']]['slug'])) ?>" data-transition-link data-transition-name="<?= e($product['collection']) ?>"><?= e($product['collection']) ?></a>
                    <?php if ($product['label']): ?><span><?= e($product['label']) ?></span><?php endif; ?>
                </div>

                <h1><?= e($product['name']) ?></h1>
                <p class="product-buy__price">
                    <?php if ($product['sale_price']): ?>
                        <strong><?= e(format_pkr($product['sale_price'])) ?></strong><del><?= e(format_pkr($product['price'])) ?></del>
                    <?php else: ?>
                        <strong><?= e(format_pkr($product['price'])) ?></strong>
                    <?php endif; ?>
                </p>
                <p class="product-buy__description"><?= e($product['description']) ?></p>

                <form class="product-form" data-product-form data-product-id="<?= e((string) $product['id']) ?>">
                    <fieldset class="product-option product-option--color">
                        <legend>Color <span data-selected-color><?= e($defaultColor['name']) ?></span></legend>
                        <div class="product-colors">
                            <?php foreach ($product['colors'] as $index => $color): ?>
                                <label>
                                    <input type="radio" name="color" value="<?= e($color['id']) ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                    <i style="--swatch:<?= e($color['hex']) ?>"></i>
                                    <span><?= e($color['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <fieldset class="product-option product-option--size">
                        <legend>Size <button type="button" data-size-guide-open>Size guide</button></legend>
                        <div class="product-sizes">
                            <?php foreach ($sizes as $size): ?>
                                <label data-size-option class="<?= ($product['stock'][$defaultColor['id']][$size] ?? 0) < 1 ? 'is-sold-out' : '' ?>">
                                    <input type="radio" name="size" value="<?= e($size) ?>" <?= $size === $defaultSize ? 'checked' : '' ?>>
                                    <span><?= e($size) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <p class="product-stock" data-stock-state aria-live="polite"><i></i> Checking availability</p>

                    <div class="product-form__actions">
                        <div class="product-quantity" aria-label="Quantity">
                            <button type="button" data-quantity-action="decrease" aria-label="Decrease quantity"><?= icon('minus', 'h-4 w-4') ?></button>
                            <span data-quantity-value>01</span>
                            <button type="button" data-quantity-action="increase" aria-label="Increase quantity"><?= icon('plus', 'h-4 w-4') ?></button>
                        </div>
                        <button class="button button--lime product-add" type="submit" data-add-to-bag>Add to bag</button>
                        <button class="product-wishlist" type="button" data-wishlist-toggle="<?= e((string) $product['id']) ?>" aria-label="Add <?= e($product['name']) ?> to wishlist" aria-pressed="false"><?= icon('heart', 'h-5 w-5') ?></button>
                    </div>
                </form>

                <div class="product-accordions">
                    <details>
                        <summary>Details <span>+</span></summary>
                        <p>Designed in Pakistan. Relaxed unisex fit with reinforced high-stress seams and tonal finishing.</p>
                    </details>
                    <details>
                        <summary>Materials <span>+</span></summary>
                        <p><?= e($product['materials']) ?></p>
                    </details>
                    <details>
                        <summary>Care <span>+</span></summary>
                        <p><?= e($product['care']) ?></p>
                    </details>
                    <details id="shipping">
                        <summary>Shipping &amp; returns <span>+</span></summary>
                        <p>Complimentary delivery across Pakistan over PKR 15,000. Unworn pieces may be returned within 14 days. Final-sale items are excluded.</p>
                    </details>
                </div>
            </div>
        </aside>
    </div>

    <section class="related-products section-pad">
        <div class="container-vanta">
            <div class="section-kicker" data-reveal><span>02</span><p>Continue the uniform</p></div>
            <div class="related-products__heading" data-reveal>
                <h2>Wear it with.</h2>
                <a class="text-link" href="<?= e(url('shop.php')) ?>" data-transition-link data-transition-name="SHOP">Shop all <?= icon('arrow-right', 'h-4 w-4') ?></a>
            </div>
            <div class="product-grid" data-product-grid>
                <?php foreach ($related as $index => $item): ?>
                    <?php render_product_card($item, $index); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<dialog class="size-guide" id="size-guide" data-size-guide>
    <div class="size-guide__top">
        <div><p class="eyebrow">VANTA / FIT</p><h2>Size guide.</h2></div>
        <button type="button" data-size-guide-close aria-label="Close size guide"><?= icon('close', 'h-5 w-5') ?></button>
    </div>
    <p>VANTA garments are intentionally relaxed. Choose your usual size for the designed silhouette.</p>
    <div class="size-table" role="table" aria-label="Garment measurements in centimeters">
        <div role="row"><strong role="columnheader">CM</strong><strong role="columnheader">S</strong><strong role="columnheader">M</strong><strong role="columnheader">L</strong><strong role="columnheader">XL</strong></div>
        <div role="row"><span role="rowheader">Chest</span><span>56</span><span>59</span><span>62</span><span>65</span></div>
        <div role="row"><span role="rowheader">Length</span><span>70</span><span>72</span><span>74</span><span>76</span></div>
        <div role="row"><span role="rowheader">Sleeve</span><span>24</span><span>25</span><span>26</span><span>27</span></div>
    </div>
</dialog>

<?php require __DIR__ . '/includes/footer.php'; ?>
