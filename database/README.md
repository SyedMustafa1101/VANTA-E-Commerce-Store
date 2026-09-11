# Database

The vanta.sql file is the importable Phase 3 schema and seed set. Create an
empty database named vanta, select it in phpMyAdmin, and import the file. The
script uses InnoDB, utf8mb4, foreign keys, useful indexes, the ten approved
storefront products, their exact variant stock, the VANTA10 demo coupon, and
order_email_deliveries for duplicate-safe transactional mail status.

The seed statements are repeatable. They update catalog seed records without
dropping customer, cart, order, mail-delivery, review, or newsletter data.
