(() => {
    'use strict';

    const ui = window.vantaUI || {};
    const reducedMotion = ui.reducedMotion || window.matchMedia('(prefers-reduced-motion: reduce)');
    const catalogNode = document.querySelector('#vanta-catalog');
    let catalog = [];

    try {
        catalog = JSON.parse(catalogNode?.textContent || '[]');
    } catch (error) {
        console.error('VANTA catalog could not be read.', error);
    }

    const productById = new Map(catalog.map((product) => [String(product.id), product]));
    const cartKey = 'vanta.phase2.cart';
    const wishlistKey = 'vanta.phase2.wishlist';
    const searchOverlay = document.querySelector('[data-search-overlay]');
    const cartDrawer = document.querySelector('[data-cart-drawer]');
    const wishlistDrawer = document.querySelector('[data-wishlist-drawer]');
    const drawerBackdrop = document.querySelector('[data-drawer-backdrop]');
    const searchInput = document.querySelector('[data-search-input]');
    let activeLayer = null;
    let activeTrigger = null;

    function readStorage(key) {
        try {
            const parsed = JSON.parse(window.localStorage.getItem(key) || '');
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }

    function writeStorage(key, value) {
        try {
            window.localStorage.setItem(key, JSON.stringify(value));
        } catch {
            // The in-memory state remains usable when private browsing blocks storage.
        }
    }

    let cart = readStorage(cartKey).filter((item) => productById.has(String(item.productId)));
    let wishlist = readStorage(wishlistKey).map(String).filter((id) => productById.has(id));

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatPkr(value) {
        return 'PKR ' + Number(value).toLocaleString('en-PK');
    }

    function getFocusable(container) {
        if (ui.getFocusable) return ui.getFocusable(container);
        return [...container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])')];
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
        window.setTimeout(() => {
            (focusTarget || getFocusable(layer)[0])?.focus({ preventScroll: true });
        }, reducedMotion.matches ? 0 : (focusTarget ? 40 : 360));
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

    function firstAvailableVariant(product) {
        for (const color of product.colors) {
            const stock = product.stock[color.id] || {};
            const size = Object.keys(stock).find((key) => Number(stock[key]) > 0);
            if (size) return { color: color.id, size };
        }
        return null;
    }

    function cartItemKey(item) {
        return item.productId + ':' + item.color + ':' + item.size;
    }

    function addToCart(productId, color, size, quantity = 1) {
        const product = productById.get(String(productId));
        if (!product) return;
        const variant = color && size ? { color, size } : firstAvailableVariant(product);
        if (!variant) {
            ui.showToast?.('This piece is currently unavailable.');
            return;
        }

        const stock = Number(product.stock[variant.color]?.[variant.size] || 0);
        if (stock < 1) {
            ui.showToast?.('That size is out of stock.');
            return;
        }

        const next = {
            productId: String(product.id),
            color: variant.color,
            size: variant.size,
            quantity: Math.min(Math.max(Number(quantity) || 1, 1), stock),
        };
        const existing = cart.find((item) => cartItemKey(item) === cartItemKey(next));
        if (existing) existing.quantity = Math.min(existing.quantity + next.quantity, stock);
        else cart.push(next);

        writeStorage(cartKey, cart);
        renderCart();
        ui.showToast?.(product.name + ' added to your bag.');
        const trigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        openLayer(cartDrawer, trigger, cartDrawer?.querySelector('[data-cart-close]'));
    }

    function renderCart() {
        const lines = document.querySelector('[data-cart-lines]');
        const empty = document.querySelector('[data-cart-empty]');
        const footer = document.querySelector('[data-cart-footer]');
        const subtotal = cart.reduce((total, item) => {
            const product = productById.get(String(item.productId));
            return total + (product ? Number(product.sale_price || product.price) * item.quantity : 0);
        }, 0);
        const count = cart.reduce((total, item) => total + item.quantity, 0);

        document.querySelectorAll('[data-cart-count]').forEach((node) => { node.textContent = String(count); });
        if (empty) empty.hidden = cart.length > 0;
        if (footer) footer.hidden = cart.length === 0;
        const subtotalNode = document.querySelector('[data-cart-subtotal]');
        if (subtotalNode) subtotalNode.textContent = formatPkr(subtotal);
        if (!lines) return;

        lines.innerHTML = cart.map((item) => {
            const product = productById.get(String(item.productId));
            if (!product) return '';
            const color = product.colors.find((option) => option.id === item.color);
            const unitPrice = Number(product.sale_price || product.price);
            const key = cartItemKey(item);
            return '<article class="cart-line" data-cart-key="' + escapeHtml(key) + '">' +
                '<a class="cart-line__image" href="' + escapeHtml(product.url) + '"><img src="' + escapeHtml(color?.images?.[0] || product.images[0]) + '" alt="" width="220" height="275"></a>' +
                '<div class="cart-line__body"><div><p>' + escapeHtml(product.collection) + '</p><h3><a href="' + escapeHtml(product.url) + '">' + escapeHtml(product.name) + '</a></h3>' +
                '<span>' + escapeHtml(color?.name || item.color) + ' / ' + escapeHtml(item.size) + '</span></div>' +
                '<strong>' + formatPkr(unitPrice * item.quantity) + '</strong><div class="cart-line__actions">' +
                '<div class="mini-quantity" aria-label="Quantity for ' + escapeHtml(product.name) + '"><button type="button" data-cart-action="decrease" aria-label="Decrease quantity">−</button>' +
                '<span>' + String(item.quantity).padStart(2, '0') + '</span><button type="button" data-cart-action="increase" aria-label="Increase quantity">+</button></div>' +
                '<button type="button" data-cart-action="remove">Remove</button></div></div></article>';
        }).join('');
    }

    function updateCartItem(key, action) {
        const index = cart.findIndex((item) => cartItemKey(item) === key);
        if (index < 0) return;
        const item = cart[index];
        const product = productById.get(String(item.productId));
        const stock = Number(product?.stock?.[item.color]?.[item.size] || 0);
        if (action === 'remove' || (action === 'decrease' && item.quantity <= 1)) cart.splice(index, 1);
        else if (action === 'decrease') item.quantity -= 1;
        else if (action === 'increase') item.quantity = Math.min(item.quantity + 1, stock);
        writeStorage(cartKey, cart);
        renderCart();
    }

    function renderWishlist() {
        const lines = document.querySelector('[data-wishlist-lines]');
        const empty = document.querySelector('[data-wishlist-empty]');
        document.querySelectorAll('[data-wishlist-count]').forEach((node) => { node.textContent = String(wishlist.length); });
        document.querySelectorAll('[data-wishlist-toggle]').forEach((button) => {
            const selected = wishlist.includes(String(button.dataset.wishlistToggle));
            button.setAttribute('aria-pressed', String(selected));
            const product = productById.get(String(button.dataset.wishlistToggle));
            button.setAttribute('aria-label', (selected ? 'Remove ' : 'Add ') + (product?.name || 'product') + (selected ? ' from wishlist' : ' to wishlist'));
        });
        if (empty) empty.hidden = wishlist.length > 0;
        if (!lines) return;

        lines.innerHTML = wishlist.map((id) => {
            const product = productById.get(id);
            if (!product) return '';
            return '<article class="wishlist-line" data-wishlist-id="' + escapeHtml(id) + '">' +
                '<a href="' + escapeHtml(product.url) + '"><img src="' + escapeHtml(product.images[0]) + '" alt="" width="220" height="275"></a>' +
                '<div><p>' + escapeHtml(product.collection) + '</p><h3><a href="' + escapeHtml(product.url) + '">' + escapeHtml(product.name) + '</a></h3>' +
                '<strong>' + formatPkr(product.sale_price || product.price) + '</strong><div><button type="button" data-wishlist-quick="' + escapeHtml(id) + '">Quick add</button>' +
                '<button type="button" data-wishlist-remove="' + escapeHtml(id) + '">Remove</button></div></div></article>';
        }).join('');
    }

    function toggleWishlist(id) {
        const product = productById.get(String(id));
        if (!product) return;
        const index = wishlist.indexOf(String(id));
        const added = index < 0;
        if (added) wishlist.push(String(id));
        else wishlist.splice(index, 1);
        writeStorage(wishlistKey, wishlist);
        renderWishlist();
        ui.showToast?.(added ? product.name + ' saved to your guest wishlist.' : product.name + ' removed from your wishlist.');
        const buttons = document.querySelectorAll('[data-wishlist-toggle="' + CSS.escape(String(id)) + '"]');
        if (added && !reducedMotion.matches && typeof window.gsap !== 'undefined') {
            window.gsap.fromTo(buttons, { scale: 0.82 }, { scale: 1, duration: 0.5, ease: 'elastic.out(1, .45)' });
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

        const matches = catalog.filter((product) => {
            const haystack = [product.name, product.category, product.collection, ...(product.keywords || [])].join(' ').toLowerCase();
            return haystack.includes(normalized);
        });
        status.textContent = matches.length + (matches.length === 1 ? ' result' : ' results') + ' for “' + query.trim() + '”.';
        if (suggestions) suggestions.hidden = true;
        if (!matches.length) {
            results.innerHTML = '<div class="search-empty"><p>No pieces found.</p><span>Try “hoodie”, “cargo”, or “After Dark”.</span></div>';
            return;
        }

        results.innerHTML = matches.slice(0, 6).map((product) =>
            '<article class="search-result"><a href="' + escapeHtml(product.url) + '" data-dynamic-transition="PRODUCT">' +
            '<img src="' + escapeHtml(product.images[0]) + '" alt="" width="180" height="225"><span><small>' + escapeHtml(product.collection) + '</small>' +
            '<strong>' + escapeHtml(product.name) + '</strong><em>' + formatPkr(product.sale_price || product.price) + '</em></span><i aria-hidden="true">↗</i></a></article>'
        ).join('');
    }

    document.querySelectorAll('[data-search-open]').forEach((button) => button.addEventListener('click', () => openLayer(searchOverlay, button, searchInput)));
    document.querySelector('[data-search-close]')?.addEventListener('click', () => closeLayer(searchOverlay));
    document.querySelectorAll('[data-cart-open]').forEach((button) => button.addEventListener('click', () => openLayer(cartDrawer, button, cartDrawer?.querySelector('[data-cart-close]'))));
    document.querySelector('[data-cart-close]')?.addEventListener('click', () => closeLayer(cartDrawer));
    document.querySelectorAll('[data-wishlist-open]').forEach((button) => button.addEventListener('click', () => openLayer(wishlistDrawer, button, wishlistDrawer?.querySelector('[data-wishlist-close]'))));
    document.querySelector('[data-wishlist-close]')?.addEventListener('click', () => closeLayer(wishlistDrawer));
    drawerBackdrop?.addEventListener('click', () => closeLayer());
    searchInput?.addEventListener('input', () => performSearch(searchInput.value));

    document.querySelectorAll('[data-search-suggestion]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!searchInput) return;
            searchInput.value = button.dataset.searchSuggestion || '';
            performSearch(searchInput.value);
            searchInput.focus();
        });
    });

    document.addEventListener('click', (event) => {
        const wishlistButton = event.target.closest('[data-wishlist-toggle]');
        const quickAdd = event.target.closest('[data-quick-add], [data-wishlist-quick]');
        const wishlistRemove = event.target.closest('[data-wishlist-remove]');
        const dynamicLink = event.target.closest('[data-dynamic-transition]');
        if (wishlistButton) toggleWishlist(wishlistButton.dataset.wishlistToggle);
        if (quickAdd) addToCart(quickAdd.dataset.quickAdd || quickAdd.dataset.wishlistQuick);
        if (wishlistRemove) toggleWishlist(wishlistRemove.dataset.wishlistRemove);
        if (dynamicLink && !event.defaultPrevented && event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey && ui.navigateWithTransition) {
            event.preventDefault();
            dynamicLink.dataset.transitionName = dynamicLink.dataset.dynamicTransition || 'VANTA';
            ui.navigateWithTransition(dynamicLink);
        }
    });

    document.querySelector('[data-cart-lines]')?.addEventListener('click', (event) => {
        const action = event.target.closest('[data-cart-action]');
        const line = event.target.closest('[data-cart-key]');
        if (action && line) updateCartItem(line.dataset.cartKey, action.dataset.cartAction);
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
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    renderCart();
    renderWishlist();
    window.vantaStore = { addToCart, catalog, formatPkr, productById, reducedMotion, ui };
})();

(() => {
    'use strict';

    const store = window.vantaStore;
    if (!store) return;
    const { addToCart, formatPkr, productById, reducedMotion, ui } = store;

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

        function selectedValues(group) {
            return [...page.querySelectorAll('[data-filter-group="' + group + '"] input:checked')].map((input) => input.value.toLowerCase());
        }

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

            const order = [...cards].sort((a, b) => {
                if (sort.value === 'price-low') return Number(a.dataset.price) - Number(b.dataset.price);
                if (sort.value === 'price-high') return Number(b.dataset.price) - Number(a.dataset.price);
                if (sort.value === 'popular') return Number(b.dataset.popularity) - Number(a.dataset.popularity);
                return Number(a.dataset.index) - Number(b.dataset.index);
            });
            order.forEach((card) => grid.append(card));

            const visible = cards.filter((card) => !card.hidden);
            resultCount.textContent = String(visible.length);
            empty.hidden = visible.length > 0;
            if (!reducedMotion.matches && typeof window.gsap !== 'undefined' && visible.length) {
                window.gsap.fromTo(visible, { clipPath: 'inset(0 0 7% 0)', scale: 0.988 }, {
                    clipPath: 'inset(0 0 0% 0)',
                    scale: 1,
                    duration: 0.42,
                    stagger: 0.025,
                    ease: 'power3.out',
                    clearProps: 'clipPath,scale',
                });
            }
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

        page.addEventListener('change', (event) => {
            if (event.target.matches('input, select')) applyFilters();
        });
        search.addEventListener('input', applyFilters);
        price.addEventListener('input', applyFilters);
        page.querySelectorAll('[data-filter-clear]').forEach((button) => {
            button.addEventListener('click', () => {
                page.querySelectorAll('[data-filter-panel] input[type="checkbox"]').forEach((input) => { input.checked = false; });
                price.value = price.max;
                search.value = '';
                applyFilters();
            });
        });
        filterOpen?.addEventListener('click', openFilters);
        filterClose?.addEventListener('click', () => closeFilters());
        document.addEventListener('keydown', (event) => {
            if (!filters.classList.contains('is-open')) return;
            if (event.key === 'Escape') {
                closeFilters();
                return;
            }
            if (event.key !== 'Tab') return;
            const focusable = ui.getFocusable ? ui.getFocusable(filters) : [...filters.querySelectorAll('button, input')];
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth > 820) {
                filters.setAttribute('aria-hidden', 'false');
                if (filters.classList.contains('is-open')) closeFilters(false);
            } else if (!filters.classList.contains('is-open')) {
                filters.setAttribute('aria-hidden', 'true');
            }
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

        function selected(name) {
            return form.querySelector('[name="' + name + '"]:checked')?.value || '';
        }

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
                const option = input.closest('[data-size-option]');
                const quantityForSize = Number(stock[input.value] || 0);
                option?.classList.toggle('is-sold-out', quantityForSize < 1);
                input.setAttribute('aria-label', input.value + ', ' + (quantityForSize > 0 ? quantityForSize + ' in stock' : 'out of stock'));
            });

            const available = Boolean(size) && Number(stock[size] || 0) > 0;
            addButton.disabled = !available;
            addButton.textContent = available ? 'Add to bag' : (size ? 'Out of stock' : 'Select a size');
            if (stockState) {
                stockState.classList.toggle('is-out', Boolean(size) && !available);
                stockState.innerHTML = available
                    ? '<i></i> IN STOCK / ' + Number(stock[size]) + ' READY TO SHIP'
                    : (size ? '<i></i> OUT OF STOCK / SELECT ANOTHER SIZE' : '<i></i> SELECT A SIZE');
            }
            quantity = Math.min(quantity, Math.max(Number(stock[size] || 1), 1), 5);
            if (quantityValue) quantityValue.textContent = String(quantity).padStart(2, '0');
        }

        colorInputs.forEach((input) => input.addEventListener('change', () => {
            updateGallery(input.value);
            updateProductState();
        }));
        sizeInputs.forEach((input) => input.addEventListener('change', updateProductState));
        form.querySelectorAll('[data-quantity-action]').forEach((button) => {
            button.addEventListener('click', () => {
                const stock = Number(product.stock[selected('color')]?.[selected('size')] || 1);
                quantity = button.dataset.quantityAction === 'increase' ? Math.min(quantity + 1, stock, 5) : Math.max(quantity - 1, 1);
                quantityValue.textContent = String(quantity).padStart(2, '0');
            });
        });
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
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const input = form.querySelector('input[type="email"]');
                const feedback = form.querySelector('[data-newsletter-feedback]');
                if (!input.checkValidity()) {
                    input.setAttribute('aria-invalid', 'true');
                    feedback.textContent = 'Enter a valid email address.';
                    input.focus();
                    return;
                }
                input.removeAttribute('aria-invalid');
                feedback.textContent = 'You are on the list. Welcome after dark.';
                input.value = '';
            });
        });
    }

    initShopFilters();
    initProductPage();
    initNewsletter();
})();
