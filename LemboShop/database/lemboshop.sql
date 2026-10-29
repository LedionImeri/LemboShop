-- =====================================================================
-- LemboShop — database schema and demo data
-- Target: MariaDB 10.4+ / MySQL 8.0+   Engine: InnoDB   Charset: utf8mb4
--
-- Import: phpMyAdmin → Import → choose this file → Go
--   (or)  mysql -u root < database/lemboshop.sql
--
-- No user accounts are created here. Create the first administrator with:
--   php bin/create-admin.php
-- All timestamps are stored in UTC.
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS lemboshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lemboshop;

DROP TABLE IF EXISTS
  rate_limits, audit_logs, newsletter_subscribers, contact_messages, settings, banners,
  payments, coupon_redemptions, order_status_history, order_items, orders, coupons, shipping_methods,
  notifications, cart_items, carts, recently_viewed, wishlists, reviews,
  product_specs, product_images, products, brands, categories, sellers, addresses, users;

-- ---------------------------------------------------------------------
-- Identity
-- ---------------------------------------------------------------------

CREATE TABLE users (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name           VARCHAR(120) NOT NULL,
  email          VARCHAR(190) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  phone          VARCHAR(30)  NULL,
  role           ENUM('customer','staff','admin') NOT NULL DEFAULT 'customer',
  status         ENUM('active','suspended') NOT NULL DEFAULT 'active',
  newsletter     TINYINT(1) NOT NULL DEFAULT 0,
  last_login_at  DATETIME NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE addresses (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED NOT NULL,
  label         VARCHAR(40)  NOT NULL DEFAULT 'Home',
  full_name     VARCHAR(120) NOT NULL,
  phone         VARCHAR(30)  NOT NULL,
  line1         VARCHAR(190) NOT NULL,
  line2         VARCHAR(190) NULL,
  city          VARCHAR(100) NOT NULL,
  postal_code   VARCHAR(20)  NOT NULL,
  country_code  CHAR(2)      NOT NULL,
  is_default    TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_addresses_user (user_id),
  CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Multi-vendor (optional module). Store-owned products have seller_id = NULL.
CREATE TABLE sellers (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id          INT UNSIGNED NOT NULL,
  store_name       VARCHAR(120) NOT NULL,
  slug             VARCHAR(140) NOT NULL,
  description      TEXT NULL,
  logo             VARCHAR(255) NULL,
  status           ENUM('pending','approved','suspended','rejected') NOT NULL DEFAULT 'pending',
  commission_rate  DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  approved_by      INT UNSIGNED NULL,
  approved_at      DATETIME NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sellers_user (user_id),
  UNIQUE KEY uq_sellers_slug (slug),
  KEY idx_sellers_status (status),
  CONSTRAINT fk_sellers_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_sellers_approver FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_sellers_commission CHECK (commission_rate BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Catalog
-- ---------------------------------------------------------------------

CREATE TABLE categories (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  parent_id    INT UNSIGNED NULL,
  name         VARCHAR(100) NOT NULL,
  slug         VARCHAR(120) NOT NULL,
  description  VARCHAR(255) NULL,
  icon         VARCHAR(40)  NULL,
  image        VARCHAR(255) NULL,
  sort_order   SMALLINT NOT NULL DEFAULT 0,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug),
  KEY idx_categories_parent (parent_id, sort_order),
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE brands (
  id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name  VARCHAR(100) NOT NULL,
  slug  VARCHAR(120) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_brands_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  seller_id            INT UNSIGNED NULL,
  category_id          INT UNSIGNED NOT NULL,
  brand_id             INT UNSIGNED NULL,
  name                 VARCHAR(190) NOT NULL,
  slug                 VARCHAR(210) NOT NULL,
  sku                  VARCHAR(64)  NOT NULL,
  short_description    VARCHAR(300) NOT NULL DEFAULT '',
  description          TEXT NOT NULL,
  price                DECIMAL(10,2) NOT NULL,
  sale_price           DECIMAL(10,2) NULL,
  stock                INT UNSIGNED NOT NULL DEFAULT 0,
  low_stock_threshold  SMALLINT UNSIGNED NOT NULL DEFAULT 5,
  status               ENUM('draft','active','archived') NOT NULL DEFAULT 'draft',
  is_featured          TINYINT(1) NOT NULL DEFAULT 0,
  rating_avg           DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  rating_count         INT UNSIGNED NOT NULL DEFAULT 0,
  sales_count          INT UNSIGNED NOT NULL DEFAULT 0,
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_slug (slug),
  UNIQUE KEY uq_products_sku (sku),
  KEY idx_products_status_category (status, category_id),
  KEY idx_products_price (price),
  KEY idx_products_created (created_at),
  KEY idx_products_sales (sales_count),
  KEY idx_products_rating (rating_avg),
  KEY idx_products_seller (seller_id),
  KEY idx_products_brand (brand_id),
  FULLTEXT KEY ft_products_search (name, short_description, description),
  CONSTRAINT fk_products_seller FOREIGN KEY (seller_id) REFERENCES sellers (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_products_price CHECK (price >= 0),
  CONSTRAINT chk_products_sale CHECK (sale_price IS NULL OR (sale_price >= 0 AND sale_price < price)),
  CONSTRAINT chk_products_rating CHECK (rating_avg BETWEEN 0 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_images (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id  INT UNSIGNED NOT NULL,
  path        VARCHAR(255) NOT NULL,
  alt_text    VARCHAR(190) NOT NULL DEFAULT '',
  sort_order  SMALLINT NOT NULL DEFAULT 0,
  is_primary  TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_product_images_product (product_id, is_primary, sort_order),
  CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_specs (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id  INT UNSIGNED NOT NULL,
  spec_name   VARCHAR(80)  NOT NULL,
  spec_value  VARCHAR(255) NOT NULL,
  sort_order  SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_product_specs_product (product_id, sort_order),
  CONSTRAINT fk_product_specs_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id         INT UNSIGNED NOT NULL,
  user_id            INT UNSIGNED NOT NULL,
  rating             TINYINT UNSIGNED NOT NULL,
  title              VARCHAR(120) NOT NULL,
  body               TEXT NOT NULL,
  status             ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  verified_purchase  TINYINT(1) NOT NULL DEFAULT 0,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reviews_product_user (product_id, user_id),
  KEY idx_reviews_status (status, created_at),
  KEY idx_reviews_user (user_id),
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Customer activity
-- ---------------------------------------------------------------------

CREATE TABLE wishlists (
  user_id     INT UNSIGNED NOT NULL,
  product_id  INT UNSIGNED NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, product_id),
  KEY idx_wishlists_product (product_id),
  CONSTRAINT fk_wishlists_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_wishlists_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recently_viewed (
  user_id     INT UNSIGNED NOT NULL,
  product_id  INT UNSIGNED NOT NULL,
  viewed_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, product_id),
  KEY idx_recently_viewed_user_time (user_id, viewed_at),
  CONSTRAINT fk_recently_viewed_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_recently_viewed_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Persistent carts for logged-in users (guests keep their cart in the PHP session)
CREATE TABLE carts (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NOT NULL,
  coupon_code  VARCHAR(40) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_carts_user (user_id),
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cart_items (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cart_id     INT UNSIGNED NOT NULL,
  product_id  INT UNSIGNED NOT NULL,
  quantity    SMALLINT UNSIGNED NOT NULL,
  added_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cart_items_cart_product (cart_id, product_id),
  KEY idx_cart_items_product (product_id),
  CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_cart_items_qty CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  type        VARCHAR(40)  NOT NULL,
  title       VARCHAR(150) NOT NULL,
  body        VARCHAR(500) NOT NULL DEFAULT '',
  link        VARCHAR(255) NULL,
  read_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_user_read (user_id, read_at, created_at),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Orders
-- ---------------------------------------------------------------------

CREATE TABLE shipping_methods (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(80)  NOT NULL,
  description  VARCHAR(190) NOT NULL DEFAULT '',
  cost         DECIMAL(10,2) NOT NULL,
  free_over    DECIMAL(10,2) NULL,
  min_days     TINYINT UNSIGNED NOT NULL,
  max_days     TINYINT UNSIGNED NOT NULL,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  sort_order   SMALLINT NOT NULL DEFAULT 0,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT chk_shipping_cost CHECK (cost >= 0),
  CONSTRAINT chk_shipping_days CHECK (min_days <= max_days)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coupons (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code            VARCHAR(40) NOT NULL,
  description     VARCHAR(190) NOT NULL DEFAULT '',
  type            ENUM('percent','fixed','free_shipping') NOT NULL,
  value           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  min_subtotal    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  max_discount    DECIMAL(10,2) NULL,
  usage_limit     INT UNSIGNED NULL,
  per_user_limit  INT UNSIGNED NULL DEFAULT 1,
  used_count      INT UNSIGNED NOT NULL DEFAULT 0,
  starts_at       DATETIME NULL,
  expires_at      DATETIME NULL,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_coupons_code (code),
  CONSTRAINT chk_coupons_value CHECK (value >= 0 AND (type <> 'percent' OR value <= 100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_number        VARCHAR(32) NOT NULL,
  user_id             INT UNSIGNED NOT NULL,
  status              ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  payment_method      ENUM('cod','demo') NOT NULL,
  payment_status      ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  subtotal            DECIMAL(10,2) NOT NULL,
  discount_total      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  shipping_total      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  grand_total         DECIMAL(10,2) NOT NULL,
  coupon_code         VARCHAR(40) NULL,
  shipping_method_id  INT UNSIGNED NULL,
  shipping_method     VARCHAR(80) NOT NULL,
  shipping_name       VARCHAR(120) NOT NULL,
  email               VARCHAR(190) NOT NULL,
  phone               VARCHAR(30)  NOT NULL,
  address_line1       VARCHAR(190) NOT NULL,
  address_line2       VARCHAR(190) NULL,
  city                VARCHAR(100) NOT NULL,
  postal_code         VARCHAR(20)  NOT NULL,
  country_code        CHAR(2)      NOT NULL,
  notes               VARCHAR(500) NULL,
  idempotency_key     CHAR(64) NOT NULL,
  placed_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cancelled_at        DATETIME NULL,
  updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_number (order_number),
  UNIQUE KEY uq_orders_idempotency (idempotency_key),
  KEY idx_orders_user_placed (user_id, placed_at),
  KEY idx_orders_status_placed (status, placed_at),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_orders_shipping FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_orders_totals CHECK (subtotal >= 0 AND discount_total >= 0 AND shipping_total >= 0 AND grand_total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Name, SKU and price are copied at purchase time so order history never changes
CREATE TABLE order_items (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id      INT UNSIGNED NOT NULL,
  product_id    INT UNSIGNED NULL,
  seller_id     INT UNSIGNED NULL,
  product_name  VARCHAR(190) NOT NULL,
  sku           VARCHAR(64)  NOT NULL,
  image         VARCHAR(255) NULL,
  unit_price    DECIMAL(10,2) NOT NULL,
  quantity      SMALLINT UNSIGNED NOT NULL,
  line_total    DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id),
  KEY idx_order_items_product (product_id),
  KEY idx_order_items_seller (seller_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_order_items_seller FOREIGN KEY (seller_id) REFERENCES sellers (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_order_items_qty CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_status_history (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id    INT UNSIGNED NOT NULL,
  status      ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL,
  note        VARCHAR(255) NULL,
  changed_by  INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_order_status_history_order (order_id, created_at),
  CONSTRAINT fk_osh_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_osh_user FOREIGN KEY (changed_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coupon_redemptions (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  coupon_id        INT UNSIGNED NOT NULL,
  user_id          INT UNSIGNED NULL,
  order_id         INT UNSIGNED NOT NULL,
  discount_amount  DECIMAL(10,2) NOT NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_coupon_redemptions_order (order_id),
  KEY idx_coupon_redemptions_coupon_user (coupon_id, user_id),
  CONSTRAINT fk_cr_coupon FOREIGN KEY (coupon_id) REFERENCES coupons (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_cr_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_cr_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo/COD payment records. No card data is ever stored.
CREATE TABLE payments (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id    INT UNSIGNED NOT NULL,
  method      ENUM('cod','demo') NOT NULL,
  status      ENUM('pending','succeeded','failed','refunded') NOT NULL DEFAULT 'pending',
  amount      DECIMAL(10,2) NOT NULL,
  reference   VARCHAR(64) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payments_reference (reference),
  KEY idx_payments_order (order_id),
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Store content and operations
-- ---------------------------------------------------------------------

CREATE TABLE banners (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title       VARCHAR(120) NOT NULL,
  subtitle    VARCHAR(255) NOT NULL DEFAULT '',
  cta_label   VARCHAR(40)  NOT NULL DEFAULT 'Shop now',
  cta_url     VARCHAR(255) NOT NULL,
  image       VARCHAR(255) NULL,
  theme       VARCHAR(20)  NOT NULL DEFAULT 'dark',
  placement   ENUM('hero','promo') NOT NULL DEFAULT 'promo',
  sort_order  SMALLINT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  starts_at   DATETIME NULL,
  ends_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_banners_placement (placement, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  `key`       VARCHAR(80) NOT NULL,
  `value`     TEXT NOT NULL,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NULL,
  name        VARCHAR(120) NOT NULL,
  email       VARCHAR(190) NOT NULL,
  subject     VARCHAR(150) NOT NULL,
  message     TEXT NOT NULL,
  status      ENUM('new','read','archived') NOT NULL DEFAULT 'new',
  ip_hash     CHAR(64) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_contact_status (status, created_at),
  CONSTRAINT fk_contact_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE newsletter_subscribers (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email       VARCHAR(190) NOT NULL,
  status      ENUM('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_newsletter_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NULL,
  action       VARCHAR(60) NOT NULL,
  entity_type  VARCHAR(40) NOT NULL,
  entity_id    INT UNSIGNED NULL,
  details      LONGTEXT NULL CHECK (details IS NULL OR JSON_VALID(details)),
  ip_hash      CHAR(64) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_entity (entity_type, entity_id),
  KEY idx_audit_created (created_at),
  KEY idx_audit_user (user_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
  scope            VARCHAR(30) NOT NULL,
  identifier_hash  CHAR(64) NOT NULL,
  hits             INT UNSIGNED NOT NULL DEFAULT 0,
  window_start     DATETIME NOT NULL,
  PRIMARY KEY (scope, identifier_hash),
  KEY idx_rate_limits_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Demo data
-- =====================================================================

INSERT INTO settings (`key`, `value`) VALUES
  ('store_name',        'LemboShop'),
  ('store_tagline',     'Everyday tech, home and style — delivered fast.'),
  ('announcement',      'Free standard shipping on orders over €75 · 30-day free returns'),
  ('contact_email',     'support@lemboshop.test'),
  ('contact_phone',     '+49 30 1234 5678'),
  ('contact_address',   'Lembo Platz 7, 10115 Berlin, Germany'),
  ('support_hours',     'Mon–Fri 9:00–18:00 CET'),
  ('return_days',       '30'),
  ('reviews_require_approval', '1'),
  ('low_stock_default', '5');

-- Top-level categories (ids 1–8) and subcategories
INSERT INTO categories (id, parent_id, name, slug, description, icon, sort_order) VALUES
  (1,  NULL, 'Electronics',      'electronics',      'Phones, laptops, audio and smart devices.',     'cpu',      1),
  (2,  NULL, 'Home & Kitchen',   'home-kitchen',     'Cookware, appliances and home essentials.',     'home',     2),
  (3,  NULL, 'Fashion',          'fashion',          'Clothing, shoes and accessories.',              'shirt',    3),
  (4,  NULL, 'Beauty & Care',    'beauty-care',      'Skincare, haircare and grooming.',              'sparkle',  4),
  (5,  NULL, 'Sports & Outdoors','sports-outdoors',  'Fitness gear, outdoor and cycling.',            'activity', 5),
  (6,  NULL, 'Books & Media',    'books-media',      'Bestsellers, learning and entertainment.',      'book',     6),
  (7,  NULL, 'Toys & Games',     'toys-games',       'Games, puzzles and creative play.',             'puzzle',   7),
  (8,  NULL, 'Office & Desk',    'office-desk',      'Workspace furniture, stationery and storage.',  'briefcase',8),
  (9,  1, 'Smartphones',         'smartphones',      NULL, NULL, 1),
  (10, 1, 'Laptops',             'laptops',          NULL, NULL, 2),
  (11, 1, 'Audio',               'audio',            NULL, NULL, 3),
  (12, 1, 'Wearables',           'wearables',        NULL, NULL, 4),
  (13, 1, 'Smart Home',          'smart-home',       NULL, NULL, 5),
  (14, 2, 'Cookware',            'cookware',         NULL, NULL, 1),
  (15, 2, 'Small Appliances',    'small-appliances', NULL, NULL, 2),
  (16, 2, 'Home Decor',          'home-decor',       NULL, NULL, 3),
  (17, 3, 'Men',                 'men',              NULL, NULL, 1),
  (18, 3, 'Women',               'women',            NULL, NULL, 2),
  (19, 3, 'Shoes',               'shoes',            NULL, NULL, 3),
  (20, 3, 'Bags & Accessories',  'bags-accessories', NULL, NULL, 4),
  (21, 4, 'Skincare',            'skincare',         NULL, NULL, 1),
  (22, 4, 'Hair & Grooming',     'hair-grooming',    NULL, NULL, 2),
  (23, 5, 'Fitness',             'fitness',          NULL, NULL, 1),
  (24, 5, 'Outdoor & Camping',   'outdoor-camping',  NULL, NULL, 2),
  (25, 6, 'Books',               'books',            NULL, NULL, 1),
  (26, 7, 'Board Games',         'board-games',      NULL, NULL, 1),
  (27, 8, 'Desk Setup',          'desk-setup',       NULL, NULL, 1);

INSERT INTO shipping_methods (id, name, description, cost, free_over, min_days, max_days, sort_order) VALUES
  (1, 'Standard',  'Tracked parcel delivery',              4.99, 75.00, 3, 5, 1),
  (2, 'Express',   'Priority handling, next working days', 12.99, NULL,  1, 2, 2),
  (3, 'Click & Collect', 'Pick up at the Berlin showroom',  0.00, NULL,  1, 1, 3);

INSERT INTO coupons (code, description, type, value, min_subtotal, max_discount, usage_limit, per_user_limit, starts_at, expires_at, is_active) VALUES
  ('WELCOME10', '10% off your first order',            'percent',       10.00,  0.00, 50.00, NULL, 1, '2026-01-01 00:00:00', '2027-12-31 23:59:59', 1),
  ('SAVE20',    '€20 off orders over €150',            'fixed',         20.00, 150.00, NULL, 500,  2, '2026-01-01 00:00:00', '2027-12-31 23:59:59', 1),
  ('FREESHIP',  'Free standard or express shipping',   'free_shipping',  0.00, 25.00, NULL,  NULL, 3, '2026-01-01 00:00:00', '2027-12-31 23:59:59', 1),
  ('SUMMER25',  'Summer sale — 25% off (ended)',       'percent',       25.00,  0.00, 80.00, NULL, 1, '2026-06-01 00:00:00', '2026-08-31 23:59:59', 1);
