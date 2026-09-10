<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'VANTA — Wear the Attitude';
$pageDescription = 'Shop the first VANTA drop: premium contemporary streetwear built for after dark.';
$currentPage = 'home';
$bodyClass = 'storefront-page';
$headerTheme = 'overlay';

$newDrop = array_slice(catalog_products(), 0, 4);
$featuredProducts = array_slice(catalog_products(), 4, 4);

require __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="storefront-hero" data-storefront-hero aria-labelledby="hero-title">
        <div class="storefront-hero__media" data-hero-media>
            <img
                src="<?= e(asset('images/vanta-foundation-campaign.jpg')) ?>"
                alt="Model wearing an oversized black VANTA streetwear silhouette beside brutalist architecture at night"
                width="1672"
                height="941"
                fetchpriority="high"
            >
        </div>
        <div class="storefront-hero__veil"></div>
        <div class="storefront-hero__grid" aria-hidden="true"></div>

        <div class="storefront-hero__content container-vanta">
            <div class="storefront-hero__meta" data-hero-meta>
                <span>VANTA / DROP 001</span>
                <span>Karachi — 2026</span>
            </div>

            <h1 class="display-title" id="hero-title" aria-label="Wear the attitude">
                <span class="text-mask"><span data-hero-line>Wear</span></span>
                <span class="text-mask"><span data-hero-line>The</span></span>
                <span class="text-mask display-title__accent"><span data-hero-line>Attitude.</span></span>
            </h1>

            <div class="storefront-hero__actions" data-hero-actions>
                <a class="button button--lime magnetic" href="#new-drop">
                    Shop the drop
                    <?= icon('arrow-right', 'h-4 w-4') ?>
                </a>
                <a class="button button--ghost" href="<?= e(url('collection.php?collection=after-dark')) ?>" data-transition-link data-transition-name="AFTER DARK">
                    Explore collection
                    <?= icon('arrow-up-right', 'h-4 w-4') ?>
                </a>
            </div>
        </div>

        <div class="storefront-hero__footer container-vanta" data-hero-footer>
            <p>Premium contemporary streetwear</p>
            <p>Scroll to enter <span aria-hidden="true">↓</span></p>
        </div>
    </section>

    <section class="new-drop section-pad" id="new-drop" aria-labelledby="new-drop-title">
        <div class="container-vanta">
            <div class="section-kicker" data-reveal>
                <span>01</span>
                <p>New Drop / 001</p>
            </div>
            <div class="new-drop__heading" data-reveal>
                <h2 id="new-drop-title">Just landed.</h2>
                <div>
                    <p>Drop 001 introduces the VANTA uniform: heavyweight foundations, disciplined volume, and utility engineered for the city after dark.</p>
                    <a class="text-link" href="<?= e(url('shop.php')) ?>" data-transition-link data-transition-name="SHOP">View all pieces <?= icon('arrow-right', 'h-4 w-4') ?></a>
                </div>
            </div>
            <div class="product-grid product-grid--editorial" data-product-grid>
                <?php foreach ($newDrop as $index => $product): ?>
                    <?php render_product_card($product, $index); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="brand-statement section-pad" aria-labelledby="brand-statement-title">
        <div class="container-vanta">
            <div class="section-kicker section-kicker--dark" data-reveal><span>02</span><p>VANTA / POSITION</p></div>
            <div class="brand-statement__grid">
                <h2 id="brand-statement-title" aria-label="Not made to fit in">
                    <span data-statement-line>Not made</span>
                    <span data-statement-line>to <em>fit in.</em></span>
                </h2>
                <div data-reveal>
                    <p>VANTA rejects disposable uniformity. Every piece is cut to hold space, move with intention, and remain unmistakable without shouting.</p>
                    <span>OWN THE NIGHT / EST. 2026</span>
                </div>
            </div>
        </div>
    </section>

    <section class="featured-collection" aria-labelledby="featured-collection-title">
        <div class="featured-collection__primary" data-image-clip data-parallax-media>
            <img src="<?= e(asset('images/catalog/vanta-after-dark-look.jpg')) ?>" alt="Model in the VANTA After Dark layered bomber look" width="1122" height="1402" loading="lazy">
        </div>
        <div class="featured-collection__content">
            <p class="eyebrow">03 / FEATURED COLLECTION</p>
            <h2 id="featured-collection-title">After<br><em>dark.</em></h2>
            <p>Structured outer layers. Weight you can feel. A collection calibrated for the shift from late evening to first light.</p>
            <div><span>04 PIECES</span><span>DROP 001</span></div>
            <a class="button button--lime magnetic" href="<?= e(url('collection.php?collection=after-dark')) ?>" data-transition-link data-transition-name="AFTER DARK">Enter After Dark <?= icon('arrow-up-right', 'h-4 w-4') ?></a>
        </div>
        <div class="featured-collection__secondary" data-image-clip>
            <img src="<?= e(asset('images/catalog/vanta-essential-look.jpg')) ?>" alt="" width="1122" height="1402" loading="lazy">
        </div>
    </section>

    <section class="featured-products section-pad" aria-labelledby="featured-products-title">
        <div class="container-vanta">
            <div class="section-kicker" data-reveal><span>04</span><p>Selected / VANTA edit</p></div>
            <div class="featured-products__heading" data-reveal>
                <h2 id="featured-products-title">The night edit.</h2>
                <p>Four pieces to build the first VANTA uniform—from heavyweight base layer to final outer shell.</p>
            </div>
            <div class="product-grid" data-product-grid>
                <?php foreach ($featuredProducts as $index => $product): ?>
                    <?php render_product_card($product, $index); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="campaign-block" aria-labelledby="campaign-title">
        <div class="campaign-block__media" data-campaign-media>
            <img src="<?= e(asset('images/catalog/vanta-essential-look.jpg')) ?>" alt="VANTA Drop 001 campaign look in a brutalist night passage" width="1122" height="1402" loading="lazy">
        </div>
        <div class="campaign-block__veil"></div>
        <div class="campaign-block__content container-vanta">
            <p class="eyebrow">CAMPAIGN 001 / KARACHI</p>
            <h2 id="campaign-title">City<br>after<br><em>midnight.</em></h2>
            <div>
                <p>A study in concrete, shadow, and the silhouettes that only make sense when the streets empty out.</p>
                <a class="text-link" href="<?= e(url('collection.php?collection=new-drop')) ?>" data-transition-link data-transition-name="NEW DROP">View the campaign edit <?= icon('arrow-up-right', 'h-4 w-4') ?></a>
            </div>
        </div>
    </section>

    <section class="collection-discovery section-pad" id="collections" aria-labelledby="collections-title">
        <div class="container-vanta">
            <div class="section-kicker section-kicker--dark" data-reveal><span>05</span><p>Explore / Collections</p></div>
            <div class="collection-discovery__heading" data-reveal>
                <h2 id="collections-title">Choose your<br>frequency.</h2>
                <p>Permanent foundations, night-specific layers, and releases that disappear without warning.</p>
            </div>
            <div class="collection-panels">
                <?php
                $panelImages = [
                    'new-drop' => 'images/catalog/vanta-essential-look.jpg',
                    'essentials' => 'images/catalog/vanta-accessories-still-life.jpg',
                    'after-dark' => 'images/catalog/vanta-after-dark-look.jpg',
                    'limited' => 'images/vanta-foundation-campaign.jpg',
                ];
                ?>
                <?php foreach (catalog_collections() as $collectionName => $collection): ?>
                    <a class="collection-panel collection-panel--<?= e($collection['slug']) ?>" href="<?= e(url('collection.php?collection=' . $collection['slug'])) ?>" data-transition-link data-transition-name="<?= e($collection['title']) ?>">
                        <img src="<?= e(asset($panelImages[$collection['slug']])) ?>" alt="" width="1122" height="1402" loading="lazy">
                        <span class="collection-panel__veil"></span>
                        <span class="collection-panel__index"><?= e(str_pad((string) (array_search($collectionName, array_keys(catalog_collections()), true) + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                        <span class="collection-panel__content"><strong><?= e($collection['title']) ?></strong><small><?= count(catalog_products_for_collection($collectionName)) ?> pieces</small></span>
                        <i aria-hidden="true">↗</i>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="storefront-marquee" aria-label="Limited drop message">
        <div data-horizontal-text>
            <span>LIMITED DROP — NO RESTOCK — VANTA — EST. 2026 —&nbsp;</span>
            <span aria-hidden="true">LIMITED DROP — NO RESTOCK — VANTA — EST. 2026 —&nbsp;</span>
        </div>
    </section>

    <section class="newsletter section-pad" aria-labelledby="newsletter-title">
        <div class="container-vanta newsletter__grid">
            <div>
                <p class="eyebrow">PRIVATE FREQUENCY / VANTA</p>
                <h2 id="newsletter-title">Enter the<br>VANTA world.</h2>
            </div>
            <form class="newsletter-form" data-newsletter-form novalidate>
                <p>Early access to drops, campaign edits, and restock decisions. No noise.</p>
                <label>
                    <span class="sr-only">Email address</span>
                    <input type="email" name="email" placeholder="EMAIL ADDRESS" autocomplete="email" required>
                    <button type="submit" aria-label="Subscribe"><?= icon('arrow-right', 'h-5 w-5') ?></button>
                </label>
                <span class="newsletter-form__feedback" data-newsletter-feedback aria-live="polite"></span>
            </form>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
