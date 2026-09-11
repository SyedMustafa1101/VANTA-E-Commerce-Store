<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$slug = strtolower(trim((string) ($_GET['collection'] ?? 'new-drop')));
$collection = catalog_collection_by_slug($slug);

if ($collection === null) {
    http_response_code(404);
    $pageTitle = 'Collection Not Found — VANTA';
    $pageDescription = 'The requested VANTA collection could not be found.';
    $currentPage = 'collections';
    $bodyClass = 'not-found-page';
    $headerTheme = 'solid';
    require __DIR__ . '/includes/header.php';
    ?>
    <main id="main-content" class="not-found">
        <div class="container-vanta">
            <p class="eyebrow">404 / COLLECTION</p>
            <h1>Lost after dark.</h1>
            <p>That collection has moved or never entered the drop.</p>
            <a class="button button--lime" href="<?= e(url('shop.php')) ?>" data-transition-link data-transition-name="SHOP">Return to shop <?= icon('arrow-right', 'h-4 w-4') ?></a>
        </div>
    </main>
    <?php
    require __DIR__ . '/includes/footer.php';
    return;
}

$products = catalog_products_for_collection($collection['title']);
$pageTitle = $collection['title'] . ' — VANTA';
$pageDescription = $collection['copy'];
$currentPage = 'collections';
$bodyClass = 'collection-page collection-page--' . $slug;
$headerTheme = in_array($slug, ['new-drop', 'after-dark', 'limited'], true) ? 'overlay' : 'solid';
$images = [$collection['primary_image'], $collection['secondary_image']];

require __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="collection-main">
    <section class="collection-hero collection-hero--<?= e($slug) ?>" aria-labelledby="collection-title">
        <div class="collection-hero__media" data-collection-media>
            <img src="<?= e(asset($images[0])) ?>" alt="<?= e($collection['title']) ?> VANTA collection campaign" width="1122" height="1402" fetchpriority="high">
        </div>
        <div class="collection-hero__content container-vanta">
            <div class="collection-hero__meta">
                <span><?= e($collection['eyebrow']) ?></span>
                <span><?= count($products) ?> PIECES / PKR</span>
            </div>
            <h1 id="collection-title">
                <?php foreach (explode(' ', $collection['title']) as $word): ?>
                    <span class="text-mask"><span data-collection-line><?= e($word) ?></span></span>
                <?php endforeach; ?>
            </h1>
            <div class="collection-hero__summary">
                <p><?= e($collection['copy']) ?></p>
                <a class="button <?= $slug === 'essentials' ? 'button--outline-dark' : 'button--lime' ?>" href="#collection-products">Shop the collection <?= icon('arrow-right', 'h-4 w-4') ?></a>
            </div>
        </div>
    </section>

    <section class="collection-story">
        <div class="container-vanta collection-story__grid">
            <div class="collection-story__copy" data-reveal>
                <p class="eyebrow">VANTA / <?= e($collection['title']) ?></p>
                <?php if ($slug === 'new-drop'): ?>
                    <h2>First release.<br>No hesitation.</h2>
                    <p>Built as one compact wardrobe: sharp layers, relaxed utility, and fabric weight you can feel before you touch it.</p>
                <?php elseif ($slug === 'essentials'): ?>
                    <h2>Repeat<br>without routine.</h2>
                    <p>Permanent pieces with exacting proportion. Quiet enough for every day, deliberate enough to never disappear.</p>
                <?php elseif ($slug === 'after-dark'): ?>
                    <h2>Designed for<br>00:01.</h2>
                    <p>Night changes how fabric, volume, and movement read. This collection begins where daylight ends.</p>
                <?php else: ?>
                    <h2>When it leaves,<br>it stays gone.</h2>
                    <p>Numbered runs, controlled quantities, and no restock calendar. Ownership starts with timing.</p>
                <?php endif; ?>
            </div>
            <figure class="collection-story__image" data-image-clip data-parallax-media>
                <img src="<?= e(asset($images[1])) ?>" alt="" width="1122" height="1402" loading="lazy">
            </figure>
        </div>
    </section>

    <section class="collection-products section-pad" id="collection-products">
        <div class="container-vanta">
            <div class="section-kicker" data-reveal>
                <span>02</span><p>The <?= e(strtolower($collection['title'])) ?> edit</p>
            </div>
            <div class="product-grid product-grid--collection" data-product-grid>
                <?php foreach ($products as $index => $product): ?>
                    <?php render_product_card($product, $index); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <nav class="collection-switcher" aria-label="Explore other collections">
        <?php foreach (catalog_collections() as $item): ?>
            <a class="<?= $item['slug'] === $slug ? 'is-current' : '' ?>" href="<?= e(url('collection.php?collection=' . $item['slug'])) ?>" data-transition-link data-transition-name="<?= e($item['title']) ?>">
                <span><?= e($item['title']) ?></span><i aria-hidden="true">↗</i>
            </a>
        <?php endforeach; ?>
    </nav>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
