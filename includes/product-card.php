<?php

declare(strict_types=1);

/** @param array<string, mixed> $product */
function render_product_card(array $product, int $index = 0): void
{
    $primaryImage = $product['images'][0];
    $secondaryImage = $product['images'][1] ?? $primaryImage;
    $isAvailable = product_available($product);
    $displayPrice = $product['sale_price'] ?? $product['price'];
    $sizes = product_sizes($product);
    $searchText = implode(' ', [
        $product['name'],
        $product['category'],
        $product['collection'],
        implode(' ', $product['keywords']),
    ]);
    ?>
    <article
        class="product-card"
        data-product-card
        data-product-id="<?= e((string) $product['id']) ?>"
        data-name="<?= e(strtolower($product['name'])) ?>"
        data-search="<?= e(strtolower($searchText)) ?>"
        data-category="<?= e(strtolower($product['category'])) ?>"
        data-collection="<?= e(strtolower($product['collection'])) ?>"
        data-colors="<?= e(strtolower(implode(',', array_column($product['colors'], 'name')))) ?>"
        data-sizes="<?= e(strtolower(implode(',', $sizes))) ?>"
        data-price="<?= e((string) $displayPrice) ?>"
        data-popularity="<?= e((string) $product['popularity']) ?>"
        data-available="<?= $isAvailable ? 'true' : 'false' ?>"
        style="--card-index: <?= $index ?>"
    >
        <div class="product-card__media">
            <a href="<?= e(url('product.php?slug=' . rawurlencode($product['slug']))) ?>" data-transition-link data-transition-name="PRODUCT" aria-label="View <?= e($product['name']) ?>">
                <img class="product-card__image product-card__image--primary" src="<?= e(asset($primaryImage)) ?>" alt="<?= e($product['name']) ?> in <?= e($product['colors'][0]['name']) ?>" width="1122" height="1402" <?= $index > 1 ? 'loading="lazy"' : '' ?>>
                <img class="product-card__image product-card__image--secondary" src="<?= e(asset($secondaryImage)) ?>" alt="" width="1122" height="1402" loading="lazy" aria-hidden="true">
            </a>

            <?php if (!empty($product['label'])): ?>
                <span class="product-card__label<?= $product['label'] === 'SOLD OUT' ? ' product-card__label--muted' : '' ?>"><?= e($product['label']) ?></span>
            <?php endif; ?>

            <button class="product-card__wishlist" type="button" data-wishlist-toggle="<?= e((string) $product['id']) ?>" aria-label="Add <?= e($product['name']) ?> to wishlist" aria-pressed="false">
                <?= icon('heart', 'h-5 w-5') ?>
            </button>

            <?php if ($isAvailable): ?>
                <button class="product-card__quick" type="button" data-quick-add="<?= e((string) $product['id']) ?>" aria-label="Quick add <?= e($product['name']) ?> to bag">
                    Quick add <?= icon('plus', 'h-4 w-4') ?>
                </button>
            <?php else: ?>
                <span class="product-card__quick product-card__quick--disabled">Unavailable</span>
            <?php endif; ?>
        </div>

        <div class="product-card__info">
            <div>
                <p class="product-card__collection"><?= e($product['collection']) ?></p>
                <h3><a href="<?= e(url('product.php?slug=' . rawurlencode($product['slug']))) ?>" data-transition-link data-transition-name="PRODUCT"><?= e($product['name']) ?></a></h3>
            </div>
            <p class="product-card__price">
                <?php if ($product['sale_price']): ?>
                    <span><?= e(format_pkr($product['sale_price'])) ?></span>
                    <del><?= e(format_pkr($product['price'])) ?></del>
                <?php else: ?>
                    <span><?= e(format_pkr($product['price'])) ?></span>
                <?php endif; ?>
            </p>
            <div class="product-card__colors" aria-label="Available colors">
                <?php foreach ($product['colors'] as $color): ?>
                    <span style="--swatch: <?= e($color['hex']) ?>" title="<?= e($color['name']) ?>"><i class="sr-only"><?= e($color['name']) ?></i></span>
                <?php endforeach; ?>
            </div>
        </div>
    </article>
    <?php
}
