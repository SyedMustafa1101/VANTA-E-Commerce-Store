# Phase 3 — database-backed commerce and customer system

Phase 3 turns the approved animated storefront into a genuine PHP/MySQL shop. It preserves Phase 2 presentation while moving identity, persistence, prices, discounts, shipping, stock, and orders behind server-controlled boundaries.

## Schema

database/vanta.sql is directly importable after selecting an empty vanta database in phpMyAdmin. It uses InnoDB, utf8mb4, foreign keys, unique constraints, check constraints, decimal money columns, status enums, timestamps, and indexes on the frequent lookup fields.

| Domain | Tables | Purpose |
| --- | --- | --- |
| Identity | users, addresses, login_attempts | Accounts, owned delivery addresses, login throttling |
| Catalog | categories, collections, products, product_images, product_variants, product_relations | Ten seeded products, media, exact SKU/option stock, related items |
| Shopping | carts, cart_items, wishlists, wishlist_items | Persistent authenticated cart and wishlist |
| Pricing | coupons, coupon_usage, settings | Coupon policy, redemption history, shipping configuration |
| Orders | orders, order_items, order_email_deliveries | Customer/guest orders, immutable line snapshots, and confirmation delivery state |
| Engagement | reviews, newsletter_subscribers | Moderated product feedback and subscriptions |

~~~text
users
├── addresses
├── carts ── cart_items ── product_variants ── products
├── wishlists ── wishlist_items ─────────────── products
├── orders ── order_items ──────────────────── products/variants
│          └── order_email_deliveries
├── reviews ─────────────────────────────────── products
└── coupon_usage ── coupons

categories ── products ── product_images
collections ─┘       ├── product_variants
                     └── product_relations
~~~

The seeds migrate all ten approved products, four collections, five categories, images, descriptions, materials, care instructions, keywords, sale prices, relationships, and 54 exact color/size variants with SKUs and stock. VANTA10 is a ten-percent coupon. Settings seed PKR 300 standard delivery and a PKR 15,000 free-shipping threshold.

## Authentication

Registration trims names, normalizes and validates email, checks duplicates, applies password requirements, hashes through password_hash, regenerates the session ID, and signs the customer in. Login uses a generic invalid-credentials response, password_verify, persisted recent-attempt throttling, a safe internal return target, and session regeneration. Logout is a CSRF-protected POST that clears authentication and rotates the session.

The session stores only the customer identifier and small guest-commerce state. Cookies are HttpOnly, SameSite=Lax, Secure whenever HTTPS is active, and strict-session compatible. Protected account pages use require_auth.

## Cart and wishlist

Guests use PHP session state, not browser local storage. Authenticated users use carts/cart_items and wishlists/wishlist_items. A successful login merges guest lines into the account:

- the exact variant identifies a cart line;
- matching quantities are combined within the quantity and current-stock limits;
- invalid, inactive, or unavailable entries are ignored;
- wishlist product IDs are inserted with duplicate-safe constraints;
- guest state is removed after the merge.

Each cart response rehydrates the exact product and variant from MySQL. Client-supplied price, title, SKU, subtotal, discount, shipping, or total values are not accepted. Quantity must be from 1 to 10 and cannot exceed current stock.

## Coupons and shipping

CouponService normalizes codes and checks active state, start/expiry times, order minimum, global usage limit, and authenticated per-customer limit. It supports percentage and fixed discounts, caps the result at the subtotal, and cannot produce a negative total.

Shipping is recalculated on the server from settings. Standard Pakistan Delivery costs PKR 300 below the configured threshold and is free at or above it.

## Checkout and order transaction

Checkout supports authenticated customers and guests. Account addresses can prefill the shipping form. Cash on Delivery and Demo Card are the only payment choices. Demo Card is simulated without card-detail fields or persistence.

Order placement performs this sequence:

1. Validate customer and shipping fields, the session cart, and the selected payment method.
2. Begin a PDO transaction.
3. Reload and lock every exact variant using SELECT ... FOR UPDATE.
4. Revalidate product/variant activity, quantity, stock, current price, coupon, shipping, and final totals.
5. Insert a readable unique order number and address/contact snapshots.
6. Insert immutable order-item snapshots.
7. Decrement each variant with a conditional stock update that cannot cross below zero.
8. Record coupon usage and queue one pending confirmation delivery.
9. Commit, then clear the purchased guest cart and authorize the confirmation session.
10. Atomically claim and send the confirmation through PHPMailer/SMTP.

Any error rolls back the order, items, coupon use, and stock changes together. Refreshing the confirmation page only reads the existing order and cannot submit a duplicate.

Authenticated order queries include the current user ID, so changing a URL ID cannot reveal another customer's order. Guest confirmations require the session authorization created by successful checkout.

## Transactional order email

Composer installs PHPMailer 7.1. SmtpMailTransport contains SMTP configuration and protocol concerns, OrderConfirmationEmailBuilder creates the message, and MailService coordinates persistent delivery state.

The responsive table-based HTML template uses VANTA near-black, off-white, and restrained lime styling. It includes customer name, order number/date, product and color/size snapshots, SKU, quantities, unit and line prices, subtotal, discount, shipping, total, shipping address, payment method/status, and order status. A complete plain-text alternative is attached to the same message.

The order_email_deliveries row is inserted as pending inside the order transaction. After commit, MailService changes it to sending with one atomic conditional update. Only the process that wins this claim may call SMTP. Success records sent and sent_at; failure records failed. Sent, failed, or already-sending rows cannot be claimed again, and the order-confirmation page only reads the status. Refreshing that page therefore cannot resend mail.

Transport exceptions are caught after commit. Only the internal order ID and exception class are logged, not credentials, SMTP responses, or stack traces. Email failure never deletes, rolls back, or hides the completed order.

Local configuration is copied from config/mail.example.php to the ignored config/mail.php. Set enabled false when SMTP is intentionally unavailable. For Gmail, use smtp.gmail.com with STARTTLS on port 587, enable 2-Step Verification, and use an App Password rather than the normal account password. Provider restrictions may prevent some Google accounts from creating App Passwords.

## Reviews and newsletter

Logged-in customers can submit one review per purchased product. Ratings must be 1–5 and content is length-validated. New reviews are pending; public product pages retrieve approved reviews only and escape all displayed content. Moderation UI is intentionally deferred to Phase 4.

Newsletter subscriptions normalize and validate email, store an active record, and return a friendly idempotent message for an existing subscriber. No email delivery is attempted.

## Security decisions

- prepared PDO statements with emulation disabled;
- allowlisted sort and other dynamic query choices;
- CSRF validation on every state-changing form and JSON endpoint;
- ownership checks embedded in address and order queries;
- escaped output for user, address, search, and review data;
- database uniqueness, foreign keys, and quantity/rating/money constraints;
- server-authoritative prices, totals, coupons, shipping, and stock;
- transaction locks and conditional inventory decrements;
- generic public exceptions and a safe database setup state;
- no credential, stack trace, SQL, filesystem path, or payment-card exposure.
- SMTP credentials remain in ignored configuration and mail failures expose no transport details.

## Validation and QA

tools/phase3-integration.php exercises the real database using isolated records and cleanup. It covers seeds, repositories, invalid slugs, registration and duplicate handling, login and wrong passwords, guest/account merges, ownership, coupons, checkout totals, stock decrement, cart clearing, guest COD, review moderation, newsletter duplicates, bad quantities, out-of-stock rejection, stock-race rollback, successful and failed mail transports, durable orders after mail failure, complete HTML/text content, and duplicate-send prevention.

tools/smtp-capture-server.cjs plus tools/phase3-mail-smtp-qa.php exercise the actual PHPMailer SMTP adapter locally and verify that the transmitted message is multipart HTML and plain text.

tools/phase2-browser-qa.cjs rechecks the approved storefront and motion contract. tools/phase3-browser-qa.cjs covers the full customer browser journey and mobile layouts. Both expect the local PHP server at http://127.0.0.1:8000.

## Deliberate Phase 3 limits

- Demo Card is simulated; no payment gateway or payment credential storage exists.
- Email verification, password reset, automatic failed-mail retry, and shipment integrations are not included.
- SMTP delivery is synchronous after commit and may add provider/network latency to checkout; a future queue worker can build on the persisted delivery record.
- Product/review/order/coupon/settings administration and analytics belong to Phase 4.
- The ten-product catalog uses a clean hybrid filter model: products are fetched in bulk from MySQL and the approved client UI filters the safe view model. A larger production catalog would move pagination/filter queries fully server-side.
