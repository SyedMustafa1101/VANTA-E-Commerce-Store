# VANTA

VANTA is a portfolio-grade streetwear storefront and administration system built with framework-free PHP, MySQL/MariaDB, and progressive JavaScript. The approved storefront remains intact; Phase 4 adds a production-minded `/admin/` workspace backed by the real commerce data.

## Current status — Phase 4 complete

- Phase 2 storefront design, responsive layouts, GSAP/Lenis choreography, filters, drawers, and product interactions are preserved.
- Phase 3 customer accounts, persistent cart/wishlist, coupons, transactional checkout, inventory deduction, order snapshots, reviews, newsletter, and PHPMailer confirmation delivery remain active.
- Phase 4 adds separate admin authentication, SUPER_ADMIN/ADMIN roles, dashboard analytics, catalog and variant management, validated image uploads, inventory adjustments, order workflows with exact-once cancellation restocking, customers, coupons, review moderation, newsletter export, safe store settings, staff management, audit history, and CSV exports.
- Phase 5 polish is intentionally not included.

## Stack

- PHP 8.0+ (8.1+ recommended)
- Apache and MySQL/MariaDB through XAMPP
- PDO native prepared statements
- Composer 2 and PHPMailer 7.1
- Tailwind CSS 3.4.17, GSAP 3.12.7, ScrollTrigger, and Lenis 1.1.20

The motion libraries are local. The approved storefront still requests Tailwind Play CDN and Google Fonts, so its complete utility/type treatment needs internet access. Admin CSS and JavaScript are local and do not depend on a frontend framework.

## Fresh XAMPP setup

1. Put the project at `C:\xampp\htdocs\vanta` and start Apache and MySQL.
2. Create an empty `vanta` database using `utf8mb4_unicode_ci` and import `database/vanta.sql`.
3. Copy `config/database.example.php` to the ignored `config/database.php` and enter local database credentials.
4. Run `composer install`.
5. Copy `config/mail.example.php` to the ignored `config/mail.php` and configure SMTP if confirmation mail is required.
6. From the project root, run `C:\xampp\php\php.exe tools\create-admin.php` and enter the first SUPER_ADMIN email and password at the prompts.
7. Open `http://localhost/vanta/admin/login.php`.

No default admin password is shipped. Admin and customer accounts are deliberately separate.

## Upgrade an approved Phase 3 database

Back up the database, then import `database/migrations/phase4_admin.sql` into the existing `vanta` database. The migration is additive and idempotent: it preserves products, users, orders, stock, reviews, coupon usage, subscribers, and mail-delivery rows. Run `tools/create-admin.php` after the migration to create the first staff login.

The complete `database/vanta.sql` is for a fresh installation. Do not replace an existing database with it when upgrading; use the migration.

## Transactional SMTP setup

Order confirmation mail uses PHPMailer after the order transaction commits. Copy `config/mail.example.php` to `config/mail.php`, keep it local, and configure SMTP host, port, encryption, authentication, From, and optional Reply-To fields. A failed transport is recorded without exposing SMTP error details and cannot roll back the order. The confirmation page never sends mail and duplicate delivery claims are prevented in the database.

## Routes

- Storefront: `/`, `/shop.php`, `/collection.php`, `/product.php`
- Customer commerce: `/register.php`, `/login.php`, `/cart.php`, `/checkout.php`, `/order-confirmation.php`, `/account/`, `/api/`
- Admin: `/admin/login.php`, `/admin/`, `/admin/products.php`, `/admin/inventory.php`, `/admin/orders.php`, `/admin/customers.php`, `/admin/coupons.php`, `/admin/reviews.php`, `/admin/newsletter.php`, `/admin/analytics.php`, `/admin/settings.php`
- SUPER_ADMIN only: `/admin/admins.php`

See `docs/phase-4-admin.md` for the route matrix, workflows, permissions, upload rules, tests, and operational checklist.

## Verification

With `http://127.0.0.1:8000` serving the project and the database configured:

~~~powershell
C:\xampp\php\php.exe tools\phase3-integration.php
C:\xampp\php\php.exe tools\phase4-integration.php
node tools\phase2-browser-qa.cjs
node tools\phase3-browser-qa.cjs
node tools\phase4-browser-qa.cjs
~~~

For mail transport QA, run `node tools\smtp-capture-server.cjs` and `C:\xampp\php\php.exe tools\phase3-mail-smtp-qa.php` in separate terminals. The harnesses use named QA records and clean up after themselves.

## Documentation

- `docs/architecture.md` — application boundaries and transaction model
- `docs/phase-3-commerce.md` — customer commerce implementation
- `docs/phase-4-admin.md` — admin operations and security reference
- `database/README.md` — fresh import and migration guidance
- `docs/phase-2-storefront.md` — approved visual/storefront reference

## Phase roadmap

1. Foundation and design system — approved
2. Animated storefront — approved
3. Database-backed commerce and customer system — approved
4. Admin system — implemented and verified
5. Final polish and QA — future phase
