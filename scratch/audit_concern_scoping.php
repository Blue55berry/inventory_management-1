<?php
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/app.php';

$tables = [
    'cutting_batches',
    'production_batches',
    'production_bills',
    'production_expenses',
    'production_outwards',
    'production_programs',
    'production_purchases',
    'production_stock_requests',
    'purchase_inwards',
    'qc_batches',
    'qr_generations',
    'roll_damages',
    'stitching_batches',
    'stock_adjustments',
    'stock_balances',
    'warehouses',
];

echo "=== CHECKING CONCERN SCOPING IN TABLES ===\n";
require BASE_PATH . '/app/Helpers/Database.php';
foreach ($tables as $t) {
    $hasCol = Database::fetchOne("SHOW COLUMNS FROM `{$t}` LIKE 'concern_id'");
    $hasIndex = Database::fetchOne("SHOW INDEX FROM `{$t}` WHERE Column_name = 'concern_id'");
    echo "  - Table {$t}: concern_id column " . ($hasCol ? "EXISTS" : "MISSING") . " | Index " . ($hasIndex ? "EXISTS" : "MISSING") . "\n";
}
