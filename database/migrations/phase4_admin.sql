-- VANTA Phase 4 in-place upgrade for an existing Phase 3 database.
-- Safe to run more than once. Customer and order history is preserved.
SET NAMES utf8mb4;
SET time_zone = '+05:00';

DELIMITER $$
DROP PROCEDURE IF EXISTS vanta_phase4_admin_upgrade$$
CREATE PROCEDURE vanta_phase4_admin_upgrade()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'description') THEN
    ALTER TABLE categories ADD COLUMN description TEXT NOT NULL AFTER slug;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'is_active') THEN
    ALTER TABLE categories ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER description;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'sort_order') THEN
    ALTER TABLE categories ADD COLUMN sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER is_active;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'updated_at') THEN
    ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'collections' AND COLUMN_NAME = 'is_active') THEN
    ALTER TABLE collections ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER secondary_image;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'collections' AND COLUMN_NAME = 'is_featured') THEN
    ALTER TABLE collections ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'short_description') THEN
    ALTER TABLE products ADD COLUMN short_description VARCHAR(500) NOT NULL DEFAULT '' AFTER name;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'status') THEN
    ALTER TABLE products ADD COLUMN status ENUM('draft','active','archived') NOT NULL DEFAULT 'active' AFTER keywords;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'is_featured') THEN
    ALTER TABLE products ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'meta_title') THEN
    ALTER TABLE products ADD COLUMN meta_title VARCHAR(190) NULL AFTER is_featured;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'meta_description') THEN
    ALTER TABLE products ADD COLUMN meta_description VARCHAR(320) NULL AFTER meta_title;
  END IF;
  UPDATE products SET status = 'archived' WHERE is_active = 0 AND status = 'active';

  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_images' AND COLUMN_NAME = 'role') THEN
    ALTER TABLE product_images ADD COLUMN role ENUM('gallery','campaign','detail') NOT NULL DEFAULT 'gallery' AFTER alt_text;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_images' AND COLUMN_NAME = 'is_primary') THEN
    ALTER TABLE product_images ADD COLUMN is_primary TINYINT(1) NOT NULL DEFAULT 0 AFTER role;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_images' AND COLUMN_NAME = 'is_uploaded') THEN
    ALTER TABLE product_images ADD COLUMN is_uploaded TINYINT(1) NOT NULL DEFAULT 0 AFTER is_primary;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_images' AND COLUMN_NAME = 'mime_type') THEN
    ALTER TABLE product_images ADD COLUMN mime_type VARCHAR(100) NULL AFTER is_uploaded;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_images' AND COLUMN_NAME = 'file_size') THEN
    ALTER TABLE product_images ADD COLUMN file_size INT UNSIGNED NULL AFTER mime_type;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_images' AND COLUMN_NAME = 'width') THEN
    ALTER TABLE product_images ADD COLUMN width INT UNSIGNED NULL AFTER file_size;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_images' AND COLUMN_NAME = 'height') THEN
    ALTER TABLE product_images ADD COLUMN height INT UNSIGNED NULL AFTER width;
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_images' AND INDEX_NAME = 'uq_product_images_slot' AND NON_UNIQUE = 0) THEN
    ALTER TABLE product_images DROP INDEX uq_product_images_slot;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_images' AND INDEX_NAME = 'idx_product_images_sort') THEN
    ALTER TABLE product_images ADD KEY idx_product_images_sort (product_id,color_slug,sort_order);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'image_id') THEN
    ALTER TABLE product_variants ADD COLUMN image_id BIGINT UNSIGNED NULL AFTER sku,
      ADD CONSTRAINT fk_product_variants_image FOREIGN KEY (image_id) REFERENCES product_images(id) ON DELETE SET NULL;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'inventory_restored_at') THEN
    ALTER TABLE orders ADD COLUMN inventory_restored_at DATETIME NULL AFTER coupon_code;
  END IF;
END$$
CALL vanta_phase4_admin_upgrade()$$
DROP PROCEDURE vanta_phase4_admin_upgrade$$
DELIMITER ;

CREATE TABLE IF NOT EXISTS admins (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 email VARCHAR(190) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('SUPER_ADMIN','ADMIN') NOT NULL DEFAULT 'ADMIN',
 active TINYINT(1) NOT NULL DEFAULT 1,
 last_login_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_admins_email (email),
 KEY idx_admins_role_active (role,active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(190) NOT NULL,
 ip_hash CHAR(64) NOT NULL,
 attempted_at DATETIME NOT NULL,
 was_successful TINYINT(1) NOT NULL DEFAULT 0,
 KEY idx_admin_login_throttle (email,ip_hash,attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_adjustments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 variant_id BIGINT UNSIGNED NOT NULL,
 admin_id BIGINT UNSIGNED NOT NULL,
 change_amount INT NOT NULL,
 reason ENUM('restock','correction','return','damage','manual_adjustment','order_cancellation') NOT NULL,
 note VARCHAR(255) NULL,
 previous_stock INT UNSIGNED NOT NULL,
 new_stock INT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_inventory_adjustments_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT,
 CONSTRAINT fk_inventory_adjustments_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE RESTRICT,
 KEY idx_inventory_adjustments_variant_date (variant_id,created_at),
 KEY idx_inventory_adjustments_admin_date (admin_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_status_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 admin_id BIGINT UNSIGNED NULL,
 from_status VARCHAR(40) NULL,
 to_status VARCHAR(40) NOT NULL,
 payment_from_status VARCHAR(40) NULL,
 payment_to_status VARCHAR(40) NULL,
 note VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_order_status_history_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_order_status_history_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
 KEY idx_order_status_history_order_date (order_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 admin_id BIGINT UNSIGNED NULL,
 action VARCHAR(100) NOT NULL,
 entity_type VARCHAR(80) NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 summary VARCHAR(500) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_admin_audit_logs_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
 KEY idx_admin_audit_entity (entity_type,entity_id,created_at),
 KEY idx_admin_audit_admin_date (admin_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key,setting_value) VALUES
 ('store_name','VANTA'),
 ('contact_email','support@vanta.local'),
 ('currency','PKR'),
 ('low_stock_threshold','5'),
 ('brand_tagline','Built for after dark.'),
 ('support_email','support@vanta.local'),
 ('default_country','Pakistan')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

UPDATE product_images pi
JOIN (
 SELECT product_id, MIN(sort_order) AS first_sort
 FROM product_images
 WHERE color_slug = ''
 GROUP BY product_id
) first_image ON first_image.product_id = pi.product_id AND first_image.first_sort = pi.sort_order
SET pi.is_primary = 1
WHERE pi.color_slug = '' AND pi.is_primary = 0;
