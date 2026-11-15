<?php
$filePath = 'C:/xampp/htdocs/test/Inventory_Software/app/Controllers/ProductionBillingController.php';
$content = file_get_contents($filePath);

$search = "                foreach (\$item['tags'] as \$tag) {\r\n                    Database::execute(\r\n                        \"INSERT INTO production_bill_item_tags (bill_id, bill_item_id, qc_item_id, tag_code) VALUES (?, ?, ?, ?)\",\r\n                        [\$billId, \$billItemId, \$stock['qc_item_id'], \$tag]\r\n                    );\r\n                }\r\n            }";

$replace = <<<'EOD'
                foreach ($item['tags'] as $tag) {
                    Database::execute(
                        "INSERT INTO production_bill_item_tags (bill_id, bill_item_id, qc_item_id, tag_code) VALUES (?, ?, ?, ?)",
                        [$billId, $billItemId, $stock['qc_item_id'], $tag]
                    );
                }

                // Inventory integration: Deduct stock from stock_balances using StockService
                $vId = (int)($stock['variant_id'] ?? 0);
                if ($vId > 0 && $productionUnitId > 0) {
                    try {
                        StockService::decrease(
                            $vId,
                            'PRODUCTION_UNIT',
                            $productionUnitId,
                            'AVAILABLE',
                            (float)$item['quantity'],
                            'PRODUCTION_SALE',
                            'production_bills',
                            $billId,
                            (int)(Auth::id() ?? 0) ?: null,
                            "Billed in Production Bill #{$billNumber}"
                        );
                    } catch (Throwable $stkEx) {
                        error_log("StockService::decrease on bill: " . $stkEx->getMessage());
                    }
                }
            }
EOD;

if (strpos($content, $search) !== false) {
    $content = str_replace($search, $replace, $content);
    file_put_contents($filePath, $content);
    echo "StockService decrease added successfully\n";
} else {
    echo "Search block not matched\n";
}
