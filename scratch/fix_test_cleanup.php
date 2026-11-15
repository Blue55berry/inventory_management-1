<?php
$filePath = 'C:/xampp/htdocs/test/Inventory_Software/scratch_test_scenarios.php';
$content = file_get_contents($filePath);
$content = str_replace(
    'Database::execute("DELETE FROM product_variants WHERE product_id IN (?, ?)", [$prodKidsId, $prodWomId]);',
    'Database::execute("DELETE FROM stock_transactions WHERE variant_id IN (SELECT id FROM product_variants WHERE product_id IN (?, ?))", [$prodKidsId, $prodWomId]);' . "\n" .
    'Database::execute("DELETE FROM stock_balances WHERE variant_id IN (SELECT id FROM product_variants WHERE product_id IN (?, ?))", [$prodKidsId, $prodWomId]);' . "\n" .
    'Database::execute("DELETE FROM product_variants WHERE product_id IN (?, ?)", [$prodKidsId, $prodWomId]);',
    $content
);
file_put_contents($filePath, $content);
