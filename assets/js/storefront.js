(() => {
    'use strict';

    const ui = window.vantaUI || {};
    const reducedMotion = ui.reducedMotion || window.matchMedia('(prefers-reduced-motion: reduce)');
    const parseJsonNode = (selector, fallback) => {
        try {
            return JSON.parse(document.querySelector(selector)?.textContent || JSON.stringify(fallback));
        } catch {
            return fallback;
        }
    };
    const config = parseJsonNode('#vanta-config', { baseUrl: '', csrfToken: '', authenticated: false });
    const catalog = parseJsonNode('#vanta-catalog', []);
    const productById = new Map(catalog.map((product) => [String(product.id), product]));
    const searchOverlay = document.querySelector('[data-search-overlay]');
    const cartDrawer = document.querySelector('[data-cart-drawer]');
    const wishlistDrawer = document.querySelector('[data-wishlist-drawer]');
    const drawerBackdrop = document.querySelector('[data-drawer-backdrop]');
    const searchInput = document.querySelector('[data-search-input]');
    let activeLayer = null;
    let activeTrigger = null;
    let cart = { lines: [], subtotal: 0, count: 0 };
    let wishlist = [];

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    }

    function formatPkr(value) {
        return 'PKR ' + Number(value || 0).toLocaleString('en-PK', { maximumFractionDigits: 0 });
    }

    async function api(path, data = null) {
        const options = { credentials: 'same-origin', headers: { Accept: 'application/json' } };
        if (data !== null) {
            options.method = 'POST';
            options.headers['Content-Type'] = 'application/json';
            options.headers['X-CSRF-Token'] = config.csrfToken;
            options.body = JSON.stringify(data);
        }
        const response = await fetch((config.baseUrl || '') + '/api/' + path, options);
        let payload;
        try {
            payload = await response.json();
        } catch {
            payload = { ok: false, message: 'The store returned an unreadable response.' };
        }
        payload.status = response.status;
        return payload;
    }

    function getFocusable(container) {
        if (ui.getFocusable) return ui.getFocusable(container);
        return [...container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')];
    }

    function stopPageScroll() {
        document.body.classList.add('overlay-open');
        window.vantaLenis?.stop();
    }

    function resumePageScroll() {
        document.body.classList.remove('overlay-open');
        window.vantaLenis?.start();
    }

    function usesBackdrop(layer) {
        return layer === cartDrawer || layer === wishlistDrawer;
    }

    function openLayer(layer, trigger, focusTarget) {
        if (!layer) return;
        if (activeLayer && activeLayer !== layer) closeLayer(activeLayer, false, true);
        ui.closeMenu?.({ returnFocus: false, immediate: true });
        activeLayer = layer;
        activeTrigger = trigger || document.activeElement;
        layer.setAttribute('aria-hidden', 'false');
        layer.classList.add('is-open');
        drawerBackdrop?.classList.toggle('is-visible', usesBackdrop(layer));
        stopPageScroll();
        window.setTimeout(() => (focusTarget || getFocusable(layer)[0])?.focus({ preventScroll: true }), reducedMotion.matches ? 0 : 360);
    }

    function closeLayer(layer = activeLayer, returnFocus = true, immediate = false) {
        if (!layer) return;
        const trigger = activeTrigger;
        layer.classList.remove('is-open');
        drawerBackdrop?.classList.remove('is-visible');
        const finish = () => {
            layer.setAttribute('aria-hidden', 'true');
            if (activeLayer === layer) {
                activeLayer = null;
                activeTrigger = null;
                resumePageScroll();
            }
            if (returnFocus && trigger instanceof HTMLElement) trigger.focus({ preventScroll: true });
        };
        if (immediate || reducedMotion.matches) finish();
        else window.setTimeout(finish, 430);
    }

    function renderCart() {
        document.querySelectorAll('[data-cart-count]').forEach((node) => { node.textContent = String(cart.count || 0); });
        const empty = document.querySelector('[data-cart-empty]');
        const footer = document.querySelector('[data-cart-footer]');
        const lines = document.querySelector('[data-cart-lines]');
        if (empty) empty.hidden = cart.lines.length > 0;
        if (footer) footer.hidden = cart.lines.length === 0;
        const subtotal = document.querySelector('[data-cart-subtotal]');
        if (subtotal) subtotal.textContent = formatPkr(cart.subtotal);
        if (lines) {
            lines.innerHTML = cart.lines.map((line) =>
                '<article class="cart-line" data-cart-key="' + line.variant_id + '">' +
                '<a class="cart-line__image" href="' + escapeHtml(line.url) + '"><img src="' + escapeHtml(line.image) + '" alt="" width="220" height="275"></a>' +
                '<div class="cart-line__body"><div><p>' + escapeHtml(line.collection) + '</p><h3><a href="' + escapeHtml(line.url) + '">' + escapeHtml(line.name) + '</a></h3>' +
                '<span>' + escapeHtml(line.color) + ' / ' + escapeHtml(line.size) + ' / ' + escapeHtml(line.sku) + '</span></div>' +
                '<strong>' + formatPkr(line.line_total) + '</strong>' +
                (!line.available ? '<small class="line-warning">Stock changed — review before checkout.</small>' : '') +
                '<div class="cart-line__actions"><div class="mini-quantity" aria-label="Quantity for ' + escapeHtml(line.name) + '">' +
                '<button type="button" data-cart-action="decrease" aria-label="Decrease quantity">−</button><span>' + String(line.quantity).padStart(2, '0') + '</span>' +
                '<button type="button" data-cart-action="increase" aria-label="Increase quantity">+</button></div><button type="button" data-cart-action="remove">Remove</button></div></div></article>'
            ).join('');
        }
        renderCartPage();
    }

    function renderCartPage() {
        const pageLines = document.querySelector('[data-cart-page-lines]');
        if (!pageLines) return;
        pageLines.innerHTML = cart.lines.map((line) =>
            '<article data-cart-key="' + line.variant_id + '"><a href="' + escapeHtml(line.url) + '"><img src="' + escapeHtml(line.image) + '" alt="" width="220" height="275"></a>' +
            '<div><p>' + escapeHtml(line.collection) + '</p><h2><a href="' + escapeHtml(line.url) + '">' + escapeHtml(line.name) + '</a></h2>' +
            '<span>' + escapeHtml(line.color) + ' / ' + escapeHtml(line.size) + ' / ' + escapeHtml(line.sku) + '</span>' +
            '<div class="cart-line__actions"><div class="mini-quantity"><button type="button" data-cart-action="decrease">−</button><span>' + String(line.quantity).padStart(2, '0') + '</span><button type="button" data-cart-action="increase">+</button></div><button type="button" data-cart-action="remove">Remove</button></div></div>' +
            '<strong>' + formatPkr(line.line_total) + '</strong></article>'
        ).join('');
        document.querySelector('[data-cart-page-empty]')?.toggleAttribute('hidden', cart.lines.length > 0);
        document.querySelector('[data-cart-page-summary]')?.toggleAttribute('hidden', cart.lines.length === 0);
        const count = document.querySelector('[data-cart-page-count]');
        const subtotal = document.querySelector('[data-cart-page-subtotal]');
        if (count) count.textContent = String(cart.count);
        if (subtotal) subtotal.textContent = formatPkr(cart.subtotal);
    }

    function renderWishlist() {
        document.querySelectorAll('[data-wishlist-count]').forEach((node) => { node.textContent = String(wishlist.length); });
        document.querySelectorAll('[data-wishlist-toggle]').forEach((button) => {
            const id = String(button.dataset.wishlistToggle);
            const selected = wishlist.includes(id);
            const product = productById.get(id);
            button.setAttribute('aria-pressed', String(selected));
            button.setAttribute('aria-label', (selected ? 'Remove ' : 'Add ') + (product?.name || 'product') + (selected ? ' from wishlist' : ' to wishlist'));
        });
        const empty = document.querySelector('[data-wishlist-empty]');
        const lines = document.querySelector('[data-wishlist-lines]');
        if (empty) empty.hidden = wishlist.length > 0;
        if (!lines) return;
        lines.innerHTML = wishlist.map((id) => {
            const product = productById.get(id);
            if (!product) return '';
            return '<article class="wishlist-line" data-wishlist-id="' + escapeHtml(id) + '"><a href="' + escapeHtml(product.url) + '">' +
                '<img src="' + escapeHtml(product.images[0]) + '" alt="" width="220" height="275"></a><div><p>' + escapeHtml(product.collection) + '</p>' +
                '<h3><a href="' + escapeHtml(product.url) + '">' + escapeHtml(product.name) + '</a></h3><strong>' + formatPkr(product.sale_price || product.price) + '</strong>' +
                '<div><button type="button" data-wishlist-quick="' + escapeHtml(id) + '">Quick add</button><button type="button" data-wishlist-remove="' + escapeHtml(id) + '">Remove</button></div></div></article>';
        }).join('');
    }

    async function addToCart(productId, color = '', size = '', quantity = 1) {
        const product = productById.get(String(productId));
        if (!product) return;
        const variantId = color && size ? product.variants?.[color]?.[size]?.id : null;
        if (color && size && !variantId) {
            ui.showToast?.('That product option is unavailable.');
            return;
        }
        try {
            const result = await api('cart/add.php', variantId ? { variant_id: variantId, quantity } : { product_id: Number(productId), quantity });
            if (result.cart) {
                cart = result.cart;
                renderCart();
            }
            ui.showToast?.(result.ok ? product.name + ' added to your bag.' : result.message);
            if (result.ok) openLayer(cartDrawer, document.activeElement, cartDrawer?.querySelector('[data-cart-close]'));
        } catch {
            ui.showToast?.('Your bag could not be updated. Try again.');
        }
    }

    async function updateCartItem(variantId, action) {
        try {
            const result = await api(action === 'remove' ? 'cart/remove.php' : 'cart/update.php', { variant_id: Number(variantId), action });
            if (result.cart) {
                cart = result.cart;
                renderCart();
            }
            if (!result.ok) ui.showToast?.(result.message);
        } catch {
            ui.showToast?.('Your bag could not be updated. Try again.');
        }
    }

    async function toggleWishlist(id) {
        const product = productById.get(String(id));
        if (!product) return;
        try {
            const result = await api('wishlist/toggle.php', { product_id: Number(id) });
            if (!result.ok) {
                ui.showToast?.(result.message);
                return;
            }
            wishlist = result.ids.map(String);
            renderWishlist();
            ui.showToast?.(result.added ? product.name + ' saved to your wishlist.' : product.name + ' removed from your wishlist.');
        } catch {
            ui.showToast?.('Your wishlist could not be updated. Try again.');
        }
    }

    function performSearch(query) {
        const results = document.querySelector('[data-search-results]');
        const status = document.querySelector('[data-search-status]');
        const suggestions = document.querySelector('[data-search-suggestions]');
        const normalized = query.trim().toLowerCase();
        if (!results || !status) return;
        if (!normalized) {
            results.innerHTML = '';
            status.textContent = 'Search by product, category, collection, or detail.';
            if (suggestions) suggestions.hidden = false;
            return;
        }
        const matches = catalog.filter((product) => [product.name, product.category, product.collection, ...(product.keywords || [])].join(' ').toLowerCase().includes(normalized));
        status.textContent = matches.length + (matches.length === 1 ? ' result' : ' results') + ' for “' + query.trim() + '”.';
        if (suggestions) suggestions.hidden = true;
        results.innerHTML = matches.length ? matches.slice(0, 6).map((product) =>
            '<article class="search-result"><a href="' + escapeHtml(product.url) + '" data-dynamic-transition="PRODUCT"><img src="' + escapeHtml(product.images[0]) +
            '" alt="" width="180" height="225"><span><small>' + escapeHtml(product.collection) + '</small><strong>' + escapeHtml(product.name) + '</strong><em>' +
            formatPkr(product.sale_price || product.price) + '</em></span><i aria-hidden="true">↗</i></a></article>'
        ).join('') : '<div class="search-empty"><p>No pieces found.</p><span>Try “hoodie”, “cargo”, or “After Dark”.</span></div>';
    }

    document.querySelectorAll('[data-search-open]').forEach((button) => button.addEventListener('click', () => openLayer(searchOverlay, button, searchInput)));
    document.querySelector('[data-search-close]')?.addEventListener('click', () => closeLayer(searchOverlay));
    document.querySelectorAll('[data-cart-open]').forEach((button) => button.addEventListener('click', () => openLayer(cartDrawer, button, cartDrawer?.querySelector('[data-cart-close]'))));
    document.querySelector('[data-cart-close]')?.addEventListener('click', () => closeLayer(cartDrawer));
    document.querySelectorAll('[data-wishlist-open]').forEach((button) => button.addEventListener('click', () => openLayer(wishlistDrawer, button, wishlistDrawer?.querySelector('[data-wishlist-close]'))));
    document.querySelector('[data-wishlist-close]')?.addEventListener('click', () => closeLayer(wishlistDrawer));
    drawerBackdrop?.addEventListener('click', () => closeLayer());
    searchInput?.addEventListener('input', () => performSearch(searchInput.value));
    document.querySelectorAll('[data-search-suggestion]').forEach((button) => button.addEventListener('click', () => {
        if (!searchInput) return;
        searchInput.value = button.dataset.searchSuggestion || '';
        performSearch(searchInput.value);
        searchInput.focus();
    }));

    document.addEventListener('click', (event) => {
        const wishlistButton = event.target.closest('[data-wishlist-toggle]');
        const quickAdd = event.target.closest('[data-quick-add], [data-wishlist-quick]');
        const wishlistRemove = event.target.closest('[data-wishlist-remove]');
        const cartAction = event.target.closest('[data-cart-action]');
        const cartLine = event.target.closest('[data-cart-key]');
        const dynamicLink = event.target.closest('[data-dynamic-transition]');
        if (wishlistButton) toggleWishlist(wishlistButton.dataset.wishlistToggle);
        if (quickAdd) addToCart(quickAdd.dataset.quickAdd || quickAdd.dataset.wishlistQuick);
        if (wishlistRemove) toggleWishlist(wishlistRemove.dataset.wishlistRemove);
        if (cartAction && cartLine) updateCartItem(cartLine.dataset.cartKey, cartAction.dataset.cartAction);
        if (dynamicLink && !event.defaultPrevented && event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey && ui.navigateWithTransition) {
            event.preventDefault();
            dynamicLink.dataset.transitionName = dynamicLink.dataset.dynamicTransition || 'VANTA';
            ui.navigateWithTransition(dynamicLink);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (!activeLayer) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            closeLayer();
            return;
        }
        if (event.key !== 'Tab') return;
        const focusable = getFocusable(activeLayer);
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    });

    Promise.all([api('cart/get.php'), api('wishlist/get.php')]).then(([cartResult, wishlistResult]) => {
        if (cartResult.ok) cart = cartResult.cart;
        if (wishlistResult.ok) wishlist = wishlistResult.ids.map(String);
        renderCart();
        renderWishlist();
    }).catch(() => ui.showToast?.('Live bag data could not be loaded.'));

    window.vantaStore = { addToCart, api, catalog, config, formatPkr, productById, reducedMotion, ui };
})();

(() => {
    'use strict';

    const store = window.vantaStore;
    if (!store) return;
    const { addToCart, api, formatPkr, productById, reducedMotion, ui } = store;

    function initShopFilters() {
        const page = document.querySelector('[data-shop-page]');
        if (!page) return;
        const grid = page.querySelector('[data-shop-grid]');
        const cards = [...grid.querySelectorAll('[data-product-card]')];
        const search = page.querySelector('[data-shop-search]');
        const sort = page.querySelector('[data-shop-sort]');
        const price = page.querySelector('[data-price-range]');
        const priceOutput = page.querySelector('[data-price-output]');
        const resultCount = page.querySelector('[data-result-count]');
        const empty = page.querySelector('[data-shop-empty]');
        const filters = page.querySelector('[data-filter-panel]');
        const filterOpen = page.querySelector('[data-filter-open]');
        const filterClose = page.querySelector('[data-filter-close]');
        const selectedValues = (group) => [...page.querySelectorAll('[data-filter-group="' + group + '"] input:checked')].map((input) => input.value.toLowerCase());

        function applyFilters() {
            const query = search.value.trim().toLowerCase();
            const categories = selectedValues('category');
            const collections = selectedValues('collection');
            const sizes = selectedValues('size');
            const colors = selectedValues('color');
            const availableOnly = Boolean(page.querySelector('[data-availability]:checked'));
            const maxPrice = Number(price.value);
            if (priceOutput) priceOutput.textContent = formatPkr(maxPrice);
            cards.forEach((card) => {
                const matches = (!query || card.dataset.search.includes(query))
                    && (!categories.length || categories.includes(card.dataset.category))
                    && (!collections.length || collections.includes(card.dataset.collection))
                    && (!sizes.length || sizes.some((size) => card.dataset.sizes.split(',').includes(size)))
                    && (!colors.length || colors.some((color) => card.dataset.colors.split(',').includes(color)))
                    && (!availableOnly || card.dataset.available === 'true')
                    && Number(card.dataset.price) <= maxPrice;
                card.hidden = !matches;
            });
            [...cards].sort((a, b) => {
                if (sort.value === 'price-low') return Number(a.dataset.price) - Number(b.dataset.price);
                if (sort.value === 'price-high') return Number(b.dataset.price) - Number(a.dataset.price);
                if (sort.value === 'popular') return Number(b.dataset.popularity) - Number(a.dataset.popularity);
                return Number(a.dataset.index) - Number(b.dataset.index);
            }).forEach((card) => grid.append(card));
            const visible = cards.filter((card) => !card.hidden);
            resultCount.textContent = String(visible.length);
            empty.hidden = visible.length > 0;
        }

        function openFilters() {
            filters.classList.add('is-open');
            filters.setAttribute('aria-hidden', 'false');
            document.body.classList.add('filter-open');
            window.vantaLenis?.stop();
            window.setTimeout(() => filterClose.focus(), reducedMotion.matches ? 0 : 300);
        }
        function closeFilters(returnFocus = true) {
            filters.classList.remove('is-open');
            document.body.classList.remove('filter-open');
            window.vantaLenis?.start();
            if (window.innerWidth <= 820) filters.setAttribute('aria-hidden', 'true');
            if (returnFocus) filterOpen.focus();
        }
        cards.forEach((card, index) => { card.dataset.index = String(index); });
        if (window.innerWidth <= 820) filters.setAttribute('aria-hidden', 'true');
        page.addEventListener('change', (event) => { if (event.target.matches('input, select')) applyFilters(); });
        search.addEventListener('input', applyFilters);
        price.addEventListener('input', applyFilters);
        page.querySelectorAll('[data-filter-clear]').forEach((button) => button.addEventListener('click', () => {
            page.querySelectorAll('[data-filter-panel] input[type="checkbox"]').forEach((input) => { input.checked = false; });
            price.value = price.max;
            search.value = '';
            applyFilters();
        }));
        filterOpen?.addEventListener('click', openFilters);
        filterClose?.addEventListener('click', () => closeFilters());
        document.addEventListener('keydown', (event) => {
            if (!filters.classList.contains('is-open')) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                closeFilters();
                return;
            }
            if (event.key !== 'Tab') return;
            const focusable = ui.getFocusable
                ? ui.getFocusable(filters)
                : [...filters.querySelectorAll('button, input, select, [href]')];
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth > 820) {
                filters.setAttribute('aria-hidden', 'false');
                if (filters.classList.contains('is-open')) closeFilters(false);
            } else if (!filters.classList.contains('is-open')) filters.setAttribute('aria-hidden', 'true');
        });
        applyFilters();
    }

    function initProductPage() {
        const form = document.querySelector('[data-product-form]');
        if (!form) return;
        const product = productById.get(String(form.dataset.productId));
        if (!product) return;
        const colorInputs = [...form.querySelectorAll('[name="color"]')];
        const sizeInputs = [...form.querySelectorAll('[name="size"]')];
        const addButton = form.querySelector('[data-add-to-bag]');
        const stockState = form.querySelector('[data-stock-state]');
        const colorName = form.querySelector('[data-selected-color]');
        const quantityValue = form.querySelector('[data-quantity-value]');
        const galleryImages = [...document.querySelectorAll('[data-gallery-image]')];
        let quantity = 1;
        const selected = (name) => form.querySelector('[name="' + name + '"]:checked')?.value || '';
        function updateGallery(colorId) {
            const color = product.colors.find((option) => option.id === colorId);
            const images = color?.images?.length ? color.images : product.images;
            galleryImages.forEach((image, index) => {
                const nextSrc = images[index % images.length];
                if (!nextSrc || image.src.endsWith(nextSrc)) return;
                image.classList.add('is-changing');
                window.setTimeout(() => {
                    image.src = nextSrc;
                    image.classList.remove('is-changing');
                }, reducedMotion.matches ? 0 : 180);
            });
            if (colorName) colorName.textContent = color?.name || colorId;
        }
        function updateProductState() {
            const color = selected('color');
            const size = selected('size');
            const stock = product.stock[color] || {};
            sizeInputs.forEach((input) => {
                const quantityForSize = Number(stock[input.value] || 0);
                input.closest('[data-size-option]')?.classList.toggle('is-sold-out', quantityForSize < 1);
                input.setAttribute('aria-label', input.value + ', ' + (quantityForSize > 0 ? quantityForSize + ' in stock' : 'out of stock'));
            });
            const available = Boolean(size) && Number(stock[size] || 0) > 0;
            addButton.disabled = !available;
            addButton.textContent = available ? 'Add to bag' : (size ? 'Out of stock' : 'Select a size');
            if (stockState) {
                stockState.classList.toggle('is-out', Boolean(size) && !available);
                stockState.innerHTML = available ? '<i></i> IN STOCK / ' + Number(stock[size]) + ' READY TO SHIP' : (size ? '<i></i> OUT OF STOCK / SELECT ANOTHER SIZE' : '<i></i> SELECT A SIZE');
            }
            quantity = Math.min(quantity, Math.max(Number(stock[size] || 1), 1), 5);
            if (quantityValue) quantityValue.textContent = String(quantity).padStart(2, '0');
        }
        colorInputs.forEach((input) => input.addEventListener('change', () => { updateGallery(input.value); updateProductState(); }));
        sizeInputs.forEach((input) => input.addEventListener('change', updateProductState));
        form.querySelectorAll('[data-quantity-action]').forEach((button) => button.addEventListener('click', () => {
            const stock = Number(product.stock[selected('color')]?.[selected('size')] || 1);
            quantity = button.dataset.quantityAction === 'increase' ? Math.min(quantity + 1, stock, 5) : Math.max(quantity - 1, 1);
            quantityValue.textContent = String(quantity).padStart(2, '0');
        }));
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const size = selected('size');
            if (!size) {
                ui.showToast?.('Select a size before adding this piece.');
                sizeInputs[0]?.focus();
                return;
            }
            addToCart(product.id, selected('color'), size, quantity);
        });
        const sizeGuide = document.querySelector('[data-size-guide]');
        const openSizeGuide = () => {
            if (typeof sizeGuide?.showModal === 'function' && !sizeGuide.open) sizeGuide.showModal();
            window.vantaLenis?.stop();
        };
        document.querySelector('[data-size-guide-open]')?.addEventListener('click', openSizeGuide);
        document.querySelector('[data-size-guide-close]')?.addEventListener('click', () => sizeGuide?.close());
        sizeGuide?.addEventListener('close', () => window.vantaLenis?.start());
        if (window.location.hash === '#size-guide') openSizeGuide();
        updateGallery(selected('color'));
        updateProductState();
    }

    function initNewsletter() {
        document.querySelectorAll('[data-newsletter-form]').forEach((form) => {
            const input = form.querySelector('input[type="email"]');
            const feedback = form.querySelector('[data-newsletter-feedback]');
            const button = form.querySelector('button[type="submit"]');
            input.addEventListener('input', () => {
                input.removeAttribute('aria-invalid');
                feedback.textContent = '';
            });
            form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!input.checkValidity()) {
                input.setAttribute('aria-invalid', 'true');
                feedback.textContent = 'Enter a valid email address.';
                input.focus();
                return;
            }
            button.disabled = true;
            const result = await api('newsletter/subscribe.php', { email: input.value });
            feedback.textContent = result.message;
            input.toggleAttribute('aria-invalid', !result.ok);
            if (result.ok) input.value = '';
            button.disabled = false;
            });
        });
    }

    function initCoupon() {
        const box = document.querySelector('[data-coupon-form]');
        if (!box) return;
        box.querySelector('[data-coupon-apply]')?.addEventListener('click', async () => {
            const code = box.querySelector('[data-coupon-code]').value;
            const email = document.querySelector('[name="email"]')?.value || '';
            const feedback = box.querySelector('[data-coupon-feedback]');
            const result = await api('coupons/apply.php', { code, email });
            feedback.textContent = result.message;
            if (result.quote) {
                document.querySelector('[data-quote-subtotal]').textContent = formatPkr(result.quote.subtotal);
                document.querySelector('[data-quote-discount]').textContent = '−' + formatPkr(result.quote.discount);
                document.querySelector('[data-quote-shipping]').textContent = result.quote.shipping > 0 ? formatPkr(result.quote.shipping) : 'Free';
                document.querySelector('[data-quote-total]').textContent = formatPkr(result.quote.total);
            }
        });
    }

    function initReviews() {
        const form = document.querySelector('[data-review-form]');
        if (!form) return;
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const feedback = form.querySelector('[data-review-feedback]');
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            const result = await api('reviews/create.php', {
                product_id: Number(form.dataset.productId),
                rating: Number(form.elements.rating.value),
                content: form.elements.content.value,
            });
            feedback.textContent = result.message;
            if (result.ok) form.reset();
            button.disabled = false;
        });
    }

    initShopFilters();
    initProductPage();
    initNewsletter();
    initCoupon();
    initReviews();
})();
