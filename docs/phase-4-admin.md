# Phase 4 — Admin system

Phase 4 turns the approved Phase 3 project into an operable store without replacing its storefront or commerce core. All admin reporting and mutations use the same MySQL data as customer-facing pages.

## Initial access

Run this from the project root after importing the Phase 4 schema or migration:

~~~powershell
C:\xampp\php\php.exe tools\create-admin.php
~~~

The CLI prompts for name, email, and a password of at least 12 characters, hashes it with PHP's password API, and creates a SUPER_ADMIN only when no staff account exists. No real or demo password is committed. Continue at `/admin/login.php`.

Customer credentials do not work on the admin login. Admin credentials do not create a storefront customer session.

## Roles and routes

| Area | Route | ADMIN | SUPER_ADMIN |
|---|---|---:|---:|
| Dashboard | `/admin/` | Yes | Yes |
| Products and variants | `/admin/products.php`, `/admin/product-edit.php` | Yes | Yes |
| Categories and collections | `/admin/categories.php`, `/admin/collections.php` | Yes | Yes |
| Inventory and adjustments | `/admin/inventory.php` | Yes | Yes |
| Orders and timelines | `/admin/orders.php`, `/admin/order.php` | Yes | Yes |
| Customers | `/admin/customers.php`, `/admin/customer.php` | Yes | Yes |
| Coupons | `/admin/coupons.php` | Yes | Yes |
| Review moderation | `/admin/reviews.php` | Yes | Yes |
| Newsletter | `/admin/newsletter.php` | Yes | Yes |
| Analytics | `/admin/analytics.php` | Yes | Yes |
| Store settings | `/admin/settings.php` | Yes | Yes |
| Profile/password | `/admin/profile.php` | Yes | Yes |
| Staff accounts | `/admin/admins.php` | No (403) | Yes |
| CSV exports | `/admin/export.php?type=…` | Yes | Yes |

Protected routes redirect anonymous visitors to the dedicated login. Forbidden role access returns an admin-styled 403. Failed authentication uses generic messaging and a persisted 15-minute throttle window.

## Dashboard and analytics

The dashboard computes live revenue, order, customer, product, stock, subscriber, coupon, and review statistics. Recent orders, low-stock variants, pending reviews, and trend data are direct queries. Analytics provides sales, orders, customer, product, category, collection, coupon, and time-series summaries with server-fed canvas charts. No metric is hard-coded.

## Catalog workflow

Products support search, category/collection/status filters, pagination, column sorting, active/archived bulk actions, and safe dependency-aware deletion. The editor separates:

- details: name, slug, descriptions, prices, category, collection, status, featured state, and SEO fields;
- variants: unique SKU, color, size, price override, stock, activity, and optional image association;
- images: validated upload, alt text, role, color association, sort position, primary selection, and deletion.

Slugs are normalized and collision-safe; SKUs are globally unique. A product must be active and belong to active taxonomy to appear publicly. Archiving is the normal removal path. Hard deletion is refused when orders, reviews, wishlist items, or cart items reference the product.

Categories and collections have search, active state, sort order, descriptions, featured collection state, and safe delete checks. In-use taxonomy cannot be removed.

## Image upload rules

- accepted: JPEG, PNG, WebP;
- maximum size: 8 MB;
- required: extension matches a server-detected MIME and the file has valid image dimensions;
- storage: randomized filename under `uploads/products/`;
- execution: denied by the uploads `.htaccess` defense;
- cleanup: managed uploaded files are removed when their image row is deleted; seeded assets are protected.

The application should be granted write access only to `uploads/products/`, not to the whole project.

## Inventory and order operations

Inventory shows all exact variants with search, stock state, activity filter, sorting, and pagination. Adjustments require a non-zero signed quantity and reason. The row update and immutable log entry share one transaction; negative resulting stock is rejected.

Order states follow an explicit server-side transition map. Every accepted change creates an order status-history record with actor and note. Cancellation restores exact variant quantities once and logs the restoration. `orders.inventory_restored_at` prevents repeated cancellation requests from adding stock again. Payment status can be updated independently using allowlisted values. Existing confirmation-email delivery state is visible on the order detail page.

## Customers, engagement, and marketing

Customer detail combines profile, addresses, orders, lifetime value, wishlist count, and review count. Disable/enable affects future login while preserving orders and historical records. Reviews can be filtered and moved between pending, approved, and rejected; only approved reviews are public. Newsletter subscribers can be searched, activated/deactivated, and exported. Coupon management validates code, type, value, minimum order, date order, global/per-customer limits, and active state.

## Settings and exports

Only an explicit safe-key list is editable: store identity, contact/support email, currency code, shipping charge, free-shipping threshold, low-stock threshold, tagline, and default country. Shipping values feed server-side checkout; default country feeds the checkout default/method label. Database credentials, SMTP host/user/password, filesystem paths, and security configuration are never displayed or editable.

CSV exports support orders, customers, newsletter subscribers, and inventory. Export types and sort choices are allowlisted. Cells beginning with spreadsheet formula characters are prefixed to prevent formula injection.

## Audit and security checklist

- Verify `config/database.php` and `config/mail.php` remain ignored and unreadable from the web.
- Serve the site over HTTPS in production so the session cookie receives `Secure`.
- Restrict `uploads/products/` to image writes and retain its script-execution denial.
- Create named staff accounts; do not share SUPER_ADMIN credentials.
- Review `admin_audit_logs`, `inventory_adjustments`, and `order_status_history` for operational changes.
- Back up the database before migration and before large catalog/order operations.
- Keep PHP, MariaDB/MySQL, Composer dependencies, and the web server supported and patched.
- Configure production web-server limits consistently with the 8 MB application upload maximum.

## Verification commands

~~~powershell
C:\xampp\php\php.exe tools\phase4-integration.php
node tools\phase4-browser-qa.cjs
~~~

The integration suite covers schema, authentication separation, CRUD/search, duplicate SKU rejection, inventory logs, order cancellation/restock idempotency, customer disablement, coupon validation, moderation visibility, safe deletion, settings, analytics, sort allowlists, CSRF, and logout. Browser QA checks anonymous protection, both roles, all routes, desktop/mobile layout, chart rendering, image upload/deletion, CSV, 403 behavior, resource errors, console errors, and overflow. Run the Phase 2 and Phase 3 suites as regression gates.

## Known limitations

- PHP-session authentication is designed for one store deployment; multi-region session infrastructure is outside Phase 4.
- Uploads use local disk; object storage/CDN synchronization is not included.
- Charts are intentionally lightweight canvas visualizations rather than a reporting warehouse.
- Order transitions, refunds, carriers, tax engines, and payment gateways remain deliberately narrow/demo-grade where Phase 3 was narrow/demo-grade.
- Existing storefront CDN/font requests still require internet access, as in the approved Phase 2 build.
- Phase 5 accessibility, performance, cross-browser, and visual polish work has not been started.
