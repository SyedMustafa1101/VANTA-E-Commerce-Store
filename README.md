# VANTA

VANTA is a portfolio-grade streetwear experience built on two fronts: an editorial, motion-led storefront and a real PHP/MySQL commerce backend. The project is intentionally being delivered in controlled phases so the design and engineering systems stay understandable.

## Current status — Phase 2 complete

Phase 2 preserves the approved foundation and adds the complete animated storefront:

- editorial home, shop, reusable collection, and product-detail routes;
- a structured local catalog with ten realistic products, variants, prices, availability, and related-product data;
- live search, category/collection/size/color/price/availability filters, and sorting;
- persistent guest cart and wishlist drawers powered by local storage;
- product-gallery, color, size, quantity, stock-state, size-guide, and add-to-bag interactions;
- responsive desktop/mobile choreography using GSAP, ScrollTrigger, Lenis, and reduced-motion fallbacks;
- keyboard-aware overlays, focus restoration, escaped output, lazy-loading, and intrinsic image sizing;
- original, locally optimized VANTA campaign and catalog imagery.

MySQL persistence, authentication, server-owned cart/checkout/order logic, reviews, and administration remain intentionally reserved for Phase 3 and later.

## Stack

- PHP 8.1+
- Apache (XAMPP compatible)
- Tailwind CSS 3.4.17
- GSAP 3 + ScrollTrigger
- Lenis
- MySQL (introduced in Phase 3)

GSAP 3.12.7, ScrollTrigger 3.12.7, and Lenis 1.1.20 are pinned and vendored locally for reliable motion. Phase 1 loads Tailwind and Google Fonts from public CDNs so the design can run without a Node installation; an internet connection is therefore required for the complete type treatment and Tailwind utility layer during this phase. The production CSS build path is already prepared for a later phase.

## Run with XAMPP

1. Install XAMPP with Apache, PHP 8.1 or newer, and MySQL.
2. Place this folder inside `C:\xampp\htdocs\vanta`, or configure an Apache alias that points to the current project folder.
3. Open the XAMPP Control Panel and start Apache. MySQL is not required until Phase 3.
4. Visit `http://localhost/vanta/`.
5. Open `http://localhost/vanta/design-system.php` to inspect the living style guide.

The project detects its Apache subdirectory automatically. For an unusual reverse-proxy or alias setup, set `VANTA_BASE_URL` to the public base path, such as `/vanta`.

### Optional local PHP server

If PHP is already on `PATH`, run:

```powershell
php -S localhost:8000
```

Then open `http://localhost:8000/`.

### Optional Tailwind build tooling

Node is not required to view Phase 2. To prepare a compiled stylesheet later:

```powershell
npm install
npm run css:build
```

The current pages continue to use the Tailwind Play CDN until the final production switch is made. This avoids committing a generated stylesheet before the page set is complete.

## Local database configuration strategy

Database work starts in Phase 3. When it does:

1. Copy `config/database.example.php` to `config/database.php`.
2. Put local XAMPP credentials only in `config/database.php`.
3. Never commit that file; it is already ignored by Git.
4. Import the future `database/vanta.sql` file through phpMyAdmin.

No real credentials are included in the repository.

## Architecture

See [docs/architecture.md](docs/architecture.md) for the planned final structure and responsibility boundaries.
See [docs/phase-2-storefront.md](docs/phase-2-storefront.md) for storefront routes, local state, image-replacement specifications, generation prompts, and QA coverage.

## Design assets

All Phase 2 campaign/catalog images are original generated demo assets with local optimized copies. Replace campaign photography without changing layout by preserving a wide landscape ratio near `16:9`, at least `1600 × 900`, and keeping useful dark negative space for overlaid text.

Future catalog imagery should use:

- portrait product/editorial images: `1600 × 2000` (`4:5`);
- product hover pairs with identical crop and dimensions;
- detail gallery images: at least `1800px` on the long edge;
- lowercase, hyphenated filenames without spaces.

## Phase roadmap

1. Foundation + design system — approved.
2. Animated storefront — complete, awaiting approval.
3. Commerce backend — MySQL, auth, variants, inventory, cart, checkout, orders, reviews.
4. Admin system — protected administration, CRUD, stock, sales, customers, analytics.
5. Polish + QA — accessibility, security, performance, responsive testing, final documentation.
