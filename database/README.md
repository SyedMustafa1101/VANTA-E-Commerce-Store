# Database setup

## Fresh installation

Create an empty `vanta` database using `utf8mb4_unicode_ci`, select it, and import `vanta.sql`. The file contains the complete Phase 4 schema, useful indexes, foreign keys, repeatable storefront catalog/settings seeds, exact initial variant stock, and the `VANTA10` demo coupon. It does not create a staff account; run `tools/create-admin.php` afterward.

## Upgrade from the approved Phase 3 database

1. Make a database backup.
2. Select the existing `vanta` database.
3. Import `migrations/phase4_admin.sql` through phpMyAdmin or the MySQL CLI.
4. Run `tools/create-admin.php` to create the first SUPER_ADMIN.
5. Sign in at `/admin/login.php` and confirm dashboard counts against the storefront database.

The migration is additive and idempotent. It adds admin authentication/audit tables, order status history, inventory adjustment history, management fields/indexes, image metadata, safe settings, and the cancellation restock marker. It neither drops nor reseeds existing users, orders, products, stock, reviews, subscribers, carts, wishlists, coupons, usages, or email deliveries.

For MySQL CLI on a typical XAMPP installation:

~~~powershell
C:\xampp\mysql\bin\mysql.exe -u root -p vanta --execute="source database/migrations/phase4_admin.sql"
~~~

Use the same command with `database\vanta.sql` only for a new empty database. Store credentials in ignored `config/database.php`; never put them in schema or migration files.
