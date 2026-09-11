-- VANTA Phase 3 commerce schema
-- Import into an empty database named vanta. No customer data is dropped.
SET NAMES utf8mb4;
SET time_zone = '+05:00';

CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 first_name VARCHAR(80) NOT NULL,
 last_name VARCHAR(80) NOT NULL,
 email VARCHAR(190) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 status ENUM('active','disabled') NOT NULL DEFAULT 'active',
 last_login_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_users_email (email),
 KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS addresses (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 label VARCHAR(60) NOT NULL DEFAULT 'Home',
 recipient_name VARCHAR(160) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 address_line_1 VARCHAR(190) NOT NULL,
 address_line_2 VARCHAR(190) NULL,
 city VARCHAR(100) NOT NULL,
 province VARCHAR(100) NOT NULL,
 postal_code VARCHAR(30) NULL,
 country VARCHAR(100) NOT NULL DEFAULT 'Pakistan',
 is_default TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 KEY idx_addresses_user_default (user_id,is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 slug VARCHAR(120) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_categories_name (name),
 UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS collections (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 slug VARCHAR(120) NOT NULL,
 eyebrow VARCHAR(160) NOT NULL,
 description TEXT NOT NULL,
 primary_image VARCHAR(255) NOT NULL,
 secondary_image VARCHAR(255) NOT NULL,
 sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_collections_name (name),
 UNIQUE KEY uq_collections_slug (slug),
 KEY idx_collections_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id INT UNSIGNED NOT NULL,
 collection_id INT UNSIGNED NOT NULL,
 slug VARCHAR(190) NOT NULL,
 name VARCHAR(190) NOT NULL,
 description TEXT NOT NULL,
 materials TEXT NOT NULL,
 care TEXT NOT NULL,
 price DECIMAL(12,2) NOT NULL,
 sale_price DECIMAL(12,2) NULL,
 label VARCHAR(40) NULL,
 popularity SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 keywords JSON NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id),
 CONSTRAINT fk_products_collection FOREIGN KEY (collection_id) REFERENCES collections(id),
 UNIQUE KEY uq_products_slug (slug),
 KEY idx_products_active_collection (is_active,collection_id),
 KEY idx_products_category (category_id),
 KEY idx_products_popularity (popularity),
 KEY idx_products_price (price),
 CONSTRAINT chk_products_price CHECK (price >= 0),
 CONSTRAINT chk_products_sale_price CHECK (sale_price IS NULL OR (sale_price >= 0 AND sale_price <= price))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL,
 color_slug VARCHAR(80) NOT NULL DEFAULT '',
 path VARCHAR(255) NOT NULL,
 alt_text VARCHAR(255) NOT NULL DEFAULT '',
 sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
 UNIQUE KEY uq_product_images_slot (product_id,color_slug,sort_order),
 KEY idx_product_images_lookup (product_id,color_slug,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_variants (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL,
 color_slug VARCHAR(80) NOT NULL,
 color_name VARCHAR(100) NOT NULL,
 color_hex CHAR(7) NOT NULL,
 size VARCHAR(20) NOT NULL,
 sku VARCHAR(100) NOT NULL,
 stock_quantity INT NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_product_variants_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
 UNIQUE KEY uq_product_variant_option (product_id,color_slug,size),
 UNIQUE KEY uq_product_variants_sku (sku),
 KEY idx_product_variants_stock (product_id,is_active,stock_quantity),
 CONSTRAINT chk_product_variants_stock CHECK (stock_quantity >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_relations (
 product_id BIGINT UNSIGNED NOT NULL,
 related_product_id BIGINT UNSIGNED NOT NULL,
 sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (product_id,related_product_id),
 CONSTRAINT fk_product_relations_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
 CONSTRAINT fk_product_relations_related FOREIGN KEY (related_product_id) REFERENCES products(id) ON DELETE CASCADE,
 KEY idx_product_relations_sort (product_id,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wishlists (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_wishlists_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 UNIQUE KEY uq_wishlists_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wishlist_items (
 wishlist_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (wishlist_id,product_id),
 CONSTRAINT fk_wishlist_items_wishlist FOREIGN KEY (wishlist_id) REFERENCES wishlists(id) ON DELETE CASCADE,
 CONSTRAINT fk_wishlist_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
 KEY idx_wishlist_items_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 UNIQUE KEY uq_carts_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cart_items (
 cart_id BIGINT UNSIGNED NOT NULL,
 variant_id BIGINT UNSIGNED NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (cart_id,variant_id),
 CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
 CONSTRAINT fk_cart_items_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
 KEY idx_cart_items_variant (variant_id),
 CONSTRAINT chk_cart_items_quantity CHECK (quantity BETWEEN 1 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupons (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(60) NOT NULL,
 type ENUM('percentage','fixed') NOT NULL,
 value DECIMAL(12,2) NOT NULL,
 minimum_order DECIMAL(12,2) NOT NULL DEFAULT 0,
 starts_at DATETIME NULL,
 expires_at DATETIME NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 usage_limit INT UNSIGNED NULL,
 per_customer_limit INT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_coupons_code (code),
 KEY idx_coupons_validation (is_active,starts_at,expires_at),
 CONSTRAINT chk_coupons_value CHECK (value > 0),
 CONSTRAINT chk_coupons_minimum CHECK (minimum_order >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NULL,
 coupon_id BIGINT UNSIGNED NULL,
 order_number VARCHAR(40) NOT NULL,
 customer_first_name VARCHAR(80) NOT NULL,
 customer_last_name VARCHAR(80) NOT NULL,
 customer_email VARCHAR(190) NOT NULL,
 customer_phone VARCHAR(30) NOT NULL,
 shipping_label VARCHAR(60) NOT NULL DEFAULT 'Delivery',
 shipping_recipient VARCHAR(160) NOT NULL,
 shipping_address_line_1 VARCHAR(190) NOT NULL,
 shipping_address_line_2 VARCHAR(190) NULL,
 shipping_city VARCHAR(100) NOT NULL,
 shipping_province VARCHAR(100) NOT NULL,
 shipping_postal_code VARCHAR(30) NULL,
 shipping_country VARCHAR(100) NOT NULL,
 shipping_method VARCHAR(80) NOT NULL,
 payment_method ENUM('cash_on_delivery','demo_card') NOT NULL,
 payment_status ENUM('pending','paid','failed','cod_pending') NOT NULL,
 order_status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
 subtotal DECIMAL(12,2) NOT NULL,
 discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
 shipping_total DECIMAL(12,2) NOT NULL DEFAULT 0,
 total DECIMAL(12,2) NOT NULL,
 coupon_code VARCHAR(60) NULL,
 placed_at DATETIME NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_orders_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL,
 UNIQUE KEY uq_orders_order_number (order_number),
 KEY idx_orders_user_date (user_id,placed_at),
 KEY idx_orders_email_date (customer_email,placed_at),
 KEY idx_orders_status (order_status,payment_status),
 CONSTRAINT chk_orders_totals CHECK (
   subtotal >= 0 AND discount_total >= 0 AND discount_total <= subtotal
   AND shipping_total >= 0 AND total >= 0
 )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NULL,
 variant_id BIGINT UNSIGNED NULL,
 product_name VARCHAR(190) NOT NULL,
 variant_description VARCHAR(190) NOT NULL,
 sku VARCHAR(100) NOT NULL,
 image_path VARCHAR(255) NULL,
 unit_price DECIMAL(12,2) NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 line_total DECIMAL(12,2) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
 CONSTRAINT fk_order_items_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
 KEY idx_order_items_order (order_id),
 KEY idx_order_items_product (product_id),
 CONSTRAINT chk_order_items_values CHECK (unit_price >= 0 AND quantity > 0 AND line_total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_email_deliveries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 email_type VARCHAR(50) NOT NULL DEFAULT 'order_confirmation',
 recipient_email VARCHAR(190) NOT NULL,
 status ENUM('pending','sending','sent','failed') NOT NULL DEFAULT 'pending',
 attempt_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 last_attempted_at DATETIME NULL,
 sent_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_order_email_deliveries_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
 UNIQUE KEY uq_order_email_delivery (order_id,email_type),
 KEY idx_order_email_delivery_status (status,last_attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupon_usage (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 coupon_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 order_id BIGINT UNSIGNED NOT NULL,
 customer_email VARCHAR(190) NOT NULL,
 used_at DATETIME NOT NULL,
 CONSTRAINT fk_coupon_usage_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE RESTRICT,
 CONSTRAINT fk_coupon_usage_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_coupon_usage_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
 UNIQUE KEY uq_coupon_usage_order (order_id),
 KEY idx_coupon_usage_customer (coupon_id,user_id),
 KEY idx_coupon_usage_email (coupon_id,customer_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 rating TINYINT UNSIGNED NOT NULL,
 content TEXT NOT NULL,
 status ENUM('pending','approved','rejected','hidden') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
 UNIQUE KEY uq_reviews_user_product (user_id,product_id),
 KEY idx_reviews_public (product_id,status,created_at),
 CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(190) NOT NULL,
 status ENUM('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_newsletter_email (email),
 KEY idx_newsletter_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(190) NOT NULL,
 ip_hash CHAR(64) NOT NULL,
 attempted_at DATETIME NOT NULL,
 was_successful TINYINT(1) NOT NULL DEFAULT 0,
 KEY idx_login_attempts_throttle (email,ip_hash,attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
 setting_key VARCHAR(100) PRIMARY KEY,
 setting_value VARCHAR(255) NOT NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (id,name,slug) VALUES
 (1,'Tees','tees'),(2,'Hoodies','hoodies'),(3,'Bottoms','bottoms'),
 (4,'Outerwear','outerwear'),(5,'Accessories','accessories')
ON DUPLICATE KEY UPDATE name=VALUES(name),slug=VALUES(slug);

INSERT INTO collections (id,name,slug,eyebrow,description,primary_image,secondary_image,sort_order) VALUES
 (1,'NEW DROP','new-drop','DROP 001 / JUST LANDED','The first VANTA release: dense jersey, controlled volume, and utility built for the hours after dark.','images/catalog/vanta-essential-look.jpg','images/catalog/vanta-after-dark-look.jpg',1),
 (2,'ESSENTIALS','essentials','CORE / PERMANENT','The permanent uniform. Heavyweight foundations designed to repeat, layer, and live in.','images/catalog/vanta-accessories-still-life.jpg','images/catalog/vanta-essential-look.jpg',2),
 (3,'AFTER DARK','after-dark','CAMPAIGN / 00:01','Technical layers and deep silhouettes shaped by the city when the daylight disappears.','images/catalog/vanta-after-dark-look.jpg','images/vanta-foundation-campaign.jpg',3),
 (4,'LIMITED','limited','NO RESTOCK / NUMBERED','Small-run pieces that leave when they are gone. No repeats. No second release.','images/vanta-foundation-campaign.jpg','images/catalog/vanta-after-dark-look.jpg',4)
ON DUPLICATE KEY UPDATE name=VALUES(name),slug=VALUES(slug),eyebrow=VALUES(eyebrow),description=VALUES(description),primary_image=VALUES(primary_image),secondary_image=VALUES(secondary_image),sort_order=VALUES(sort_order);

INSERT INTO products (id,category_id,collection_id,slug,name,description,materials,care,price,sale_price,label,popularity,keywords,is_active) VALUES
 (101,1,2,'vanta-oversized-essential-tee','VANTA Oversized Essential Tee','A deliberately oversized heavyweight tee with dropped shoulders and a clean, structured fall.','320 GSM combed cotton jersey. Pre-shrunk and garment washed.','Cold wash inside out. Do not bleach. Hang dry. Cool iron on reverse.',6800,NULL,'NEW',98,JSON_ARRAY('heavyweight','oversized','cotton','core','black tee'),1),
 (102,2,3,'after-dark-hoodie','After Dark Hoodie','Dense brushed fleece, a sculpted hood, and a cropped boxy body made for layered night uniforms.','480 GSM brushed cotton fleece with tonal rib trims.','Cold wash on gentle cycle. Reshape while damp. Dry flat.',14500,NULL,'LIMITED',96,JSON_ARRAY('hoodie','fleece','layer','night','black'),1),
 (103,3,1,'shadow-cargo-pant','Shadow Cargo Pant','Wide utility trousers with articulated knees, low-profile cargo pockets, and an adjustable hem.','Cotton-nylon ripstop with matte metal hardware.','Cold wash. Close hardware before washing. Line dry.',12800,10900,'NEW',91,JSON_ARRAY('cargo','trouser','utility','wide','technical'),1),
 (104,4,3,'nocturne-bomber','Nocturne Bomber','A cropped technical bomber with sculpted volume, tonal pocketing, and a muted gunmetal zip.','Water-resistant matte nylon shell with recycled fill.','Professional clean recommended. Do not tumble dry.',24500,NULL,'LIMITED',94,JSON_ARRAY('bomber','jacket','outerwear','technical','cropped'),1),
 (105,1,1,'void-heavyweight-tee','Void Heavyweight Tee','Compact cotton jersey with a high rib neck and a long, angular sleeve line.','340 GSM compact cotton jersey.','Cold wash inside out. Hang dry.',7200,NULL,'NEW',87,JSON_ARRAY('tee','heavyweight','cotton','void'),1),
 (106,2,2,'vanta-core-hoodie','VANTA Core Hoodie','An everyday heavyweight layer cut with dropped shoulders and a clean kangaroo pocket.','450 GSM brushed cotton fleece.','Cold wash. Dry flat. Do not iron trims.',13200,11800,NULL,90,JSON_ARRAY('hoodie','core','essential','fleece'),1),
 (107,3,4,'midnight-utility-cargo','Midnight Utility Cargo','A limited wide-leg cargo with modular pockets and concealed ankle adjusters.','Technical cotton-nylon canvas.','Spot clean or cold hand wash. Line dry.',15600,NULL,'LIMITED',84,JSON_ARRAY('cargo','limited','utility','midnight'),1),
 (108,4,4,'obsidian-jacket','Obsidian Jacket','A rigid cropped shell with a high stand collar and concealed fastening.','Bonded cotton shell with a smooth cupro lining.','Professional dry clean only.',28900,NULL,'SOLD OUT',93,JSON_ARRAY('jacket','shell','obsidian','outerwear'),1),
 (109,5,3,'after-hours-cap','After Hours Cap','A structured six-panel cap in dense brushed cotton with tonal hardware.','Brushed cotton twill with a metal adjuster.','Spot clean only.',4200,NULL,'NEW',82,JSON_ARRAY('cap','hat','accessory','after hours'),1),
 (110,5,2,'vanta-crossbody-bag','VANTA Crossbody Bag','A compact crossbody with modular webbing, tonal zips, and a fully adjustable strap.','Recycled matte nylon with powder-coated hardware.','Wipe clean with a damp cloth.',8900,NULL,NULL,86,JSON_ARRAY('bag','crossbody','utility','accessory'),1)
ON DUPLICATE KEY UPDATE category_id=VALUES(category_id),collection_id=VALUES(collection_id),slug=VALUES(slug),name=VALUES(name),description=VALUES(description),materials=VALUES(materials),care=VALUES(care),price=VALUES(price),sale_price=VALUES(sale_price),label=VALUES(label),popularity=VALUES(popularity),keywords=VALUES(keywords),is_active=VALUES(is_active);

INSERT INTO product_images (product_id,color_slug,path,alt_text,sort_order) VALUES
 (101,'','images/catalog/vanta-essential-look.jpg','VANTA Oversized Essential Tee',1),(101,'','images/catalog/vanta-after-dark-look.jpg','',2),(101,'','images/vanta-foundation-campaign.jpg','',3),(101,'black','images/catalog/vanta-essential-look.jpg','VANTA Oversized Essential Tee in Black',1),(101,'black','images/catalog/vanta-after-dark-look.jpg','',2),(101,'bone','images/vanta-foundation-campaign.jpg','VANTA Oversized Essential Tee in Bone',1),(101,'bone','images/catalog/vanta-essential-look.jpg','',2),
 (102,'','images/catalog/vanta-after-dark-look.jpg','After Dark Hoodie',1),(102,'','images/catalog/vanta-essential-look.jpg','',2),(102,'','images/vanta-foundation-campaign.jpg','',3),(102,'black','images/catalog/vanta-after-dark-look.jpg','After Dark Hoodie in Black',1),(102,'black','images/catalog/vanta-essential-look.jpg','',2),(102,'charcoal','images/catalog/vanta-essential-look.jpg','After Dark Hoodie in Charcoal',1),(102,'charcoal','images/catalog/vanta-after-dark-look.jpg','',2),
 (103,'','images/catalog/vanta-essential-look.jpg','Shadow Cargo Pant',1),(103,'','images/catalog/vanta-after-dark-look.jpg','',2),(103,'','images/vanta-foundation-campaign.jpg','',3),(103,'black','images/catalog/vanta-essential-look.jpg','Shadow Cargo Pant in Black',1),(103,'black','images/catalog/vanta-after-dark-look.jpg','',2),(103,'graphite','images/catalog/vanta-after-dark-look.jpg','Shadow Cargo Pant in Graphite',1),(103,'graphite','images/catalog/vanta-essential-look.jpg','',2),
 (104,'','images/catalog/vanta-after-dark-look.jpg','Nocturne Bomber',1),(104,'','images/catalog/vanta-essential-look.jpg','',2),(104,'','images/vanta-foundation-campaign.jpg','',3),(104,'black','images/catalog/vanta-after-dark-look.jpg','Nocturne Bomber in Black',1),(104,'black','images/catalog/vanta-essential-look.jpg','',2),
 (105,'','images/catalog/vanta-essential-look.jpg','Void Heavyweight Tee',1),(105,'','images/vanta-foundation-campaign.jpg','',2),(105,'','images/catalog/vanta-after-dark-look.jpg','',3),(105,'black','images/catalog/vanta-essential-look.jpg','Void Heavyweight Tee in Black',1),(105,'black','images/vanta-foundation-campaign.jpg','',2),(105,'white','images/vanta-foundation-campaign.jpg','Void Heavyweight Tee in White',1),(105,'white','images/catalog/vanta-essential-look.jpg','',2),
 (106,'','images/catalog/vanta-after-dark-look.jpg','VANTA Core Hoodie',1),(106,'','images/catalog/vanta-essential-look.jpg','',2),(106,'','images/vanta-foundation-campaign.jpg','',3),(106,'black','images/catalog/vanta-after-dark-look.jpg','VANTA Core Hoodie in Black',1),(106,'black','images/catalog/vanta-essential-look.jpg','',2),(106,'charcoal','images/catalog/vanta-essential-look.jpg','VANTA Core Hoodie in Charcoal',1),(106,'charcoal','images/catalog/vanta-after-dark-look.jpg','',2),
 (107,'','images/catalog/vanta-essential-look.jpg','Midnight Utility Cargo',1),(107,'','images/catalog/vanta-after-dark-look.jpg','',2),(107,'','images/vanta-foundation-campaign.jpg','',3),(107,'black','images/catalog/vanta-essential-look.jpg','Midnight Utility Cargo in Black',1),(107,'black','images/catalog/vanta-after-dark-look.jpg','',2),
 (108,'','images/catalog/vanta-after-dark-look.jpg','Obsidian Jacket',1),(108,'','images/vanta-foundation-campaign.jpg','',2),(108,'','images/catalog/vanta-essential-look.jpg','',3),(108,'black','images/catalog/vanta-after-dark-look.jpg','Obsidian Jacket in Black',1),(108,'black','images/vanta-foundation-campaign.jpg','',2),
 (109,'','images/catalog/vanta-accessories-still-life.jpg','After Hours Cap',1),(109,'','images/catalog/vanta-after-dark-look.jpg','',2),(109,'','images/vanta-foundation-campaign.jpg','',3),(109,'black','images/catalog/vanta-accessories-still-life.jpg','After Hours Cap in Black',1),(109,'black','images/catalog/vanta-after-dark-look.jpg','',2),
 (110,'','images/catalog/vanta-accessories-still-life.jpg','VANTA Crossbody Bag',1),(110,'','images/catalog/vanta-essential-look.jpg','',2),(110,'','images/vanta-foundation-campaign.jpg','',3),(110,'black','images/catalog/vanta-accessories-still-life.jpg','VANTA Crossbody Bag in Black',1),(110,'black','images/catalog/vanta-essential-look.jpg','',2)
ON DUPLICATE KEY UPDATE path=VALUES(path),alt_text=VALUES(alt_text);

INSERT INTO product_variants (id,product_id,color_slug,color_name,color_hex,size,sku,stock_quantity,is_active) VALUES
 (1011,101,'black','Black','#0A0A0A','S','VNT-ESS-BLK-S',8,1),(1012,101,'black','Black','#0A0A0A','M','VNT-ESS-BLK-M',12,1),(1013,101,'black','Black','#0A0A0A','L','VNT-ESS-BLK-L',4,1),(1014,101,'black','Black','#0A0A0A','XL','VNT-ESS-BLK-XL',0,1),(1015,101,'bone','Bone','#E7E2D8','S','VNT-ESS-BON-S',3,1),(1016,101,'bone','Bone','#E7E2D8','M','VNT-ESS-BON-M',7,1),(1017,101,'bone','Bone','#E7E2D8','L','VNT-ESS-BON-L',9,1),(1018,101,'bone','Bone','#E7E2D8','XL','VNT-ESS-BON-XL',2,1),
 (1021,102,'black','Black','#0A0A0A','S','VNT-ADH-BLK-S',4,1),(1022,102,'black','Black','#0A0A0A','M','VNT-ADH-BLK-M',7,1),(1023,102,'black','Black','#0A0A0A','L','VNT-ADH-BLK-L',3,1),(1024,102,'black','Black','#0A0A0A','XL','VNT-ADH-BLK-XL',1,1),(1025,102,'charcoal','Charcoal','#393939','S','VNT-ADH-CHR-S',0,1),(1026,102,'charcoal','Charcoal','#393939','M','VNT-ADH-CHR-M',3,1),(1027,102,'charcoal','Charcoal','#393939','L','VNT-ADH-CHR-L',5,1),(1028,102,'charcoal','Charcoal','#393939','XL','VNT-ADH-CHR-XL',2,1),
 (1031,103,'black','Black','#0A0A0A','S','VNT-SCP-BLK-S',5,1),(1032,103,'black','Black','#0A0A0A','M','VNT-SCP-BLK-M',8,1),(1033,103,'black','Black','#0A0A0A','L','VNT-SCP-BLK-L',6,1),(1034,103,'black','Black','#0A0A0A','XL','VNT-SCP-BLK-XL',2,1),(1035,103,'graphite','Graphite','#555555','S','VNT-SCP-GRA-S',2,1),(1036,103,'graphite','Graphite','#555555','M','VNT-SCP-GRA-M',0,1),(1037,103,'graphite','Graphite','#555555','L','VNT-SCP-GRA-L',4,1),(1038,103,'graphite','Graphite','#555555','XL','VNT-SCP-GRA-XL',1,1),
 (1041,104,'black','Black','#080808','S','VNT-NOC-BLK-S',2,1),(1042,104,'black','Black','#080808','M','VNT-NOC-BLK-M',4,1),(1043,104,'black','Black','#080808','L','VNT-NOC-BLK-L',2,1),(1044,104,'black','Black','#080808','XL','VNT-NOC-BLK-XL',0,1),
 (1051,105,'black','Black','#0B0B0B','S','VNT-VOI-BLK-S',7,1),(1052,105,'black','Black','#0B0B0B','M','VNT-VOI-BLK-M',9,1),(1053,105,'black','Black','#0B0B0B','L','VNT-VOI-BLK-L',7,1),(1054,105,'black','Black','#0B0B0B','XL','VNT-VOI-BLK-XL',3,1),(1055,105,'white','White','#F1EFE8','S','VNT-VOI-WHT-S',2,1),(1056,105,'white','White','#F1EFE8','M','VNT-VOI-WHT-M',6,1),(1057,105,'white','White','#F1EFE8','L','VNT-VOI-WHT-L',1,1),(1058,105,'white','White','#F1EFE8','XL','VNT-VOI-WHT-XL',0,1),
 (1061,106,'black','Black','#0A0A0A','S','VNT-COR-BLK-S',9,1),(1062,106,'black','Black','#0A0A0A','M','VNT-COR-BLK-M',12,1),(1063,106,'black','Black','#0A0A0A','L','VNT-COR-BLK-L',8,1),(1064,106,'black','Black','#0A0A0A','XL','VNT-COR-BLK-XL',4,1),(1065,106,'charcoal','Charcoal','#373737','S','VNT-COR-CHR-S',4,1),(1066,106,'charcoal','Charcoal','#373737','M','VNT-COR-CHR-M',5,1),(1067,106,'charcoal','Charcoal','#373737','L','VNT-COR-CHR-L',2,1),(1068,106,'charcoal','Charcoal','#373737','XL','VNT-COR-CHR-XL',1,1),
 (1071,107,'black','Black','#060606','S','VNT-MID-BLK-S',0,1),(1072,107,'black','Black','#060606','M','VNT-MID-BLK-M',2,1),(1073,107,'black','Black','#060606','L','VNT-MID-BLK-L',1,1),(1074,107,'black','Black','#060606','XL','VNT-MID-BLK-XL',0,1),
 (1081,108,'black','Black','#050505','S','VNT-OBS-BLK-S',0,1),(1082,108,'black','Black','#050505','M','VNT-OBS-BLK-M',0,1),(1083,108,'black','Black','#050505','L','VNT-OBS-BLK-L',0,1),(1084,108,'black','Black','#050505','XL','VNT-OBS-BLK-XL',0,1),
 (1091,109,'black','Black','#090909','OS','VNT-AHC-BLK-OS',14,1),(1101,110,'black','Black','#080808','OS','VNT-XBD-BLK-OS',9,1)
ON DUPLICATE KEY UPDATE product_id=VALUES(product_id),color_slug=VALUES(color_slug),color_name=VALUES(color_name),color_hex=VALUES(color_hex),size=VALUES(size),sku=VALUES(sku),is_active=VALUES(is_active);

INSERT INTO product_relations (product_id,related_product_id,sort_order) VALUES
 (101,106,1),(101,103,2),(101,110,3),(101,102,4),(102,104,1),(102,101,2),(102,106,3),(102,109,4),
 (103,105,1),(103,101,2),(103,107,3),(103,104,4),(104,102,1),(104,107,2),(104,108,3),(104,103,4),
 (105,103,1),(105,106,2),(105,101,3),(105,110,4),(106,101,1),(106,105,2),(106,103,3),(106,110,4),
 (107,104,1),(107,103,2),(107,102,3),(107,108,4),(108,104,1),(108,107,2),(108,102,3),(108,103,4),
 (109,110,1),(109,102,2),(109,101,3),(109,106,4),(110,109,1),(110,101,2),(110,106,3),(110,103,4)
ON DUPLICATE KEY UPDATE sort_order=VALUES(sort_order);

INSERT INTO coupons (code,type,value,minimum_order,starts_at,expires_at,is_active,usage_limit,per_customer_limit)
VALUES ('VANTA10','percentage',10,0,NULL,NULL,1,1000,5)
ON DUPLICATE KEY UPDATE type=VALUES(type),value=VALUES(value),minimum_order=VALUES(minimum_order),is_active=VALUES(is_active),usage_limit=VALUES(usage_limit),per_customer_limit=VALUES(per_customer_limit);

INSERT INTO settings (setting_key,setting_value) VALUES
 ('shipping_standard_pkr','300'),('free_shipping_threshold_pkr','15000'),
 ('store_country','Pakistan'),('review_requires_purchase','1')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
