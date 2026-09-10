# Phase 2 — animated storefront

Phase 2 is a frontend-only commerce experience. It uses structured PHP sample data and browser-local guest state so the interaction model can be reviewed before Phase 3 introduces MySQL, authentication, orders, or administrative workflows.

## Routes

- `/` — editorial homepage and Drop 001 story;
- `/shop.php` — complete catalog with client-side filters and sorting;
- `/collection.php?collection=new-drop` — New Drop collection;
- `/collection.php?collection=essentials` — Essentials collection;
- `/collection.php?collection=after-dark` — After Dark collection;
- `/collection.php?collection=limited` — Limited collection;
- `/product.php?slug=vanta-oversized-essential-tee` — representative product detail route;
- `/design-system.php` — preserved Phase 1 visual-system reference.

`includes/catalog.php` is the single Phase 2 product-data source. Product cards are rendered through `includes/product-card.php`. Guest cart and wishlist state use the `vanta.phase2.cart` and `vanta.phase2.wishlist` local-storage keys. Those values are presentation state only; Phase 3 must validate price, inventory, identity, and orders on the server.

## Image replacement guide

| Asset role | Current demo size | Replacement target | Crop guidance |
| --- | ---: | ---: | --- |
| Hero campaign | 1672 × 941 | 1920 × 1080 or larger | 16:9, subject to the right, dark negative space on the left |
| Catalog/editorial portraits | 1122 × 1402 | 1600 × 2000 | 4:5 portrait, full garment silhouette, restrained background |
| Product hover pairs | 1122 × 1402 | 1600 × 2000 | identical crop, camera height, and subject scale across the pair |
| Product-detail gallery | 1122 × 1402 | 1800px or more on the long edge | consistent 4:5 crop; include front, alternate, and detail views |

Use optimized JPEG or WebP files, lowercase hyphenated names, and accurate intrinsic `width`/`height` attributes. Keep text, labels, and third-party branding out of replacement images.

## Generated demo assets

The three catalog assets were created with the built-in ImageGen workflow in `photorealistic-natural` or `product-mockup` mode, then converted non-destructively to local quality-86 JPEGs:

- `assets/images/catalog/vanta-essential-look.jpg` — "VANTA streetwear campaign portrait for a premium product grid: adult model wearing a heavy oversized black T-shirt and wide black cargo trousers in a brutalist concrete passage at night; low-key hard side light, charcoal and black palette, subtle electric-lime reflection, realistic fabric texture, full garment silhouette, vertical 4:5 composition, editorial fashion photography, restrained and cinematic; no visible logos, no text, no watermark.";
- `assets/images/catalog/vanta-after-dark-look.jpg` — "VANTA streetwear campaign portrait for a secondary hover image: adult model shown at a three-quarter rear angle wearing a structured matte-black bomber over a charcoal hoodie in an industrial loading bay at night; cold rim light, deep shadows, realistic nylon and heavyweight cotton texture, vertical 4:5 composition, premium editorial fashion photography; no visible logos, no text, no watermark.";
- `assets/images/catalog/vanta-accessories-still-life.jpg` — "Premium VANTA accessories still life for an ecommerce product grid: a matte-black six-panel cap and compact black crossbody bag arranged on off-white plaster plinths, hard directional studio lighting, crisp sculptural shadows, visible technical fabric texture, subtle electric-lime zipper pull accent, vertical 4:5 composition, minimalist luxury product photography; no visible logos, no text, no watermark.".

## Phase 2 QA

The storefront is exercised at 1440px desktop and 390px mobile widths. Coverage includes direct and deep URLs, animated navigation plus browser back/forward history, search, filters, sorting, product selection and out-of-stock states, size-guide dismissal, cart and wishlist persistence behaviors, newsletter validation, reduced-motion mode, runtime/console/resource errors, and horizontal overflow. The local browser harness is `tools/phase2-browser-qa.cjs`; it expects the site at `http://127.0.0.1:8000`.
