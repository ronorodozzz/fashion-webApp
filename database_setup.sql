-- ==========================================================
-- TREND & FASHION BOUTIQUE SHOP
-- Database Schema & Sample Seeding Data
-- Target Database: fashion_boutique
-- Compatible with MySQL 5.7+ / MariaDB 10.4+ (XAMPP default)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `fashion_boutique` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `fashion_boutique`;

-- Drop existing tables to allow fresh re-import
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `product_sizes`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `address` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: products
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `category_id` INT NOT NULL,
  `gender` ENUM('Men', 'Women', 'Unisex') NOT NULL,
  `type_slug` VARCHAR(50) NOT NULL,
  `base_price` DECIMAL(10,2) NOT NULL,
  `has_size_pricing` TINYINT(1) DEFAULT 0,
  `description` TEXT NOT NULL,
  `primary_image` VARCHAR(255) NOT NULL,
  `is_featured` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: product_images
-- --------------------------------------------------------
CREATE TABLE `product_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `version_number` INT NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  `sort_order` INT DEFAULT 0,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: product_sizes (Dynamic size-based pricing for apparel & shoes)
-- --------------------------------------------------------
CREATE TABLE `product_sizes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `size_name` VARCHAR(20) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `stock_quantity` INT DEFAULT 25,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: orders
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(20) NOT NULL,
  `payment_method` ENUM('credit_card', 'cod') NOT NULL,
  `payment_status` ENUM('paid', 'pending_cod') NOT NULL,
  `confirmation_message` TEXT NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `shipping_fee` DECIMAL(10,2) DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `order_notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: order_items
-- --------------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_code` VARCHAR(50) NOT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `selected_size` VARCHAR(20) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL,
  `item_subtotal` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- SEED DATA
-- ==========================================================

-- Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Apparel & Fashion', 'apparel', 'Minimalist architectural silhouettes, tailored jackets, and fine shirts'),
(2, 'Bags', 'bags', 'Structured full-grain leather bags, totes, and travel companions'),
(3, 'Shoes', 'shoes', 'Artisan footwear hand-crafted from premium calfskin and wooden lasts'),
(4, 'Accessories', 'accessories', 'Bauhaus timepieces, jewelry, and luxury wardrobe essentials');

-- Default Demo Users (Password: password123)
INSERT INTO `users` (`id`, `full_name`, `email`, `username`, `password`, `phone`, `address`) VALUES
(1, 'Elena Vance', 'elena@trendfashion.com', 'fashionista', '$2y$10$mkzZSBONdkS.EM/Ae10iDumcSdBuzUfmh80c8ndIJfN0ebYqO9yOy', '+1 (555) 321-9876', '42 Atelier Boulevard, Suite 5A, New York, NY 10012'),
(2, 'Julian Thorne', 'julian@trendfashion.com', 'julian', '$2y$10$mkzZSBONdkS.EM/Ae10iDumcSdBuzUfmh80c8ndIJfN0ebYqO9yOy', '+1 (555) 789-4321', '88 Minimalist Way, London EC1A 1BB');

-- Products (12 items matching the 25 AVIF files exactly)
INSERT INTO `products` (`id`, `product_code`, `name`, `category_id`, `gender`, `type_slug`, `base_price`, `has_size_pricing`, `description`, `primary_image`, `is_featured`) VALUES
(1, 'MBAG-001', 'Men\'s Artisan Leather Weekender Duffle', 2, 'Men', 'bag', 145.00, 0, 'Hand-stitched from full-grain pull-up leather, this timeless travel duffle pairs rugged durability with minimalist architectural proportions. Features burnished edges, solid brass hardware, water-resistant canvas lining, and a reinforced bottom panel.', 'assets/men_bag_1_version_1.avif', 1),
(2, 'MJCK-001', 'Men\'s Structured Wool Hybrid Jacket', 1, 'Men', 'jacket', 110.00, 1, 'Tailored from a mid-weight virgin wool blend with a clean boxy silhouette. Sharp notch lapels, concealed horn-button placket, and deep jet pockets capture contemporary masculine minimalism. Size-adjusted fabric tailoring for seamless fit.', 'assets/men_jacket_1_version_1.avif', 1),
(3, 'MSHR-001', 'Men\'s Relaxed Linen-Cotton Plain Shirt', 1, 'Men', 'plainshirt', 42.00, 1, 'Spun from breathable French flax and combed organic cotton. Features an unstructured spread collar, French seams, and a relaxed drape designed for warm afternoons and understated layering.', 'assets/men_plainshirt_1_version_1.avif', 1),
(4, 'MSHO-001', 'Men\'s Hand-Burnished Leather Derby Shoes', 3, 'Men', 'shoe', 135.00, 1, 'Classic five-eyelet open-laced Derby shoes handcrafted in Tuscany. Features vegetable-tanned calfskin, Goodyear welted construction, and stacked leather heels with rubberized grip inserts.', 'assets/men_shoe_1_version_1.avif', 1),
(5, 'MWAT-001', 'Men\'s Bauhaus Monochrome Chronograph Watch', 4, 'Men', 'watch', 185.00, 0, 'A study in functional reductionism. Brushed 316L stainless steel case housing a precision Japanese quartz movement, scratch-resistant sapphire crystal glass, matte mocha dial, and quick-release vegetable-tanned leather band.', 'assets/men_watch_1_version_1.avif', 1),
(6, 'WBAG-001', 'Women\'s Sculptural Structured Leather Tote', 2, 'Women', 'bag', 165.00, 0, 'Defined by sharp architectural geometry and supple Nappa leather. Engineered with magnetic closures, suede interior, detachable crossbody strap, and custom gold-finished hardware for versatile luxury styling.', 'assets/women_bag_1_version_1.avif', 1),
(7, 'WJCK-001', 'Women\'s Minimalist Cropped Utility Jacket', 1, 'Women', 'jacket', 95.00, 1, 'A clean cropped silhouette tailored from dense organic cotton twill. Features sharp point collar, concealed zipper with horn snap placket, and angular front storm flap detailing for an elevated contemporary edge.', 'assets/women_jacket_1_version_1.avif', 1),
(8, 'WJCK-002', 'Women\'s Belted Double-Breasted Wool Coat', 1, 'Women', 'jacket', 140.00, 1, 'Crafted from double-faced Australian wool in rich mocha-tinged sand tones. Features exaggerated peak lapels, raglan sleeves, a self-tie waist belt, and deep welt pockets.', 'assets/women_jacket_2_version_1.avif', 1),
(9, 'WSHR-001', 'Women\'s Mulberry Silk Drape Blouse Shirt', 1, 'Women', 'shirt', 58.00, 1, 'Tailored from lustrous 19mm Mulberry silk with fluid drape and subtle matte sheen. Features mother-of-pearl buttons, clean extended cuffs, and a minimal band collar for effortless refinement.', 'assets/women_shirt_1_version_1.avif', 1),
(10, 'WSHO-001', 'Women\'s Sculpted Block-Heel Leather Mules', 3, 'Women', 'shoe', 110.00, 1, 'Sleek open-back square-toe mules shaped from glove-soft calfskin. Features a hand-carved geometric wooden block heel (55mm), cushioned leather footbed, and sharp modern welt line.', 'assets/women_shoe_1_version_1.avif', 1),
(11, 'WSHO-002', 'Women\'s Architectural Pointed Ankle Boots', 3, 'Women', 'shoe', 145.00, 1, 'Sharp pointed silhouette with an inner ankle zip closure. Handcrafted from smooth Italian leather, resting on an angled kitten heel with rubber non-slip injected sole.', 'assets/women_shoe_2_version_1.avif', 1),
(12, 'WWAT-001', 'Women\'s Rose Gold Petite Milanese Mesh Watch', 4, 'Women', 'watch', 175.00, 0, 'Delicate 28mm stainless steel casing plated in warm rose gold. Features an ultra-slim profile, sunray sand dial with minimalist indices, and an adjustable Milanese magnetic mesh strap.', 'assets/women_watch_1_version_1.avif', 1);

-- Product Images (all 25 AVIF assets mapped)
INSERT INTO `product_images` (`product_id`, `image_path`, `version_number`, `is_primary`, `sort_order`) VALUES
(1, 'assets/men_bag_1_version_1.avif', 1, 1, 1),
(1, 'assets/men_bag_1_version_2.avif', 2, 0, 2),
(2, 'assets/men_jacket_1_version_1.avif', 1, 1, 1),
(2, 'assets/men_jacket_1_version_2.avif', 2, 0, 2),
(3, 'assets/men_plainshirt_1_version_1.avif', 1, 1, 1),
(3, 'assets/men_plainshirt_1_version_2.avif', 2, 0, 2),
(4, 'assets/men_shoe_1_version_1.avif', 1, 1, 1),
(4, 'assets/men_shoe_1_version_2.avif', 2, 0, 2),
(5, 'assets/men_watch_1_version_1.avif', 1, 1, 1),
(5, 'assets/men_watch_1_version_2.avif', 2, 0, 2),
(6, 'assets/women_bag_1_version_1.avif', 1, 1, 1),
(6, 'assets/women_bag_1_version_2.avif', 2, 0, 2),
(6, 'assets/women_bag_1_version_3.avif', 3, 0, 3),
(7, 'assets/women_jacket_1_version_1.avif', 1, 1, 1),
(7, 'assets/women_jacket_1_version_2.avif', 2, 0, 2),
(8, 'assets/women_jacket_2_version_1.avif', 1, 1, 1),
(8, 'assets/women_jacket_2_version_2.avif', 2, 0, 2),
(9, 'assets/women_shirt_1_version_1.avif', 1, 1, 1),
(9, 'assets/women_shirt_1_version_2.avif', 2, 0, 2),
(10, 'assets/women_shoe_1_version_1.avif', 1, 1, 1),
(10, 'assets/women_shoe_1_version_2.avif', 2, 0, 2),
(11, 'assets/women_shoe_2_version_1.avif', 1, 1, 1),
(11, 'assets/women_shoe_2_version_2.avif', 2, 0, 2),
(12, 'assets/women_watch_1_version_1.avif', 1, 1, 1),
(12, 'assets/women_watch_1_version_2.avif', 2, 0, 2);

-- Product Sizes with Dynamic Size-Based Pricing (Dresses/Jackets/Shirts and Shoes differ by size)
INSERT INTO `product_sizes` (`product_id`, `size_name`, `price`, `stock_quantity`) VALUES
-- Men's Bag (One Size)
(1, 'One Size', 145.00, 15),

-- Men's Jacket (Dynamic size pricing)
(2, 'S', 110.00, 12),
(2, 'M', 120.00, 18),
(2, 'L', 128.00, 15),
(2, 'XL', 138.00, 8),

-- Men's Plain Shirt (Dynamic size pricing)
(3, 'S', 42.00, 20),
(3, 'M', 48.00, 25),
(3, 'L', 54.00, 20),
(3, 'XL', 60.00, 10),

-- Men's Shoe (Dynamic size pricing)
(4, 'EU 40', 135.00, 10),
(4, 'EU 41', 142.00, 15),
(4, 'EU 42', 150.00, 18),
(4, 'EU 43', 158.00, 12),
(4, 'EU 44', 165.00, 8),

-- Men's Watch (One Size)
(5, 'One Size', 185.00, 14),

-- Women's Bag (One Size)
(6, 'One Size', 165.00, 20),

-- Women's Cropped Jacket (Dynamic size pricing)
(7, 'XS', 95.00, 10),
(7, 'S', 105.00, 18),
(7, 'M', 115.00, 22),
(7, 'L', 125.00, 14),

-- Women's Wool Coat (Dynamic size pricing)
(8, 'S', 140.00, 10),
(8, 'M', 152.00, 16),
(8, 'L', 165.00, 14),
(8, 'XL', 178.00, 8),

-- Women's Silk Shirt (Dynamic size pricing)
(9, 'XS', 58.00, 14),
(9, 'S', 64.00, 20),
(9, 'M', 70.00, 24),
(9, 'L', 76.00, 12),

-- Women's Mules (Dynamic size pricing)
(10, 'EU 36', 110.00, 8),
(10, 'EU 37', 118.00, 14),
(10, 'EU 38', 125.00, 20),
(10, 'EU 39', 132.00, 15),
(10, 'EU 40', 140.00, 9),

-- Women's Boots (Dynamic size pricing)
(11, 'EU 36', 145.00, 7),
(11, 'EU 37', 155.00, 12),
(11, 'EU 38', 165.00, 16),
(11, 'EU 39', 175.00, 14),
(11, 'EU 40', 185.00, 8),

-- Women's Watch (One Size)
(12, 'One Size', 175.00, 16);
