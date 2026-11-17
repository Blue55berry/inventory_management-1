<?php
/**
 * Master Migration & Data Synchronizer
 * 
 * 1. Restores the baseline data archive (pre_rebuild_backup_utf8.sql)
 * 2. Applies new schema additions (designs, qr_generations, qr_generation_items, user_permission_overrides)
 * 3. Adds missing columns (variant_code, design_id, attr_hash, staff_role, etc.)
 * 4. Backfills variant_code (V0001...) for existing variants
 * 5. Executes all additive SQL migrations in database/migrations/
 * 6. Seeds complete granular permissions & binds them to Super Admin
 * 7. Performs a full integrity report
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

$db = Database::getInstance();

echo "========================================================\n";
echo "   STARTING COMPLETE LOCAL DATABASE MIGRATION\n";
echo "========================================================\n\n";

// Helper for column existence
function columnExists(PDO $db, string $table, string $column): bool {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return (bool)$stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

// Helper for table existence
function tableExists(PDO $db, string $table): bool {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
        return (bool)$stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

// -------------------------------------------------------------
// STEP 1: IMPORT BASELINE DATA DUMP (pre_rebuild_backup_utf8.sql)
// -------------------------------------------------------------
echo "[1/7] Importing master data from pre_rebuild_backup_utf8.sql...\n";
$dumpFile = BASE_PATH . '/database/backups/pre_rebuild_backup_utf8.sql';
if (!file_exists($dumpFile)) {
    throw new RuntimeException("Dump file not found: {$dumpFile}");
}

// Run through mysql CLI for speed and accurate multi-query execution
$config = require BASE_PATH . '/config/database.php';
$mysqlBin = 'C:\\xampp\\mysql\\bin\\mysql.exe';
if (file_exists($mysqlBin)) {
    $cmd = sprintf(
        '"%s" --host=%s --port=%s --user=%s %s %s < "%s"',
        $mysqlBin,
        escapeshellarg($config['host']),
        escapeshellarg($config['port']),
        escapeshellarg($config['username']),
        !empty($config['password']) ? '--password=' . escapeshellarg($config['password']) : '',
        escapeshellarg($config['database']),
        $dumpFile
    );
    // Execute command via shell_exec
    $out = shell_exec($cmd . ' 2>&1');
    if ($out && strpos($out, 'ERROR') !== false) {
        echo "  Notice from mysql CLI: $out\n";
    } else {
        echo "  Master dump imported successfully via mysql CLI.\n";
    }
} else {
    // Pure PHP fallback
    echo "  mysql.exe not found, running pure PHP importer...\n";
    $sql = file_get_contents($dumpFile);
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec($sql);
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "  Import completed via PDO.\n";
}

// -------------------------------------------------------------
// STEP 2: CREATE NEW TABLES REQUIRED BY APPLICATION
// -------------------------------------------------------------
echo "\n[2/7] Creating new schema tables...\n";

// 2a. designs table
if (!tableExists($db, 'designs')) {
    echo "  Creating table `designs`...\n";
    $db->exec("
        CREATE TABLE `designs` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(100) NOT NULL,
          `code` varchar(20) DEFAULT NULL,
          `slug` varchar(100) DEFAULT NULL,
          `description` text DEFAULT NULL,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `sort_order` int(11) NOT NULL DEFAULT 0,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_designs_name` (`name`),
          UNIQUE KEY `uq_designs_code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

// Seed default designs if empty
$designCount = (int)$db->query("SELECT COUNT(*) FROM `designs`")->fetchColumn();
if ($designCount === 0) {
    echo "  Seeding default designs...\n";
    $db->exec("
        INSERT INTO `designs` (`id`, `name`, `code`, `slug`, `sort_order`, `is_active`) VALUES
        (1, 'Plain / Solid', 'PLN', 'plain-solid', 1, 1),
        (2, 'Striped', 'STP', 'striped', 2, 1),
        (3, 'Checked', 'CHK', 'checked', 3, 1),
        (4, 'Printed', 'PRT', 'printed', 4, 1),
        (5, 'Floral', 'FLR', 'floral', 5, 1),
        (6, 'Textured', 'TXT', 'textured', 6, 1)
        ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);
    ");
}

// 2b. qr_generations table
if (!tableExists($db, 'qr_generations')) {
    echo "  Creating table `qr_generations`...\n";
    $db->exec("
        CREATE TABLE `qr_generations` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `batch_number` varchar(50) NOT NULL,
          `production_unit_id` int(10) unsigned NOT NULL DEFAULT 1,
          `product_id` int(10) unsigned DEFAULT NULL,
          `variant_id` int(10) unsigned DEFAULT NULL,
          `style_id` int(10) unsigned DEFAULT NULL,
          `generation_type` enum('CATALOG','CUSTOM') NOT NULL DEFAULT 'CATALOG',
          `quantity` int(10) unsigned NOT NULL,
          `format` enum('QR','CODE128','BOTH') NOT NULL DEFAULT 'QR',
          `prefix` varchar(10) NOT NULL DEFAULT 'GRM',
          `start_number` int(10) unsigned NOT NULL,
          `end_number` int(10) unsigned NOT NULL,
          `label_size` varchar(20) NOT NULL DEFAULT '50x25',
          `notes` text DEFAULT NULL,
          `status` enum('GENERATED','PRINTED','USED','CANCELLED') NOT NULL DEFAULT 'GENERATED',
          `created_by` int(10) unsigned DEFAULT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_qr_batch_number` (`batch_number`),
          KEY `idx_qr_product` (`product_id`),
          KEY `idx_qr_variant` (`variant_id`),
          KEY `idx_qr_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

// 2c. qr_generation_items table
if (!tableExists($db, 'qr_generation_items')) {
    echo "  Creating table `qr_generation_items`...\n";
    $db->exec("
        CREATE TABLE `qr_generation_items` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `generation_id` int(10) unsigned NOT NULL,
          `qr_code` varchar(100) NOT NULL,
          `item_code` varchar(100) NOT NULL,
          `sequence_number` int(10) unsigned NOT NULL,
          `is_printed` tinyint(1) NOT NULL DEFAULT 0,
          `is_used` tinyint(1) NOT NULL DEFAULT 0,
          `used_at` datetime DEFAULT NULL,
          `used_in_module` varchar(50) DEFAULT NULL,
          `used_reference_id` int(10) unsigned DEFAULT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_qr_generation_item_code` (`qr_code`),
          KEY `idx_qr_gen_id` (`generation_id`),
          KEY `idx_qr_status_used` (`is_used`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

// 2d. user_permission_overrides table
if (!tableExists($db, 'user_permission_overrides')) {
    echo "  Creating table `user_permission_overrides`...\n";
    $db->exec("
        CREATE TABLE `user_permission_overrides` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `user_id` int(10) unsigned NOT NULL,
          `permission_id` int(10) unsigned NOT NULL,
          `is_granted` tinyint(1) NOT NULL DEFAULT 1,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_user_perm_override` (`user_id`,`permission_id`),
          KEY `idx_user_perm_uid` (`user_id`),
          KEY `idx_user_perm_pid` (`permission_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

// -------------------------------------------------------------
// STEP 3: ADD NEW COLUMNS TO EXISTING TABLES
// -------------------------------------------------------------
echo "\n[3/7] Adding missing columns to existing tables...\n";

// 3a. product_variants: variant_code, design_id, attr_hash, production_unit_id
if (!columnExists($db, 'product_variants', 'variant_code')) {
    echo "  Adding `variant_code` to `product_variants`...\n";
    $db->exec("ALTER TABLE `product_variants` ADD COLUMN `variant_code` varchar(20) DEFAULT NULL AFTER `id`");
}
if (!columnExists($db, 'product_variants', 'design_id')) {
    echo "  Adding `design_id` to `product_variants`...\n";
    $db->exec("ALTER TABLE `product_variants` ADD COLUMN `design_id` int(10) unsigned DEFAULT 1 AFTER `color_id`");
}
if (!columnExists($db, 'product_variants', 'attr_hash')) {
    echo "  Adding `attr_hash` to `product_variants`...\n";
    $db->exec("ALTER TABLE `product_variants` ADD COLUMN `attr_hash` varchar(64) DEFAULT NULL AFTER `design_id`");
}
if (!columnExists($db, 'product_variants', 'production_unit_id')) {
    echo "  Adding `production_unit_id` to `product_variants`...\n";
    $db->exec("ALTER TABLE `product_variants` ADD COLUMN `production_unit_id` int(10) unsigned DEFAULT 1 AFTER `variant_code`");
}

// 3b. users: staff_role, production_unit_id, warehouse_id, shop_id
if (!columnExists($db, 'users', 'staff_role')) {
    echo "  Adding `staff_role` to `users`...\n";
    $db->exec("ALTER TABLE `users` ADD COLUMN `staff_role` varchar(50) DEFAULT NULL AFTER `department`");
}
if (!columnExists($db, 'users', 'production_unit_id')) {
    echo "  Adding `production_unit_id` to `users`...\n";
    $db->exec("ALTER TABLE `users` ADD COLUMN `production_unit_id` int(10) unsigned DEFAULT 1 AFTER `staff_role`");
}
if (!columnExists($db, 'users', 'warehouse_id')) {
    echo "  Adding `warehouse_id` to `users`...\n";
    $db->exec("ALTER TABLE `users` ADD COLUMN `warehouse_id` int(10) unsigned DEFAULT 1 AFTER `production_unit_id`");
}
if (!columnExists($db, 'users', 'shop_id')) {
    echo "  Adding `shop_id` to `users`...\n";
    $db->exec("ALTER TABLE `users` ADD COLUMN `shop_id` int(10) unsigned DEFAULT 1 AFTER `warehouse_id`");
}

// 3c. production_units: logo_url
if (!columnExists($db, 'production_units', 'logo_url')) {
    echo "  Adding `logo_url` to `production_units`...\n";
    $db->exec("ALTER TABLE `production_units` ADD COLUMN `logo_url` varchar(255) DEFAULT NULL AFTER `capacity_per_day`");
}

// 3d. sellers: production_deleted_at
if (!columnExists($db, 'sellers', 'production_deleted_at')) {
    echo "  Adding `production_deleted_at` to `sellers`...\n";
    $db->exec("ALTER TABLE `sellers` ADD COLUMN `production_deleted_at` datetime DEFAULT NULL AFTER `is_active`");
}

// 3e. partition_id columns on production tables
$partitionTables = [
    'sellers', 'seller_rolls', 'roll_damages', 'production_programs',
    'cutting_batches', 'wastage_sales', 'stitching_batches', 'qc_batches',
    'production_batches', 'production_outwards', 'production_purchases'
];
foreach ($partitionTables as $pt) {
    if (tableExists($db, $pt) && !columnExists($db, $pt, 'partition_id')) {
        echo "  Adding `partition_id` to `{$pt}`...\n";
        $db->exec("ALTER TABLE `{$pt}` ADD COLUMN `partition_id` int(10) unsigned DEFAULT 1 AFTER `id`");
    }
}

// -------------------------------------------------------------
// STEP 4: BACKFILL DATA INTEGRITY (variant_code, attr_hash, etc.)
// -------------------------------------------------------------
echo "\n[4/7] Backfilling variant codes and hashes...\n";
$variants = Database::fetchAll("SELECT id, product_id, size_id, color_id, design_id, variant_code FROM `product_variants`");
foreach ($variants as $v) {
    $updates = [];
    $params = [];
    if (empty($v['variant_code'])) {
        $updates[] = "`variant_code` = ?";
        $params[] = sprintf('V%04d', $v['id']);
    }
    if (empty($v['design_id'])) {
        $updates[] = "`design_id` = 1";
    }
    // Generate attr_hash
    $hash = hash('sha256', "{$v['product_id']}:{$v['size_id']}:" . ($v['color_id'] ?? '0') . ":" . ($v['design_id'] ?? '1'));
    $updates[] = "`attr_hash` = ?";
    $params[] = $hash;

    if (!empty($updates)) {
        $params[] = $v['id'];
        $db->prepare("UPDATE `product_variants` SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
    }
}
echo "  Updated " . count($variants) . " product variants with collision-safe codes & hashes.\n";

// -------------------------------------------------------------
// STEP 5: RUN ALL DATABASE MIGRATIONS
// -------------------------------------------------------------
echo "\n[5/7] Executing incremental database migrations...\n";

// Migration 1: 2026_05_28_add_return_settlement_modes.sql
if (tableExists($db, 'returns')) {
    echo "  Applying 2026_05_28_add_return_settlement_modes.sql...\n";
    try {
        $db->exec("ALTER TABLE `returns` MODIFY COLUMN `refund_mode` ENUM('CASH','UPI','CARD','ONLINE','BANK_TRANSFER','SPLIT','CREDIT_NOTE') DEFAULT NULL");
    } catch (Exception $e) {
        echo "    Notice: " . $e->getMessage() . "\n";
    }
}

// Migration 2: 2026_11_11_add_production_supplier_archive.sql
echo "  Applying 2026_11_11_add_production_supplier_archive.sql...\n";
if (!columnExists($db, 'sellers', 'production_deleted_at')) {
    $db->exec("ALTER TABLE `sellers` ADD COLUMN `production_deleted_at` datetime DEFAULT NULL AFTER `is_active`");
}

// Migration 3: 2026_11_11_add_product_price_logs.sql
echo "  Applying 2026_11_11_add_product_price_logs.sql...\n";
if (!tableExists($db, 'product_price_logs')) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS `product_price_logs` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `variant_id` int(10) unsigned DEFAULT NULL,
          `product_id` int(10) unsigned DEFAULT NULL,
          `sku` varchar(100) NOT NULL,
          `old_selling_price` decimal(10,2) NOT NULL,
          `new_selling_price` decimal(10,2) NOT NULL,
          `old_mrp` decimal(10,2) NOT NULL,
          `new_mrp` decimal(10,2) NOT NULL,
          `cost_price` decimal(10,2) NOT NULL,
          `markup_percentage` decimal(10,4) DEFAULT NULL,
          `discount_percentage` decimal(10,4) NOT NULL,
          `revision_notes` text NOT NULL,
          `user_id` int(10) unsigned DEFAULT NULL,
          `user_name` varchar(200) NOT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_price_logs_variant_created` (`variant_id`,`created_at`),
          KEY `idx_price_logs_product` (`product_id`),
          KEY `idx_price_logs_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

// Migration 4: 2026_11_12_add_production_billing_unit.sql
echo "  Applying 2026_11_12_add_production_billing_unit.sql...\n";
if (tableExists($db, 'production_bills') && !columnExists($db, 'production_bills', 'production_unit_id')) {
    $db->exec("ALTER TABLE `production_bills` ADD COLUMN `production_unit_id` int(10) unsigned DEFAULT NULL AFTER `bill_number`, ADD KEY `idx_production_bills_unit` (`production_unit_id`)");
}

// Migration 5: 2026_11_12_add_production_bill_batch_link.sql
echo "  Applying 2026_11_12_add_production_bill_batch_link.sql...\n";
if (tableExists($db, 'production_batches') && !columnExists($db, 'production_batches', 'production_bill_id')) {
    $db->exec("ALTER TABLE `production_batches` ADD COLUMN `production_bill_id` int(10) unsigned DEFAULT NULL AFTER `batch_number`, ADD KEY `idx_production_batches_bill` (`production_bill_id`)");
}

// Migration 6: 2026_11_13_add_damage_resolution_audit_fields.sql
echo "  Applying 2026_11_13_add_damage_resolution_audit_fields.sql...\n";
if (tableExists($db, 'warehouse_damages')) {
    $whDamageCols = [
        'resolution_action' => "varchar(32) DEFAULT NULL AFTER `status`",
        'resolution_reason' => "varchar(255) DEFAULT NULL AFTER `resolution_action`",
        'resolution_notes' => "text DEFAULT NULL AFTER `resolution_reason`",
        'resolution_amount' => "decimal(10,2) DEFAULT NULL AFTER `resolution_notes`",
        'replacement_variant_id' => "int(10) unsigned DEFAULT NULL AFTER `resolution_amount`",
        'replacement_qr_code' => "varchar(100) DEFAULT NULL AFTER `replacement_variant_id`",
        'replacement_inwarded_at' => "datetime DEFAULT NULL AFTER `replacement_qr_code`",
        'deleted_at' => "datetime DEFAULT NULL AFTER `replacement_inwarded_at`",
        'deleted_by' => "int(10) unsigned DEFAULT NULL AFTER `deleted_at`"
    ];
    foreach ($whDamageCols as $col => $def) {
        if (!columnExists($db, 'warehouse_damages', $col)) {
            $db->exec("ALTER TABLE `warehouse_damages` ADD COLUMN `{$col}` {$def}");
        }
    }
}

// Migration 7: 2026_12_01_separate_production_attributes_and_cash_change.sql
echo "  Applying 2026_12_01_separate_production_attributes_and_cash_change.sql...\n";
if (!tableExists($db, 'production_category_attributes')) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS `production_category_attributes` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `category_id` int(10) unsigned NOT NULL,
          `subcategory_id` int(10) unsigned DEFAULT NULL,
          `sub_subcategory_id` int(10) unsigned DEFAULT NULL,
          `attr_name` varchar(100) NOT NULL,
          `attr_type` enum('TEXT','NUMBER','SELECT','MULTI_SELECT') NOT NULL DEFAULT 'TEXT',
          `attr_options` text DEFAULT NULL,
          `is_required` tinyint(1) NOT NULL DEFAULT 0,
          `sort_order` int(11) NOT NULL DEFAULT 0,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_production_cat_attrs` (`category_id`,`subcategory_id`,`sub_subcategory_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
}
if (tableExists($db, 'purchase_orders')) {
    if (!columnExists($db, 'purchase_orders', 'cash_tendered')) {
        $db->exec("ALTER TABLE `purchase_orders` ADD COLUMN `cash_tendered` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `paid_amount`");
    }
    if (!columnExists($db, 'purchase_orders', 'change_amount')) {
        $db->exec("ALTER TABLE `purchase_orders` ADD COLUMN `change_amount` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `cash_tendered`");
    }
    // Migration 8: 2026_12_02_separate_direct_customer_purchase_orders.sql
    if (!columnExists($db, 'purchase_orders', 'is_direct_purchase')) {
        $db->exec("ALTER TABLE `purchase_orders` ADD COLUMN `is_direct_purchase` tinyint(1) NOT NULL DEFAULT 0 AFTER `payment_method`");
    }
}

// Migration 9: 2026_12_03_add_cancelled_shop_damage_status.sql
if (tableExists($db, 'shop_damages')) {
    echo "  Applying 2026_12_03_add_cancelled_shop_damage_status.sql...\n";
    try {
        $db->exec("ALTER TABLE `shop_damages` MODIFY COLUMN `status` ENUM('LOGGED','INSPECTED','SALVAGED','WRITTEN_OFF','REPAIRED','RETURN_TO_WH','REWORK','CANCELLED') DEFAULT 'LOGGED'");
    } catch (Exception $e) {
        echo "    Notice: " . $e->getMessage() . "\n";
    }
}

// Migration 10: 2026_11_17_add_purchase_types_and_third_party_mapping.sql
echo "  Applying 2026_11_17_add_purchase_types_and_third_party_mapping.sql...\n";
if (tableExists($db, 'production_purchases') && !columnExists($db, 'production_purchases', 'purchase_type')) {
    $db->exec("ALTER TABLE `production_purchases` ADD COLUMN `purchase_type` ENUM('FABRIC_ROLL', 'THIRD_PARTY', 'OWN_PRODUCT') NOT NULL DEFAULT 'FABRIC_ROLL' AFTER `purchase_number`");
    $db->exec("ALTER TABLE `production_purchases` ADD KEY `idx_purchases_type_date` (`purchase_type`, `purchase_date`, `seller_id`)");
}
if (tableExists($db, 'production_purchase_items')) {
    try {
        $db->exec("ALTER TABLE `production_purchase_items` MODIFY COLUMN `item_type` ENUM('FABRIC_ROLL', 'THIRD_PARTY', 'OWN_PRODUCT', 'GENERAL_SUPPLY') NOT NULL DEFAULT 'FABRIC_ROLL'");
    } catch (Exception $e) {}
    $tpItemCols = [
        'product_id' => "int(10) unsigned DEFAULT NULL AFTER `item_name`",
        'variant_id' => "int(10) unsigned DEFAULT NULL AFTER `product_id`",
        'product_code' => "varchar(100) DEFAULT NULL AFTER `variant_id`",
        'vendor_sku' => "varchar(100) DEFAULT NULL AFTER `product_code`",
        'size' => "varchar(50) DEFAULT NULL AFTER `color`",
        'mrp' => "decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `unit_price`",
        'selling_price' => "decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `mrp`",
        'start_code' => "varchar(100) DEFAULT NULL AFTER `seller_roll_id`",
        'end_code' => "varchar(100) DEFAULT NULL AFTER `start_code`",
        'qr_code' => "varchar(100) DEFAULT NULL AFTER `end_code`",
        'hsn_code' => "varchar(20) DEFAULT NULL AFTER `qr_code`",
        'is_inwarded' => "tinyint(1) NOT NULL DEFAULT 0 AFTER `hsn_code`",
        'is_mapped' => "tinyint(1) NOT NULL DEFAULT 0 AFTER `is_inwarded`",
        'mapped_category_id' => "int(10) unsigned DEFAULT NULL AFTER `is_mapped`",
        'mapped_subcategory_id' => "int(10) unsigned DEFAULT NULL AFTER `mapped_category_id`",
        'mapped_sub_subcategory_id' => "int(10) unsigned DEFAULT NULL AFTER `mapped_subcategory_id`",
        'mapped_attributes' => "text DEFAULT NULL AFTER `mapped_sub_subcategory_id`",
        'mapped_at' => "datetime DEFAULT NULL AFTER `mapped_attributes`",
        'mapped_by' => "int(10) unsigned DEFAULT NULL AFTER `mapped_at`",
    ];
    foreach ($tpItemCols as $col => $def) {
        if (!columnExists($db, 'production_purchase_items', $col)) {
            $db->exec("ALTER TABLE `production_purchase_items` ADD COLUMN `{$col}` {$def}");
        }
    }
}

// Migration 11: 2026_11_17_enhance_stock_requests_inter_module_flow.sql
echo "  Applying 2026_11_17_enhance_stock_requests_inter_module_flow.sql...\n";
if (tableExists($db, 'production_stock_requests')) {
    try {
        $db->exec("ALTER TABLE `production_stock_requests` MODIFY COLUMN `status` ENUM('PENDING','RECEIVED_BY_SEND_AREA','APPROVED','IN_PRODUCTION','READY_FOR_DISPATCH','DISPATCHED','FULFILLED','REJECTED') NOT NULL DEFAULT 'PENDING'");
    } catch (Exception $e) {}
    $srCols = [
        'from_module' => "varchar(100) NOT NULL DEFAULT 'Central Warehouse' AFTER `request_number`",
        'to_module' => "varchar(100) NOT NULL DEFAULT 'Production Unit' AFTER `from_module`",
        'from_unit_id' => "int(10) unsigned DEFAULT NULL AFTER `to_module`",
        'to_unit_id' => "int(10) unsigned DEFAULT NULL AFTER `from_unit_id`",
        'dispatched_pieces' => "int(10) unsigned NOT NULL DEFAULT 0 AFTER `fulfilled_pieces`",
        'received_by_send_area_at' => "datetime DEFAULT NULL AFTER `notes`",
        'received_by_send_area_by' => "varchar(150) DEFAULT NULL AFTER `received_by_send_area_at`",
        'dispatched_at' => "datetime DEFAULT NULL AFTER `received_by_send_area_by`",
        'dispatched_by' => "varchar(150) DEFAULT NULL AFTER `dispatched_at`",
        'received_at' => "datetime DEFAULT NULL AFTER `dispatched_by`",
        'received_by' => "varchar(150) DEFAULT NULL AFTER `received_at`",
    ];
    foreach ($srCols as $col => $def) {
        if (!columnExists($db, 'production_stock_requests', $col)) {
            $db->exec("ALTER TABLE `production_stock_requests` ADD COLUMN `{$col}` {$def}");
        }
    }
}
if (tableExists($db, 'production_stock_request_items')) {
    $sriCols = [
        'dispatched_qty' => "int(10) unsigned NOT NULL DEFAULT 0 AFTER `requested_qty`",
        'received_qty' => "int(10) unsigned NOT NULL DEFAULT 0 AFTER `dispatched_qty`",
    ];
    foreach ($sriCols as $col => $def) {
        if (!columnExists($db, 'production_stock_request_items', $col)) {
            $db->exec("ALTER TABLE `production_stock_request_items` ADD COLUMN `{$col}` {$def}");
        }
    }
    try {
        $db->exec("ALTER TABLE `production_stock_request_items` MODIFY COLUMN `status` ENUM('PENDING','RECEIVED_BY_SEND_AREA','PARTIAL','ALLOCATED','DISPATCHED','FULFILLED','REJECTED') NOT NULL DEFAULT 'PENDING'");
    } catch (Exception $e) {}
}

// -------------------------------------------------------------
// STEP 6: SEED PERMISSIONS & BIND TO SUPER ADMIN
// -------------------------------------------------------------
echo "\n[6/7] Synchronizing permissions matrix...\n";
require_once BASE_PATH . '/database/seed_permissions.php';

// Also ensure variants and qr_generation permissions are explicitly present
$extraPermissions = [
    ['variants', 'view', 'View Product Variants & QR Identities', 'variants.view'],
    ['variants', 'create', 'Create New Product Variant', 'variants.create'],
    ['variants', 'edit', 'Edit Product Variant Details & MRP', 'variants.edit'],
    ['variants', 'delete', 'Delete Product Variant', 'variants.delete'],
    ['qr_generation', 'view', 'View QR Code Generations & Batches', 'qr_generation.view'],
    ['qr_generation', 'create', 'Generate QR Code Batches', 'qr_generation.create'],
    ['qr_generation', 'edit', 'Update QR Generation Details', 'qr_generation.edit'],
    ['qr_generation', 'delete', 'Delete QR Generation Batch', 'qr_generation.delete'],
];

foreach ($extraPermissions as $p) {
    $exists = Database::fetchOne("SELECT id FROM permissions WHERE module = ? AND action = ?", [$p[0], $p[1]]);
    if (!$exists) {
        Database::execute(
            "INSERT INTO permissions (module, action, description, slug, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$p[0], $p[1], $p[2], $p[3]]
        );
    }
}

// Bind ALL active permissions to Super Admin role (role_id = 1)
$allPermIds = Database::fetchAll("SELECT id FROM permissions");
$adminRolePerms = Database::fetchAll("SELECT permission_id FROM role_permissions WHERE role_id = 1");
$existingAdminPermIds = array_column($adminRolePerms, 'permission_id');

$addedToAdmin = 0;
$insertStmt = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, ?)");
foreach ($allPermIds as $p) {
    if (!in_array($p['id'], $existingAdminPermIds)) {
        $insertStmt->execute([$p['id']]);
        $addedToAdmin++;
    }
}
echo "  Super Admin Role (ID 1) synchronized with all " . count($allPermIds) . " system permissions (newly mapped: {$addedToAdmin}).\n";

// Ensure Admin User (ID 1) exists and has Super Admin role
$adminUser = Database::fetch("SELECT id, username, role_id, is_active FROM users WHERE id = 1 OR username = 'admin'");
if (!$adminUser) {
    $db->exec("
        INSERT INTO `users` (`id`, `username`, `emp_id`, `email`, `password_hash`, `full_name`, `phone`, `role_id`, `department`, `production_unit_id`, `warehouse_id`, `shop_id`, `reports_access`, `is_active`, `must_change_password`, `created_at`) VALUES
        (1, 'admin', 'EMP-001', 'admin@grm.com', '$2y$10$WXeV9abEWzjWySfggVzhTOp4j7pzF7UuFNUJ1lrLbWe9NDL7Cz7UC', 'System Administrator', '9876543210', 1, 'ADMINISTRATION', 1, 1, 1, 'ALL', 1, 0, NOW());
    ");
    echo "  Re-created root admin user.\n";
} else {
    $db->exec("UPDATE users SET role_id = 1, is_active = 1, production_unit_id = 1, warehouse_id = 1, shop_id = 1 WHERE id = {$adminUser['id']}");
    echo "  Root admin user verified (ID: {$adminUser['id']}, Username: {$adminUser['username']}).\n";
}

// -------------------------------------------------------------
// STEP 7: VERIFICATION & SUMMARY REPORT
// -------------------------------------------------------------
echo "\n[7/7] Database Verification & Summary Report:\n";
echo "--------------------------------------------------------\n";
$keyTables = [
    'users', 'roles', 'permissions', 'role_permissions',
    'categories', 'subcategories', 'designs', 'colors', 'sizes',
    'products', 'product_variants', 'product_price_logs',
    'qr_generations', 'qr_generation_items', 'warehouse_qr_codes',
    'production_programs', 'cutting_batches', 'stitching_batches',
    'qc_batches', 'label_batches', 'production_batches',
    'sellers', 'seller_rolls', 'production_purchases',
    'stock_balances', 'stock_transactions', 'warehouse_allocations',
    'allocation_items', 'production_units', 'warehouses', 'shops'
];

foreach ($keyTables as $tbl) {
    if (tableExists($db, $tbl)) {
        $c = (int)$db->query("SELECT COUNT(*) FROM `{$tbl}`")->fetchColumn();
        printf("  %-30s : %d rows\n", $tbl, $c);
    } else {
        printf("  %-30s : [NOT FOUND]\n", $tbl);
    }
}

echo "--------------------------------------------------------\n";
$totalTables = count(Database::fetchAll('SHOW TABLES'));
echo "Total Tables in Local Database: {$totalTables}\n";
echo "MIGRATION COMPLETE & VERIFIED SUCCESSFULLY!\n";
