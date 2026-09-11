    <footer class="site-footer">
        <div class="site-footer__top">
            <p class="eyebrow">VANTA / EST. 2026</p>
            <p class="site-footer__statement">Wear the<br>attitude.</p>
            <a class="text-link magnetic" href="<?= e(url('shop.php')) ?>" data-transition-link data-transition-name="SHOP">
                Shop Drop 001 <?= icon('arrow-up-right', 'h-4 w-4') ?>
            </a>
        </div>
        <div class="site-footer__links">
            <div>
                <p>Shop</p>
                <a href="<?= e(url('shop.php')) ?>" data-transition-link data-transition-name="SHOP">All pieces</a>
                <a href="<?= e(url('collection.php?collection=new-drop')) ?>" data-transition-link data-transition-name="NEW DROP">New Drop</a>
                <a href="<?= e(url('index.php#collections')) ?>">Collections</a>
                <a href="<?= e(url(current_user() ? 'account/index.php' : 'login.php')) ?>">Account</a>
            </div>
            <div>
                <p>Customer care</p>
                <a href="<?= e(url('product.php?slug=vanta-oversized-essential-tee#shipping')) ?>" data-transition-link data-transition-name="PRODUCT">Shipping</a>
                <a href="<?= e(url('product.php?slug=vanta-oversized-essential-tee#shipping')) ?>" data-transition-link data-transition-name="PRODUCT">Returns</a>
                <a href="<?= e(url('product.php?slug=vanta-oversized-essential-tee#size-guide')) ?>" data-transition-link data-transition-name="PRODUCT">Size guide</a>
                <a href="mailto:care@vanta.example">Contact</a>
            </div>
            <div>
                <p>Legal</p>
                <a href="#" data-foundation-notice="The final privacy policy is scheduled for Phase 5.">Privacy</a>
                <a href="#" data-foundation-notice="The final terms are scheduled for Phase 5.">Terms</a>
            </div>
            <div>
                <p>Social</p>
                <a href="#" data-foundation-notice="Instagram will be connected before launch.">Instagram</a>
                <a href="#" data-foundation-notice="TikTok will be connected before launch.">TikTok</a>
            </div>
        </div>
        <div class="site-footer__wordmark" aria-label="VANTA">VANTA</div>
        <div class="site-footer__meta">
            <span>&copy; <?= date('Y') ?> VANTA</span>
            <span>Premium streetwear / Pakistan</span>
            <a href="#main-content">Back to top &uarr;</a>
        </div>
    </footer>

    <script src="<?= e(asset('vendor/gsap/gsap.min.js')) ?>"></script>
    <script src="<?= e(asset('vendor/gsap/ScrollTrigger.min.js')) ?>"></script>
    <script src="<?= e(asset('vendor/lenis/lenis.min.js')) ?>"></script>
    <script src="<?= e(asset('js/motion.js')) ?>" defer></script>
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
    <script src="<?= e(asset('js/storefront.js')) ?>" defer></script>
</body>
</html>
