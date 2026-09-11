# VANTA architecture

VANTA uses a shallow, framework-free PHP architecture. Page templates compose the approved storefront, repositories isolate SQL, and services own business rules and transaction boundaries.

~~~text
VANTA/
├── index.php, shop.php, collection.php, product.php
├── login.php, register.php, logout.php
├── cart.php, checkout.php, order-confirmation.php
├── account/                    # Protected profile, addresses, wishlist, orders
├── api/                        # Small JSON endpoints grouped by domain
├── includes/
│   ├── bootstrap.php           # Configuration, sessions, loading, error boundary
│   ├── db.php                  # PDO connection and settings access
│   ├── functions.php           # Escaping, CSRF, auth, redirects, service factories
│   ├── repositories/           # Prepared SQL and row hydration
│   ├── services/               # Auth, cart, wishlist, coupon, order, and mail rules
│   ├── mail/                   # PHPMailer SMTP adapter and order email templates
│   └── shared view components
├── config/
│   ├── database.example.php    # Safe committed template
│   └── database.php            # Ignored local credentials
│   ├── mail.example.php        # Safe SMTP template
│   └── mail.php                # Ignored local SMTP credentials
├── composer.json / composer.lock
├── database/vanta.sql          # Importable schema and Phase 2 catalog seeds
├── assets/                     # Approved CSS, JavaScript, motion, and images
└── tools/                      # Integration and Playwright QA harnesses
~~~

## Request lifecycle

Every PHP route loads includes/bootstrap.php. Bootstrap configures strict session behavior, loads helpers/repositories/services, verifies the database, and installs an exception boundary that returns either safe JSON or the branded setup state.

GET routes ask repositories/services for view data and escape dynamic output with e(). State-changing HTML forms validate the session CSRF token. JSON mutations accept the same token through the X-CSRF-Token header or request body.

## Responsibility boundaries

Repositories contain prepared PDO statements and database hydration only:

- ProductRepository loads products, images, exact variants, collections, related products, and catalog metadata in bulk.
- UserRepository and AddressRepository own customer records and ownership-scoped address queries.
- CartRepository and WishlistRepository persist authenticated state.
- CouponRepository resolves coupons and usage.
- OrderRepository reads customer-owned orders and owns atomic email-delivery status changes.
- ReviewRepository and NewsletterRepository persist engagement data.

Services contain rules:

- AuthService validates credentials, throttles repeated failures, regenerates sessions, and merges guest state.
- CartService validates product/variant relationships, quantity, activity, stock, and current server price.
- WishlistService provides duplicate-safe session/database behavior.
- CouponService evaluates dates, thresholds, type, global use, and per-customer use.
- OrderService recalculates the full quote and creates orders inside one database transaction.
- MailService renders confirmation content and invokes the PHPMailer SMTP transport after commit.

Templates do not contain commerce SQL, and JavaScript is never authoritative for price, discount, stock, ownership, or totals.

## Database relationship map

~~~text
users
├── addresses
├── carts ── cart_items ── product_variants
├── wishlists ── wishlist_items ── products
├── orders ── order_items
│          └── order_email_deliveries
├── reviews ── products
├── coupon_usage ── coupons
└── login_attempts

categories ── products ── product_images
collections ─┘       ├── product_variants
                     ├── product_relations
                     ├── reviews
                     └── order_items snapshots

settings
newsletter_subscribers
~~~

Products and variants are separate because color/size combinations own SKUs and stock. Cart lines reference exact variants. Order items retain variant references where possible but also store immutable product, option, SKU, unit-price, quantity, and line-total snapshots.

## Transaction and inventory boundary

OrderService begins a transaction, locks each requested variant with SELECT ... FOR UPDATE, repeats all availability and quantity checks, reloads current prices, validates the coupon and shipping threshold, inserts the order and its snapshots, performs conditional stock decrements, and queues one pending confirmation delivery. Any invalid line or failed decrement throws and rolls back the whole operation. The cart is cleared only after a successful order.

Once commit succeeds, MailService atomically claims that delivery and sends the branded HTML/plain-text message through PHPMailer SMTP. Failure is recorded and safely logged but cannot roll back the order. The confirmation page reads delivery status and never sends mail.

## Security boundaries

- native PDO prepared statements and allowlists for dynamic choices;
- password_hash and password_verify, generic login errors, and a persisted throttling foundation;
- CSRF validation on registration, login, logout, account changes, cart, wishlist, checkout, and reviews;
- HttpOnly, SameSite=Lax, HTTPS-aware Secure cookies, strict session mode, and ID regeneration;
- ownership-scoped address and order access;
- server-owned money and inventory calculations;
- escaped customer, address, and review output;
- public review queries return approved content only;
- no card number collection or storage;
- ignored SMTP credentials and non-disclosing mail failure logs;
- safe database-unavailable and exception responses without credentials, SQL, paths, or traces.

## Frontend boundary

assets/js/app.js retains global navigation and feedback. assets/js/motion.js retains the optional GSAP/Lenis layer and reduced-motion behavior. assets/js/storefront.js retains search, filters, drawers, product options, and form feedback but synchronizes commerce through the Phase 3 JSON endpoints. The catalog remains a small hybrid: PHP loads the ten products from MySQL once, renders reusable cards, and exposes the same safe view model to the approved client filter/search experience.

The account and checkout pages reuse the existing tokens and components with calmer motion and density. Phase 4 administration remains deliberately outside this architecture.
