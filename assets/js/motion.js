(() => {
    'use strict';

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const hasGsap = typeof window.gsap !== 'undefined';
    const hasScrollTrigger = typeof window.ScrollTrigger !== 'undefined';

    document.documentElement.dataset.motionEngine = hasGsap && hasScrollTrigger ? 'gsap' : 'fallback';
    document.documentElement.dataset.motionPreference = reducedMotion.matches ? 'reduced' : 'full';

    function completeLoader(loader) {
        loader?.remove();
        try {
            window.sessionStorage.setItem('vanta.loader.seen', 'true');
        } catch {
            // Session storage is optional.
        }
        document.documentElement.classList.add('is-ready');
        window.dispatchEvent(new CustomEvent('vanta:ready'));
    }

    function initLoader() {
        const loader = document.querySelector('[data-site-loader]');
        let loaderSeen = false;

        try {
            loaderSeen = window.sessionStorage.getItem('vanta.loader.seen') === 'true';
        } catch {
            loaderSeen = false;
        }

        if (!loader || reducedMotion.matches || loaderSeen) {
            completeLoader(loader);
            return;
        }

        const letters = loader.querySelectorAll('.site-loader__word span');
        const progress = loader.querySelector('[data-loader-progress]');
        const counter = loader.querySelector('[data-loader-count]');

        if (!hasGsap) {
            letters.forEach((letter) => { letter.style.transform = 'translateY(0)'; });
            if (progress) progress.style.transform = 'scaleX(1)';
            if (counter) counter.textContent = '100';
            window.setTimeout(() => completeLoader(loader), 240);
            return;
        }

        const count = { value: 0 };
        const timeline = window.gsap.timeline({
            defaults: { ease: 'power4.out' },
            onComplete: () => completeLoader(loader),
        });

        timeline
            .to(letters, { yPercent: -115, duration: 0, stagger: 0 })
            .to(letters, { yPercent: 0, duration: 0.62, stagger: 0.055 })
            .to(progress, { scaleX: 1, duration: 0.5, ease: 'power2.inOut' }, '<0.08')
            .to(count, {
                value: 100,
                duration: 0.5,
                ease: 'none',
                onUpdate: () => {
                    if (counter) counter.textContent = String(Math.round(count.value)).padStart(2, '0');
                },
            }, '<')
            .to(letters, { yPercent: -120, duration: 0.45, stagger: 0.035, ease: 'power3.in' }, '+=0.05')
            .to(loader, { yPercent: -100, duration: 0.62, ease: 'power4.inOut' }, '-=0.16');
    }

    function initHeroEntrance() {
        const heroLines = document.querySelectorAll('[data-hero-line]');
        const heroMedia = document.querySelector('[data-hero-media] img');
        const heroMeta = document.querySelector('[data-hero-meta]');
        const heroActions = document.querySelector('[data-hero-actions]');
        const heroFooter = document.querySelector('[data-hero-footer]');

        if (!heroLines.length || reducedMotion.matches || !hasGsap) return;

        window.gsap.set(heroLines, { yPercent: 115 });
        window.gsap.set(heroMedia, { scale: 1.08 });
        window.gsap.set([heroMeta, heroActions, heroFooter].filter(Boolean), { opacity: 0, y: 16 });

        window.addEventListener('vanta:ready', () => {
            window.gsap.timeline({ defaults: { ease: 'power4.out' } })
                .to(heroMeta, { opacity: 1, y: 0, duration: 0.5 })
                .to(heroLines, { yPercent: 0, duration: 0.9, stagger: 0.075 }, '-=0.26')
                .to(heroActions, { opacity: 1, y: 0, duration: 0.48 }, '-=0.44')
                .to(heroFooter, { opacity: 1, y: 0, duration: 0.42 }, '-=0.32');
            window.gsap.to(heroMedia, { scale: 1.025, duration: 1.65, ease: 'power3.out' });
        }, { once: true });
    }

    function initSmoothScroll() {
        if (reducedMotion.matches || typeof window.Lenis === 'undefined') {
            document.documentElement.dataset.smoothScroll = 'native';
            return;
        }

        const lenis = new window.Lenis({
            duration: 1.05,
            smoothWheel: true,
            wheelMultiplier: 0.9,
            touchMultiplier: 1.1,
        });

        function raf(time) {
            lenis.raf(time);
            window.requestAnimationFrame(raf);
        }

        if (hasScrollTrigger) {
            lenis.on('scroll', window.ScrollTrigger.update);
        }

        window.requestAnimationFrame(raf);
        window.vantaLenis = lenis;
        document.documentElement.dataset.smoothScroll = 'lenis';
    }

    function initCollectionEntrance() {
        const lines = document.querySelectorAll('[data-collection-line]');
        const media = document.querySelector('[data-collection-media] img');
        if (!lines.length || reducedMotion.matches || !hasGsap) return;

        window.gsap.set(lines, { yPercent: 115 });
        window.gsap.set(media, { scale: 1.07 });
        window.addEventListener('vanta:ready', () => {
            window.gsap.timeline({ defaults: { ease: 'power4.out' } })
                .to(lines, { yPercent: 0, duration: 0.92, stagger: 0.08 })
                .to(media, { scale: 1, duration: 1.4, ease: 'power3.out' }, 0);
        }, { once: true });
    }

    function initScrollMotion() {
        if (!hasGsap || !hasScrollTrigger || reducedMotion.matches) return;

        window.gsap.registerPlugin(window.ScrollTrigger);

        document.querySelectorAll('[data-horizontal-text]').forEach((track) => {
            window.gsap.fromTo(track, { xPercent: -8 }, {
                xPercent: -35,
                ease: 'none',
                scrollTrigger: {
                    trigger: track,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: 0.8,
                },
            });
        });

        const heroMedia = document.querySelector('[data-hero-media] img');
        if (heroMedia) {
            window.gsap.to(heroMedia, {
                yPercent: 8,
                ease: 'none',
                scrollTrigger: {
                    trigger: '[data-storefront-hero], .foundation-hero',
                    start: 'top top',
                    end: 'bottom top',
                    scrub: true,
                },
            });
        }

        document.querySelectorAll('[data-product-grid]').forEach((grid) => {
            const cards = grid.querySelectorAll('[data-product-card]');
            window.gsap.set(cards, { clipPath: 'inset(0 0 12% 0)', scale: 0.985 });
            window.ScrollTrigger.batch(cards, {
                start: 'top 92%',
                once: true,
                onEnter: (batch) => window.gsap.to(batch, {
                    clipPath: 'inset(0 0 0% 0)',
                    scale: 1,
                    duration: 0.7,
                    stagger: 0.06,
                    ease: 'power3.out',
                    clearProps: 'clipPath,scale',
                }),
            });
        });

        document.querySelectorAll('[data-image-clip]').forEach((frame) => {
            window.gsap.fromTo(frame, { clipPath: 'inset(0 0 14% 0)' }, {
                clipPath: 'inset(0 0 0% 0)',
                duration: 1.05,
                ease: 'power4.out',
                scrollTrigger: {
                    trigger: frame,
                    start: 'top 88%',
                    once: true,
                },
            });
        });

        document.querySelectorAll('[data-parallax-media] img').forEach((image) => {
            window.gsap.fromTo(image, { yPercent: -5 }, {
                yPercent: 5,
                ease: 'none',
                scrollTrigger: {
                    trigger: image.parentElement,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: 0.8,
                },
            });
        });

        document.querySelectorAll('[data-statement-line]').forEach((line, index) => {
            window.gsap.fromTo(line, { xPercent: index % 2 === 0 ? -8 : 8 }, {
                xPercent: 0,
                ease: 'none',
                scrollTrigger: {
                    trigger: line,
                    start: 'top bottom',
                    end: 'top 45%',
                    scrub: 0.65,
                },
            });
        });

        const campaignMedia = document.querySelector('[data-campaign-media] img');
        if (campaignMedia) {
            window.gsap.fromTo(campaignMedia, { yPercent: -5, scale: 1.04 }, {
                yPercent: 6,
                scale: 1,
                ease: 'none',
                scrollTrigger: {
                    trigger: campaignMedia.parentElement,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: 1,
                },
            });
        }

        let refreshTimer;
        window.addEventListener('resize', () => {
            window.clearTimeout(refreshTimer);
            refreshTimer = window.setTimeout(() => window.ScrollTrigger.refresh(), 180);
        });
        window.addEventListener('pagehide', () => window.ScrollTrigger.getAll().forEach((trigger) => trigger.kill()), { once: true });
    }

    initHeroEntrance();
    initCollectionEntrance();
    initLoader();
    initSmoothScroll();
    initScrollMotion();
})();
