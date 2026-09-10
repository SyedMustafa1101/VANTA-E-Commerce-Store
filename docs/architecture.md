# VANTA architecture

The final project uses a deliberately shallow PHP architecture. It is framework-free, XAMPP-friendly, and organized by responsibility so each important system can be explained in an interview.

```text
VANTA/
├── index.php                   # Editorial homepage
├── shop.php                    # Catalog and filter surface
├── collection.php              # Reusable collection landing
├── product.php                 # Product detail and variant selection
├── cart.php                    # Full cart fallback route
├── checkout.php                # Multi-step checkout
├── order-confirmation.php      # Completed order summary
├── login.php / register.php    # Customer authentication
├── design-system.php           # Development reference
├── account/                    # Profile, addresses, orders, wishlist
├── admin/                      # Separately protected admin routes
├── api/                        # Small JSON endpoints by domain
├── includes/                   # Bootstrap, helpers, layout, reusable views
├── config/                     # App config + ignored local DB config
├── assets/
│   ├── css/                    # Tokens, authored CSS, Tailwind input/output
│   ├── js/                     # Core interactions + page/domain modules
│   ├── images/                 # Replaceable editorial/catalog assets
│   └── fonts/                  # Optional self-hosted fonts later
├── uploads/                    # Validated generated upload names only
├── database/vanta.sql          # Phase 3 schema and realistic seeds
└── docs/                       # Architecture and implementation notes
```

## Request lifecycle

Every PHP page loads `includes/bootstrap.php`. Bootstrap owns configuration, timezone, hardened session defaults, and shared helpers. Pages set metadata and layout state, include the shared header, render their domain content, and include the shared footer.

Backend actions will be split by domain rather than placed in one large endpoint. Server-side services introduced in Phase 3 will own inventory checks, pricing, coupons, order totals, and transaction boundaries. JavaScript can request or present results, but it will never be trusted for prices, stock, discounts, or authorization.

## Database direction

Phase 3 will add a normalized MySQL schema with products separate from their purchasable variants. A unique product/color/size combination and unique SKU will be enforced at database level. Order items will store immutable purchase snapshots alongside variant references so historical totals remain correct after product edits.

Expected domains:

- identity: `users`, `admins`, `addresses`;
- catalog: `categories`, `collections`, `products`, `product_images`, `product_variants`;
- engagement: `wishlists`, `wishlist_items`, `reviews`;
- commerce: cart/session strategy, `orders`, `order_items`, `coupons`, `coupon_usage`;
- operations: `settings`, indexed inventory/status fields, audit timestamps.

## Security boundaries

- prepared PDO statements for every dynamic query;
- `password_hash()` / `password_verify()` and session ID regeneration;
- role checks on every admin request, independent of navigation visibility;
- CSRF validation for state-changing browser requests;
- escaped output by default through `e()`;
- server-owned prices, coupon math, shipping, totals, and variant stock checks;
- allow-listed upload types, size limits, content inspection, and generated filenames;
- transaction-protected order creation and inventory deduction.

## Frontend boundaries

`assets/js/app.js` owns global navigation, notices, and transition primitives. `assets/js/motion.js` owns the optional animation layer and checks for missing libraries or reduced-motion preferences. `assets/js/storefront.js` owns the Phase 2 catalog interactions, search, guest cart/wishlist state, filters, variant selection, and newsletter feedback. Each feature initializes only when its matching data hooks exist.

The visual system is token-driven in `assets/css/app.css`. Tailwind is used for compositional utilities while authored CSS owns brand-specific components and animation states. Storefront pages can be expressive; account, checkout, and admin surfaces will reuse tokens with calmer density and motion.
