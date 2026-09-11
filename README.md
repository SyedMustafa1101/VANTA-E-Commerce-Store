# VANTA

VANTA is a portfolio-grade streetwear experience with an editorial, motion-led storefront and a real PHP/MySQL commerce backend. It is deliberately framework-free and XAMPP-friendly so the important architecture remains easy to explain.

## Current status — Phase 3 complete

The approved Phase 2 design, responsive layouts, product interactions, overlays, and GSAP/Lenis choreography are preserved. Phase 3 replaces browser-only commerce state with:

- MySQL-backed products, collections, categories, images, exact variants, and stock;
- customer registration, login/logout, hardened sessions, profiles, and owned addresses;
- a PHP-session guest cart and wishlist that merge into persistent account data on login;
- server-priced cart totals, coupons, Pakistan shipping, guest checkout, and authenticated checkout;
- transaction-protected orders, item snapshots, inventory deduction, confirmation, and order history;
- post-commit PHPMailer/SMTP order-confirmation emails with duplicate-delivery protection;
- purchase-gated reviews with moderation states and persistent newsletter subscriptions;
- JSON endpoints with CSRF checks and polished database/error/empty states.

Phase 4 administration is intentionally not included.

## Stack

- PHP 8.0+; PHP 8.1+ recommended
- Apache and MySQL/MariaDB through XAMPP
- PDO with native prepared statements
- Composer 2 and PHPMailer 7.1
- Tailwind CSS 3.4.17
- GSAP 3.12.7 with ScrollTrigger
- Lenis 1.1.20

The motion libraries are pinned and vendored locally. The current pages still use the Tailwind Play CDN and Google Fonts, so an internet connection is required for the complete type treatment and utility layer. Node is only needed for the optional stylesheet build and browser QA.

## XAMPP setup

1. Put this repository at C:\xampp\htdocs\vanta.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open phpMyAdmin, create a database named vanta using utf8mb4_unicode_ci, select it, and import database/vanta.sql.
4. Copy config/database.example.php to config/database.php.
5. Edit only the ignored config/database.php with your local credentials. Typical XAMPP values are host localhost, database vanta, user root, and an empty local password.
6. Run composer install from the repository root.
7. Copy config/mail.example.php to config/mail.php and configure SMTP as described below.
8. Open http://localhost/vanta/.

The import is safe to rerun for catalog/settings seeds and does not reset live variant stock. It does not create the database itself, which keeps it compatible with both phpMyAdmin and restricted database users. No real credentials are committed.

If the connection or schema is missing, VANTA displays a developer-friendly setup screen instead of a PDO exception. For an unusual Apache alias, set VANTA_BASE_URL to the public base path, such as /vanta.

### Optional local PHP server

From the repository root:

~~~powershell
C:\xampp\php\php.exe -S 127.0.0.1:8000
~~~

Then open http://127.0.0.1:8000/.

### Optional Tailwind build

~~~powershell
npm install
npm run css:build
~~~

## Transactional SMTP setup

Order confirmation mail uses PHPMailer through Composer; PHP mail() is not used.

1. Install Composer from https://getcomposer.org/ if it is not already available.
2. From the repository root, run composer install --no-dev --optimize-autoloader.
3. Copy config/mail.example.php to config/mail.php.
4. Set enabled to true and enter the SMTP host, port, encryption mode, authentication credentials, From address, and optional Reply-To address.
5. Keep config/mail.php local. It is ignored by Git together with vendor and the temporary Composer tooling directory.

For Gmail SMTP, use smtp.gmail.com, port 587, encryption tls, and authentication enabled. The username and From address should normally be the full Gmail address. Turn on Google 2-Step Verification and create a 16-character App Password for the SMTP password; do not use or commit the normal Google account password. App Password availability can be restricted for managed, security-key-only, or Advanced Protection accounts. Google documents the current requirements at https://support.google.com/accounts/answer/185833 and SMTP settings at https://support.google.com/mail/answer/7104828.

For a local mail catcher such as Mailpit or MailHog, use host 127.0.0.1, port 1025, encryption none, authentication false, and enabled true.

Mail is attempted synchronously only after the order transaction commits. A failed transport is recorded and logged without its SMTP error details, while the customer still receives a successful order page with a quiet delivery notice.

## Demo commerce

- Seed coupon: VANTA10 — 10% off eligible orders.
- Standard Pakistan Delivery: PKR 300.
- Shipping is free from the database-configured PKR 15,000 threshold.
- Payment choices are Cash on Delivery and clearly labeled Demo Card.
- Demo Card does not request, transmit, or store card numbers.
- No demo customer is seeded; register through the storefront to test an account.
- Reviews enter pending moderation and are not public until approved. Phase 4 will provide moderation UI.

## Routes

Storefront routes remain /, /shop.php, /collection.php, and /product.php. Customer routes are /register.php, /login.php, /logout.php, /cart.php, /checkout.php, and /order-confirmation.php. Account pages live under /account/ and cover overview, profile, addresses, wishlist, order history, and protected order detail.

Server-backed JSON endpoints live under /api/ for cart retrieval/mutations, wishlist toggles, coupon validation, review submission, and newsletter subscription.

## Verification

With the database configured and the local PHP server running:

~~~powershell
C:\xampp\php\php.exe tools\phase3-integration.php
node tools\smtp-capture-server.cjs
C:\xampp\php\php.exe tools\phase3-mail-smtp-qa.php
node tools\phase2-browser-qa.cjs
node tools\phase3-browser-qa.cjs
~~~

Run the SMTP capture server and PHPMailer smoke test in separate terminals. The integration harness uses isolated QA records, restores tested stock, and cleans up after itself. The browser harnesses expect http://127.0.0.1:8000 and exercise desktop at 1440px and mobile at 390px.

## Documentation

- docs/architecture.md explains the implemented boundaries.
- docs/phase-2-storefront.md preserves the approved visual/storefront reference.
- docs/phase-3-commerce.md explains schema relationships, authentication, cart merge, transactional checkout, inventory, coupons, reviews, and security.
- database/README.md contains database-only import notes.

## Design assets

All campaign and catalog imagery remains local. Preserve a 4:5 crop for product/editorial images, matching crops for hover pairs, and at least 1800px on the long edge for future gallery replacements.

## Phase roadmap

1. Foundation and design system — approved.
2. Animated storefront — approved and committed.
3. Database-backed commerce and customer system — complete, awaiting review.
4. Admin system — deferred; no Phase 4 UI is included.
5. Final polish and QA — future phase.
