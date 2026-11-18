-- =========================================================================
-- Multi-Concern Architecture Migration
-- Based on Multi-Concern Implementation Blueprint (concern.pdf)
-- =========================================================================

-- 1. Create concerns Master Table
CREATE TABLE IF NOT EXISTS `concerns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL COMMENT 'Display Name (e.g. GRM Apparels Unit 1)',
  `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Short unique code (e.g. CN-01, GRM-MFG)',
  `legal_name` VARCHAR(255) NULL COMMENT 'Registered Company Name',
  `gst_number` VARCHAR(15) NULL COMMENT '15-digit GSTIN',
  `pan_number` VARCHAR(10) NULL COMMENT '10-digit PAN',
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(100) NULL,
  `pincode` VARCHAR(10) NULL,
  `phone` VARCHAR(20) NULL,
  `email` VARCHAR(150) NULL,
  `logo_url` VARCHAR(255) NULL COMMENT 'Brand logo printed on invoices/labels',
  `invoice_prefix` VARCHAR(20) DEFAULT 'INV' COMMENT 'Prefix for POS & bills (e.g. GRM-A-)',
  `po_prefix` VARCHAR(20) DEFAULT 'PO' COMMENT 'Prefix for purchases (e.g. PO-A-)',
  `dispatch_prefix` VARCHAR(20) DEFAULT 'DSP' COMMENT 'Prefix for gate passes',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` INT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_concerns_code` (`code`),
  INDEX `idx_concerns_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create concern_modules Entitlement Matrix Table
CREATE TABLE IF NOT EXISTS `concern_modules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `concern_id` INT NOT NULL,
  `module_key` VARCHAR(50) NOT NULL COMMENT 'e.g. production, purchases, warehouse, retail_pos, digital_sales, returns, expenses, assets',
  `is_enabled` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_concern_module` (`concern_id`, `module_key`),
  CONSTRAINT `fk_concern_module_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create user_concerns Access Control Table
CREATE TABLE IF NOT EXISTS `user_concerns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `concern_id` INT NOT NULL,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_concern` (`user_id`, `concern_id`),
  CONSTRAINT `fk_user_concerns_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_concerns_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
