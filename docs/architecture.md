# VANTA architecture

VANTA uses a shallow, framework-free PHP architecture. Storefront templates preserve the approved experience, repositories isolate SQL, and services own validation, authorization-sensitive rules, and transaction boundaries.

~~~text
VANTA/
├── index.php, shop.php, collection.php, product.php
├── cart.php, checkout.php, order-confirmation.php
├── account/                         # Protected customer workspace
├── api/                             # CSRF-protected commerce JSON endpoints
├── admin/
│   ├── login.php, logout.php        # Independent staff session
│   ├── index.php                    # Dashboard
│   ├── products.php, product-edit.php, categories.php, collections.php
│   ├── inventory.php, orders.php, order.php
│   ├── customers.php, customer.php, coupons.php, reviews.php
│   ├── newsletter.php, analytics.php, settings.php, admins.php
│   ├── export.php                   # Allowlisted CSV exports
│   └── assets/                      # Local admin CSS and JavaScript
├── includes/
│   ├── bootstrap.php, db.php, functions.php
│   ├── repositories/ and services/ # Customer commerce boundaries
│   ├── admin/                       # AdminRepository, AdminService, helpers
│   └── mail/                        # PHPMailer adapter and templates
├── uploads/products/                # Runtime images, scripts denied
├── database/vanta.sql               # Complete fresh-install schema
├── database/migrations/phase4_admin.sql
└── tools/                            # Setup and QA harnesses
~~~

## Request and identity boundaries

Every public route loads `includes/bootstrap.php`. Admin routes additionally load `admin/_init.php`, which initializes the admin layer and requires the staff session for every protected route. `current_admin()` reads a different session key from `current_user()`; signing into admin never grants a customer session and vice versa. Successful authentication regenerates the session ID. Staff passwords use `password_hash()`/`password_verify()`, failures return one generic message, and repeated attempts are throttled in `admin_login_attempts`.

ADMIN can operate the catalog, stock, orders, customers, coupons, reviews, subscribers, analytics, safe settings, exports, and its own profile. SUPER_ADMIN additionally manages staff accounts. Server-side checks protect the route; hidden navigation is only a presentation detail.

## Responsibility boundaries

Customer repositories and services retain the Phase 3 behavior. `AdminRepository` contains prepared SQL and explicit allowlists for dynamic table/order choices. `AdminService` validates mutations, creates collision-safe slugs, enforces SKU uniqueness, handles transactions, records audit entries, and owns image lifecycle rules. Templates render escaped values and use CSRF-protected POST forms. JavaScript supplies drawers, dialogs, confirmations, and canvas charts but is never authoritative for permissions, stock, money, status transitions, or deletion.

## Data relationships

~~~text
admins ── admin_audit_logs
   └───── admin_login_attempts

categories ── products ── product_images
collections ─┘       ├── product_variants ── inventory_adjustments
                     ├── product_relations
                     ├── reviews
                     └── order_items snapshots

users ── addresses
  ├───── carts/wishlists
  └───── orders ── order_items
                ├── order_status_history
                └── order_email_deliveries

coupons ── coupon_usage
settings
newsletter_subscribers
~~~

Products can be draft, active, or archived. Public catalog queries require an active product plus active category and collection. Variants own SKUs, color/size attributes, prices, stock, activity, and an optional image association. Uploaded images retain their metadata and can be assigned primary, hover, gallery, or detail roles.

## Transaction and inventory boundaries

Checkout begins a transaction, locks each requested variant with `SELECT ... FOR UPDATE`, reloads current availability and price, validates coupon and shipping settings, inserts order/item snapshots, conditionally decrements stock, records coupon use, and queues one confirmation delivery. Any failure rolls back the entire order.

Admin stock adjustments also run in a transaction: the variant is locked, negative stock is rejected, the new quantity is written, and a reasoned adjustment row is inserted. Admin cancellation accepts only allowed transitions, restores each item quantity, creates adjustment rows, writes status history, and sets `inventory_restored_at`. That marker makes a repeated cancellation request harmless and prevents double-restocking.

## Image storage boundary

Product uploads are stored under `uploads/products/` using generated random names. The server validates the original extension, actual MIME through `finfo`, byte size, and image dimensions before accepting JPEG, PNG, or WebP. Executable extensions are denied by `uploads/.htaccess`. The database stores a relative path plus MIME, size, width, height, role, primary state, and variant association. Deletion removes only a validated managed upload after database success; seeded storefront assets are never unlinked.

## Security boundaries

- native PDO prepared statements and allowlists for sort, export, status, and table choices;
- CSRF validation on every state-changing admin and customer request;
- role enforcement on the server and separate customer/admin session identities;
- generic credential errors, persisted throttling, session regeneration, HttpOnly/SameSite cookies;
- output escaping, upload content validation, randomized filenames, and executable-file denial;
- server-owned prices, totals, inventory, status transitions, and delete dependency checks;
- explicit prevention of deleting referenced products, categories, collections, or the last active SUPER_ADMIN;
- CSV formula-injection neutralization;
- audit rows for sensitive admin mutations and safe errors without SQL, paths, traces, or SMTP secrets.

## Frontend boundary

The original storefront CSS, product compositions, drawers, responsive behavior, and motion code remain the public interface. Admin uses its own local visual system: near-black shell, off-white work surface, acid-lime actions, sharp cards/tables, sticky responsive navigation, minimal motion, and real server-fed canvas charts. It does not reuse storefront hero treatments or alter customer workflows beyond honoring safe commerce settings and newly managed catalog/stock data.
