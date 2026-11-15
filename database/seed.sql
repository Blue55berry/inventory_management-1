-- ============================================================
-- GRM INVENTORY SOFTWARE — CLEAN MASTER SEED DATA
-- Updated: 2026-11-15 17:42:13
-- Contains: Admin user, roles, permissions, master categories,
--           colors, sizes, designs, units, HSN, asset categories & settings
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Roles Seed
-- ------------------------------------------------------------
INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_active`, `is_system`, `created_at`) VALUES
(1, 'Super Admin', 'super-admin', 'Full system access and user administration', 1, 1, NOW()),
(2, 'Admin', 'admin', 'Business administration and managerial approvals', 1, 1, NOW()),
(3, 'Production Manager', 'production-manager', 'Production planning and batch tracking', 1, 1, NOW()),
(4, 'Production Staff', 'production-staff', 'Floor operations and cutting entries', 1, 1, NOW()),
(5, 'Warehouse Manager', 'warehouse-manager', 'Warehouse receiving and allocations', 1, 1, NOW()),
(6, 'Warehouse Staff', 'warehouse-staff', 'Picking, packing, and dispatch', 1, 1, NOW());

-- ------------------------------------------------------------
-- Organization Foundations
-- ------------------------------------------------------------
INSERT INTO `production_units` (`id`, `name`, `code`, `address`, `location`, `capacity_per_day`, `is_active`, `created_at`) VALUES
(1, 'Production Factory - Unit 1', 'PU-01', 'Main Industrial Complex, Tirupur', 'Tirupur', 2500, 1, NOW());

INSERT INTO `warehouses` (`id`, `name`, `code`, `address`, `is_active`, `created_at`) VALUES
(1, 'Central Warehouse', 'WH-01', 'Central Logistics Hub, Tirupur', 1, NOW());

INSERT INTO `shops` (`id`, `name`, `code`, `address`, `is_active`, `created_at`) VALUES
(1, 'Factory Outlet / Showroom', 'SH-01', 'Factory Front Showroom, Tirupur', 1, NOW());

INSERT INTO `sales_channels` (`id`, `name`, `code`, `is_active`, `created_at`) VALUES
(1, 'Direct Store POS', 'POS', 1, NOW()),
(2, 'WhatsApp Catalog Sales', 'WHATSAPP', 1, NOW()),
(3, 'B2B Wholesale Channel', 'WHOLESALE', 1, NOW());

-- ------------------------------------------------------------
-- Users Seed (Admin Only)
-- ------------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `emp_id`, `email`, `password_hash`, `full_name`, `phone`, `role_id`, `department`, `production_unit_id`, `warehouse_id`, `shop_id`, `reports_access`, `is_active`, `must_change_password`, `created_at`) VALUES
(1, 'admin', 'EMP-001', 'admin@grm.com', '$2y$10$WXeV9abEWzjWySfggVzhTOp4j7pzF7UuFNUJ1lrLbWe9NDL7Cz7UC', 'System Administrator', '9876543210', 1, 'ADMINISTRATION', 1, 1, 1, 'ALL', 1, 0, NOW());

-- ------------------------------------------------------------
-- Product Categories
-- ------------------------------------------------------------
INSERT INTO `categories` (`id`, `name`, `code`, `slug`, `description`, `hsn_code`, `gst_rate`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Kids Wear', 'KW', 'kids-wear', 'Children apparel and garments', '6109', '5.00', 1, 1, NOW()),
(2, 'Men\'s Wear', 'MW', 'mens-wear', 'Men apparel, t-shirts, shirts, casuals', '6109', '5.00', 1, 2, NOW()),
(3, 'Women\'s Wear', 'WW', 'womens-wear', 'Women apparel, tops, dresses, kurtis', '6109', '5.00', 1, 3, NOW()),
(4, 'Baby Wear', 'BW', 'baby-wear', 'Infant clothing, rompers, baby sets', '6109', '5.00', 1, 4, NOW()),
(5, 'Accessories & Other', 'ACC', 'accessories-other', 'Caps, masks, bibs, socks', '6109', '5.00', 1, 5, NOW());

INSERT INTO `subcategories` (`id`, `category_id`, `name`, `code`, `slug`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 1, 'Kids T-Shirt', 'KT', 'kids-t-shirt', 1, 1, NOW()),
(2, 1, 'Kids Shorts & Pants', 'KP', 'kids-shorts-pants', 1, 2, NOW()),
(3, 1, 'Kids Nightwear', 'KN', 'kids-nightwear', 1, 3, NOW()),
(4, 2, 'Men Polo T-Shirt', 'MPT', 'men-polo-t-shirt', 1, 1, NOW()),
(5, 2, 'Men Round Neck T-Shirt', 'MRT', 'men-round-neck-t-shirt', 1, 2, NOW()),
(6, 3, 'Women Casual Tops', 'WCT', 'women-casual-tops', 1, 1, NOW()),
(7, 3, 'Women Frocks & Dresses', 'WFD', 'women-frocks-dresses', 1, 2, NOW()),
(8, 4, 'Baby Rompers', 'BR', 'baby-rompers', 1, 1, NOW());

INSERT INTO `sub_subcategories` (`id`, `subcategory_id`, `name`, `code`, `slug`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 1, 'Half Sleeve Cotton', 'HSC', 'half-sleeve-cotton', 1, 1, NOW()),
(2, 1, 'Full Sleeve Winter', 'FSW', 'full-sleeve-winter', 1, 2, NOW()),
(3, 4, 'Bio-Wash Combed Cotton', 'BWC', 'bio-wash-combed-cotton', 1, 1, NOW()),
(4, 5, 'Single Jersey 180 GSM', 'SJ180', 'single-jersey-180-gsm', 1, 2, NOW());

INSERT INTO `warehouse_categories` (`id`, `name`, `category_type`, `code`, `slug`, `hsn_code`, `gst_rate`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'Kids Wear', 'NORMAL', 'WH-KW', 'wh-kids-wear', '6109', '5.00', 1, 1, NOW()),
(2, 'Men\'s Wear', 'NORMAL', 'WH-MW', 'wh-mens-wear', '6109', '5.00', 2, 1, NOW()),
(3, 'Women\'s Wear', 'NORMAL', 'WH-WW', 'wh-womens-wear', '6109', '5.00', 3, 1, NOW()),
(4, 'Baby Wear', 'NORMAL', 'WH-BW', 'wh-baby-wear', '6109', '5.00', 4, 1, NOW()),
(5, 'Accessories & Other', 'NORMAL', 'WH-ACC', 'wh-accessories-other', '6109', '5.00', 5, 1, NOW());

INSERT INTO `warehouse_subcategories` (`id`, `category_id`, `name`, `code`, `slug`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 1, 'Kids T-Shirt', 'WH-KT', 'wh-kids-t-shirt', 1, 1, NOW()),
(2, 2, 'Men Polo T-Shirt', 'WH-MPT', 'wh-men-polo-t-shirt', 2, 1, NOW());

INSERT INTO `warehouse_sub_subcategories` (`id`, `subcategory_id`, `name`, `code`, `slug`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 1, 'Half Sleeve Cotton', 'WH-HSC', 'wh-half-sleeve-cotton', 1, 1, NOW()),
(2, 2, 'Bio-Wash Combed Cotton', 'WH-BWC', 'wh-bio-wash-combed-cotton', 2, 1, NOW());

-- ------------------------------------------------------------
-- Brands Seed
-- ------------------------------------------------------------
INSERT INTO `brands` (`id`, `name`, `code`, `description`, `is_active`, `created_at`) VALUES
(1, 'GRM Apparels', 'GRM', 'In-house manufacturing flagship brand', 1, NOW()),
(2, 'Mini Trendz', 'MT', 'Kids and toddlers fashion line', 1, NOW()),
(3, 'Urban Fit', 'UF', 'Men and women contemporary casual wear', 1, NOW());

-- ------------------------------------------------------------
-- Colors, Sizes & Designs
-- ------------------------------------------------------------
INSERT INTO `colors` (`id`, `name`, `hex_code`, `is_active`, `created_at`) VALUES
(1, 'Red', '#EF4444', 1, NOW()),
(2, 'Navy Blue', '#1E3A8A', 1, NOW()),
(3, 'Sky Blue', '#38BDF8', 1, NOW()),
(4, 'White', '#FFFFFF', 1, NOW()),
(5, 'Black', '#111827', 1, NOW()),
(6, 'Bottle Green', '#15803D', 1, NOW()),
(7, 'Yellow', '#FACC15', 1, NOW()),
(8, 'Maroon', '#831843', 1, NOW()),
(9, 'Charcoal Melange', '#374151', 1, NOW()),
(10, 'Baby Pink', '#F472B6', 1, NOW());

INSERT INTO `sizes` (`id`, `name`, `code`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'XS', 'XS', 1, 1, NOW()),
(2, 'S', 'S', 2, 1, NOW()),
(3, 'M', 'M', 3, 1, NOW()),
(4, 'L', 'L', 4, 1, NOW()),
(5, 'XL', 'XL', 5, 1, NOW()),
(6, 'XXL', 'XXL', 6, 1, NOW()),
(7, '3XL', '3XL', 7, 1, NOW()),
(8, '2-3Y', '2-3Y', 8, 1, NOW()),
(9, '3-4Y', '3-4Y', 9, 1, NOW()),
(10, '4-5Y', '4-5Y', 10, 1, NOW()),
(11, '5-6Y', '5-6Y', 11, 1, NOW());

INSERT INTO `designs` (`id`, `name`, `code`, `description`, `is_active`, `created_at`) VALUES
(1, 'Plain Solid', 'PLN', 'Solid plain dyed single jersey', 1, NOW()),
(2, 'Striped Pattern', 'STR', 'Yarn-dyed feeder stripes', 1, NOW()),
(3, 'Panda Print', 'PAN', 'Chest panda cartoon placement graphic', 1, NOW()),
(4, 'Floral Print', 'FLR', 'All-over botanical floral rotary print', 1, NOW()),
(5, 'Checkered Pattern', 'CHK', 'Woven check design', 1, NOW()),
(6, 'Typography Graphic', 'TYP', 'Urban streetwear text chest print', 1, NOW());

-- ------------------------------------------------------------
-- Standard Lookups
-- ------------------------------------------------------------
INSERT INTO `hsn_codes` (`id`, `hsn_code`, `description`, `gst_rate`, `created_at`) VALUES
(1, '6109', 'T-shirts, singlets and other vests, knitted or crocheted', 5.00, NOW()),
(2, '6105', 'Men or boys shirts, knitted or crocheted', 5.00, NOW()),
(3, '6203', 'Men or boys suits, ensembles, jackets, blazers, trousers', 5.00, NOW()),
(4, '6204', 'Women or girls suits, ensembles, jackets, dresses, skirts', 5.00, NOW());

INSERT INTO `units` (`id`, `name`, `abbreviation`, `is_active`, `created_at`) VALUES
(1, 'Pieces', 'PCS', 1, NOW()),
(2, 'Meters', 'MTR', 1, NOW()),
(3, 'Kilograms', 'KGS', 1, NOW()),
(4, 'Dozens', 'DOZ', 1, NOW());

INSERT INTO `expense_categories` (`id`, `name`, `code`, `is_active`, `created_at`) VALUES
(1, 'Factory Electricity & Power', 'ELEC', 1, NOW()),
(2, 'Sewing Thread & Machine Needles', 'THR_NDL', 1, NOW()),
(3, 'Machine Maintenance & Spares', 'MCH_MNT', 1, NOW()),
(4, 'Diesel & Generator Fuel', 'GEN_FUEL', 1, NOW()),
(5, 'Packaging Materials & Poly Bags', 'PKG_MAT', 1, NOW()),
(6, 'Transport & Logistics', 'LOGISTICS', 1, NOW());

INSERT INTO `roll_damage_reasons` (`id`, `reason`, `created_at`) VALUES
(1, 'Hole / Fabric Puncture', NOW()),
(2, 'Oil / Grease Stain', NOW()),
(3, 'Weaving Defect / Slub', NOW()),
(4, 'Color Variation / Patchy Dye', NOW()),
(5, 'GSM / Weight Variance', NOW());

-- ------------------------------------------------------------
-- Asset Categories Seed
-- ------------------------------------------------------------
INSERT INTO `asset_categories` (`id`, `name`, `code`, `icon`, `description`, `depreciation_rate_annual`, `is_active`, `created_at`) VALUES
(1, 'Cutting Machinery', 'CUT_MACH', 'bi-scissors', 'Fabric spreaders, straight knife, and band knife cutting machines', 15.00, 1, NOW()),
(2, 'Stitching & Sewing Machines', 'STITCH_SEW', 'bi-gear', 'Single needle lockstitch, overlock, flatlock, button hole machines', 15.00, 1, NOW()),
(3, 'Finishing & Steam Press', 'FINISH_PRESS', 'bi-fire', 'Steam ironing vacuum tables, boilers, stain removing guns', 15.00, 1, NOW()),
(4, 'Packaging & Barcode Equipment', 'PKG_BC', 'bi-upc-scan', 'Thermal label barcode printers, scanners, carton sealers', 20.00, 1, NOW()),
(5, 'Generators & Power Systems', 'GEN_PWR', 'bi-lightning-charge', 'Diesel generator sets, heavy-duty UPS units, servo stabilizers', 10.00, 1, NOW()),
(6, 'IT & Office Hardware', 'IT_OFFICE', 'bi-pc-display', 'Billing desktop PCs, network switches, CCTV DVRs, biometric attendance', 25.00, 1, NOW());

-- ------------------------------------------------------------
-- System Settings Seed
-- ------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `is_public`, `created_at`) VALUES
('company_name', 'GRM Apparels', 'general', 1, NOW()),
('company_email', 'admin@grm.com', 'general', 1, NOW()),
('company_phone', '9876543210', 'general', 1, NOW()),
('currency_symbol', '₹', 'general', 1, NOW()),
('currency_code', 'INR', 'general', 1, NOW()),
('timezone', 'Asia/Kolkata', 'general', 1, NOW()),
('default_warehouse_id', '1', 'inventory', 0, NOW()),
('default_production_unit_id', '1', 'production', 0, NOW());

SET FOREIGN_KEY_CHECKS = 1;
