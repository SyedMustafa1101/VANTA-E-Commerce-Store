(() => {
    'use strict';

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const header = document.querySelector('[data-site-header]');
    const menu = document.querySelector('[data-mobile-menu]');
    const menuToggle = document.querySelector('[data-menu-toggle]');
    const transition = document.querySelector('[data-page-transition]');
    const transitionLabel = document.querySelector('[data-transition-label]');
    const toast = document.querySelector('[data-toast]');
    let menuOpen = false;
    let toastTimer;

    function initHeader() {
        if (!header) return;

        const updateHeader = () => header.classList.toggle('is-scrolled', window.scrollY > 40);
        updateHeader();
        window.addEventListener('scroll', updateHeader, { passive: true });
    }

    function revealPageElements() {
        const elements = document.querySelectorAll('[data-reveal]');

        if (reducedMotion.matches || !('IntersectionObserver' in window)) {
            elements.forEach((element) => element.classList.add('is-revealed'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

        elements.forEach((element) => observer.observe(element));
    }

    function getFocusable(container) {
        return [...container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])')]
            .filter((element) => !element.hasAttribute('hidden'));
    }

    function finishMenuClose() {
        if (!menu || !menuToggle) return;
        menu.classList.remove('is-open');
        menu.setAttribute('aria-hidden', 'true');
        menu.style.removeProperty('transform');
        document.body.classList.remove('menu-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.querySelector('.sr-only').textContent = 'Open menu';
        menuOpen = false;
    }

    function closeMenu({ returnFocus = true, immediate = false } = {}) {
        if (!menu || !menuToggle || !menuOpen) return;

        if (immediate || reducedMotion.matches || typeof window.gsap === 'undefined') {
            finishMenuClose();
        } else {
            window.gsap.to(menu, {
                yPercent: -100,
                duration: 0.55,
                ease: 'power4.inOut',
                onComplete: finishMenuClose,
            });
        }

        if (returnFocus) menuToggle.focus({ preventScroll: true });
    }

    function openMenu() {
        if (!menu || !menuToggle || menuOpen) return;

        menuOpen = true;
        menu.classList.add('is-open');
        menu.setAttribute('aria-hidden', 'false');
        document.body.classList.add('menu-open');
        menuToggle.setAttribute('aria-expanded', 'true');
        menuToggle.querySelector('.sr-only').textContent = 'Close menu';

        if (!reducedMotion.matches && typeof window.gsap !== 'undefined') {
            const links = menu.querySelectorAll('.mobile-menu__nav a');
            const secondary = menu.querySelectorAll('.mobile-menu__top, .mobile-menu__secondary, .mobile-menu__statement');

            window.gsap.set(menu, { yPercent: -100 });
            window.gsap.set(links, { yPercent: 105, opacity: 0 });
            window.gsap.set(secondary, { y: 16, opacity: 0 });

            window.gsap.timeline()
                .to(menu, { yPercent: 0, duration: 0.58, ease: 'power4.inOut' })
                .to(links, { yPercent: 0, opacity: 1, duration: 0.62, stagger: 0.07, ease: 'power4.out' }, '-=0.15')
                .to(secondary, { y: 0, opacity: 1, duration: 0.45, stagger: 0.05, ease: 'power3.out' }, '-=0.42');
        }

        window.setTimeout(() => getFocusable(menu)[0]?.focus({ preventScroll: true }), reducedMotion.matches ? 0 : 420);
    }

    function initMenu() {
        if (!menu || !menuToggle) return;

        menuToggle.addEventListener('click', () => {
            if (menuOpen) closeMenu(); else openMenu();
        });

        document.addEventListener('keydown', (event) => {
            if (!menuOpen) return;

            if (event.key === 'Escape') {
                closeMenu();
                return;
            }

            if (event.key !== 'Tab') return;
            const focusable = [menuToggle, ...getFocusable(menu)];
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
            if (window.innerWidth > 820 && menuOpen) closeMenu({ returnFocus: false, immediate: true });
        });
    }

    function showToast(message) {
        if (!toast) return;
        window.clearTimeout(toastTimer);
        toast.textContent = message;
        toast.classList.add('is-visible');
        toastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 3200);
    }

    function initFoundationNotices() {
        document.querySelectorAll('[data-foundation-notice]').forEach((control) => {
            control.addEventListener('click', (event) => {
                event.preventDefault();
                showToast(control.dataset.foundationNotice || 'This feature is scheduled for a later phase.');
            });
        });
    }

    function navigateWithTransition(link) {
        const href = link.href;

        if (!transition || reducedMotion.matches || typeof window.gsap === 'undefined') {
            window.location.assign(href);
            return;
        }

        if (transitionLabel) transitionLabel.textContent = link.dataset.transitionName || 'VANTA';
        closeMenu({ returnFocus: false, immediate: true });

        window.gsap.set(transition, { yPercent: 101 });
        window.gsap.to(transition, {
            yPercent: 0,
            duration: 0.55,
            ease: 'power4.inOut',
            onComplete: () => window.location.assign(href),
        });
    }

    function initPageTransitions() {
        document.querySelectorAll('[data-transition-link]').forEach((link) => {
            link.addEventListener('click', (event) => {
                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

                const target = new URL(link.href, window.location.href);
                const current = new URL(window.location.href);
                const sameDocument = target.pathname === current.pathname && target.search === current.search;

                if (sameDocument && target.hash) return;

                event.preventDefault();
                navigateWithTransition(link);
            });
        });
    }

    function initAnchorScroll() {
        document.querySelectorAll('a[href*="#"]').forEach((link) => {
            link.addEventListener('click', (event) => {
                if (event.defaultPrevented) return;

                const targetUrl = new URL(link.href, window.location.href);
                const currentUrl = new URL(window.location.href);
                const sameDocument = targetUrl.pathname === currentUrl.pathname && targetUrl.search === currentUrl.search;

                if (!sameDocument || !targetUrl.hash) return;
                const target = document.querySelector(targetUrl.hash);
                if (!target) return;

                event.preventDefault();
                closeMenu({ returnFocus: false, immediate: true });

                if (window.vantaLenis && !reducedMotion.matches) {
                    window.vantaLenis.scrollTo(target, { offset: -68 });
                } else {
                    target.scrollIntoView({ behavior: reducedMotion.matches ? 'auto' : 'smooth' });
                }
            });
        });
    }

    function initMagneticActions() {
        if (reducedMotion.matches || window.matchMedia('(pointer: coarse)').matches || typeof window.gsap === 'undefined') return;

        document.querySelectorAll('.magnetic').forEach((element) => {
            element.addEventListener('pointermove', (event) => {
                const bounds = element.getBoundingClientRect();
                const x = event.clientX - bounds.left - bounds.width / 2;
                const y = event.clientY - bounds.top - bounds.height / 2;

                window.gsap.to(element, { x: x * 0.12, y: y * 0.12, duration: 0.35, ease: 'power2.out' });
            });

            element.addEventListener('pointerleave', () => {
                window.gsap.to(element, { x: 0, y: 0, duration: 0.55, ease: 'elastic.out(1, 0.45)' });
            });
        });
    }

    window.vantaUI = {
        closeMenu,
        getFocusable,
        navigateWithTransition,
        reducedMotion,
        showToast,
    };

    initHeader();
    initMenu();
    initFoundationNotices();
    initPageTransitions();
    initAnchorScroll();
    initMagneticActions();
    revealPageElements();
})();
