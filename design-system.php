<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'VANTA — Design System';
$pageDescription = 'The visual and interaction foundation for the VANTA streetwear experience.';
$currentPage = 'design-system';
$bodyClass = 'system-page';
$headerTheme = 'solid';

require __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="system-main">
    <section class="system-hero">
        <div class="container-vanta">
            <div class="system-hero__meta" data-reveal>
                <p>VANTA / DIGITAL SYSTEM</p>
                <p>VERSION 01.0 / 2026</p>
            </div>
            <h1 data-reveal>Designed for<br><em>after dark.</em></h1>
            <div class="system-hero__summary" data-reveal>
                <p>A living reference for VANTA’s type, palette, controls, spacing, states, and motion. Built to keep every future surface expressive and coherent.</p>
                <a class="text-link" href="<?= e(url('index.php')) ?>" data-transition-link data-transition-name="HOME">
                    Return to foundation <?= icon('arrow-up-right', 'h-4 w-4') ?>
                </a>
            </div>
        </div>
    </section>

    <section class="system-section" id="tokens">
        <div class="container-vanta">
            <div class="system-heading" data-reveal>
                <span>01</span>
                <div>
                    <p>Color tokens</p>
                    <h2>Controlled contrast.</h2>
                </div>
            </div>

            <div class="swatch-grid">
                <article class="swatch swatch--black" data-reveal>
                    <span>Near Black</span><code>#0A0A0A</code>
                </article>
                <article class="swatch swatch--jet" data-reveal>
                    <span>Jet Black</span><code>#0F0F0F</code>
                </article>
                <article class="swatch swatch--dark" data-reveal>
                    <span>Dark Gray</span><code>#2A2A2A</code>
                </article>
                <article class="swatch swatch--light" data-reveal>
                    <span>Light Gray</span><code>#CFCFCF</code>
                </article>
                <article class="swatch swatch--white" data-reveal>
                    <span>Off White</span><code>#F5F5F5</code>
                </article>
                <article class="swatch swatch--lime" data-reveal>
                    <span>Signal Lime</span><code>#B7FF2A</code>
                </article>
            </div>
            <p class="system-note" data-reveal>Lime is reserved for action, selection, and meaningful status. It is a signal—not a surface.</p>
        </div>
    </section>

    <section class="system-section type-section">
        <div class="container-vanta">
            <div class="system-heading" data-reveal>
                <span>02</span>
                <div>
                    <p>Typography</p>
                    <h2>Voice before decoration.</h2>
                </div>
            </div>

            <div class="type-specimens">
                <article class="type-specimen type-specimen--display" data-reveal>
                    <div><span>Display / Barlow Condensed</span><span>800 / -0.045em</span></div>
                    <p>OWN THE<br><em>NIGHT.</em></p>
                </article>
                <article class="type-specimen type-specimen--body" data-reveal>
                    <div><span>Interface / Manrope</span><span>400—700</span></div>
                    <p>Premium streetwear engineered with an uncompromising eye for material, proportion, and movement.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="system-section" id="components">
        <div class="container-vanta">
            <div class="system-heading" data-reveal>
                <span>03</span>
                <div>
                    <p>Components</p>
                    <h2>Sharp, quiet, responsive.</h2>
                </div>
            </div>

            <div class="component-grid">
                <article class="component-panel component-panel--dark" data-reveal>
                    <p class="component-label">Actions / Dark surface</p>
                    <div class="component-stack">
                        <button class="button button--lime magnetic" type="button">Add to bag <?= icon('plus', 'h-4 w-4') ?></button>
                        <button class="button button--outline magnetic" type="button">View collection <?= icon('arrow-right', 'h-4 w-4') ?></button>
                        <button class="icon-button" type="button" aria-label="Add to wishlist"><?= icon('heart', 'h-5 w-5') ?></button>
                    </div>
                    <p class="component-caption">Square geometry. Visible focus. Tactile press response.</p>
                </article>

                <article class="component-panel component-panel--light" data-reveal>
                    <p class="component-label">Selection / Product options</p>
                    <fieldset class="choice-group">
                        <legend>Size <span>Select one</span></legend>
                        <div class="choice-row">
                            <label><input type="radio" name="size"><span>S</span></label>
                            <label><input type="radio" name="size" checked><span>M</span></label>
                            <label><input type="radio" name="size"><span>L</span></label>
                            <label class="is-disabled"><input type="radio" name="size" disabled><span>XL</span></label>
                        </div>
                    </fieldset>
                    <fieldset class="choice-group choice-group--color">
                        <legend>Color <span>Obsidian</span></legend>
                        <div class="color-row">
                            <label><input type="radio" name="color" checked><span style="--swatch:#0A0A0A"></span><i>Obsidian</i></label>
                            <label><input type="radio" name="color"><span style="--swatch:#EAE8E2"></span><i>Bone</i></label>
                        </div>
                    </fieldset>
                </article>

                <article class="component-panel component-panel--form" data-reveal>
                    <p class="component-label">Form / Core states</p>
                    <form class="form-preview" action="#" onsubmit="return false">
                        <label>
                            <span>Email address</span>
                            <input type="email" placeholder="you@example.com">
                        </label>
                        <label class="has-value">
                            <span>Discount code</span>
                            <input type="text" value="AFTERDARK">
                            <em>Applied</em>
                        </label>
                        <label class="has-error">
                            <span>Postal code</span>
                            <input type="text" aria-describedby="postal-error" value="12">
                            <small id="postal-error">Enter a valid postal code.</small>
                        </label>
                    </form>
                </article>

                <article class="component-panel component-panel--feedback" data-reveal>
                    <p class="component-label">Feedback / Status</p>
                    <div class="feedback-row"><span class="status-chip"><i></i> In stock</span><small>Ready to ship</small></div>
                    <div class="feedback-row"><span class="status-chip status-chip--low"><i></i> Low stock</span><small>Only 3 left</small></div>
                    <div class="feedback-row"><span class="status-chip status-chip--out"><i></i> Sold out</span><small>Notify me</small></div>
                    <button class="quantity-control" type="button" aria-label="Quantity preview">
                        <?= icon('minus', 'h-4 w-4') ?><span>01</span><?= icon('plus', 'h-4 w-4') ?>
                    </button>
                </article>
            </div>
        </div>
    </section>

    <section class="system-section motion-section" id="motion">
        <div class="container-vanta">
            <div class="system-heading" data-reveal>
                <span>04</span>
                <div>
                    <p>Motion language</p>
                    <h2>Fast. Physical. Restrained.</h2>
                </div>
            </div>
            <div class="motion-grid">
                <article data-reveal>
                    <span>01 / REVEAL</span>
                    <div class="motion-demo motion-demo--reveal"><i></i></div>
                    <p>Clipped vertical entrances for type and imagery.</p>
                </article>
                <article data-reveal>
                    <span>02 / SHIFT</span>
                    <div class="motion-demo motion-demo--shift"><i></i></div>
                    <p>Small directional movement clarifies interaction.</p>
                </article>
                <article data-reveal>
                    <span>03 / SIGNAL</span>
                    <div class="motion-demo motion-demo--signal"><i></i></div>
                    <p>Lime confirms a selected or successful state.</p>
                </article>
            </div>
            <p class="system-note" data-reveal>When reduced motion is preferred, transitions become immediate and all information remains available.</p>
        </div>
    </section>

    <section class="system-close">
        <div class="container-vanta" data-reveal>
            <span>FOUNDATION / COMPLETE</span>
            <h2>Ready for<br><em>the drop.</em></h2>
            <p>The next approved phase turns this system into the animated customer storefront.</p>
            <a class="button button--lime magnetic" href="<?= e(url('index.php')) ?>" data-transition-link data-transition-name="HOME">
                Back to VANTA <?= icon('arrow-up-right', 'h-4 w-4') ?>
            </a>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

