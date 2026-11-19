<?php
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/app.php';

$content = file_get_contents(BASE_PATH . '/app/Controllers/ProductionController.php');

$methods = [
    'productionPurchaseList',
    'thirdPartyMappingList',
    'cuttingList',
    'stitchingList',
    'qcList',
    'productionInventory',
    'damageList',
    'programList',
    'inwardList',
    'outwardList',
    'billList',
    'purchasedStockIndex'
];

echo "=== CHECKING CONCERN_ID SCOPING IN PRODUCTIONCONTROLLER METHODS ===\n";
foreach ($methods as $m) {
    // Find the method body
    $pos = strpos($content, "function $m");
    if ($pos === false) {
        echo "  - $m: METHOD NOT FOUND\n";
        continue;
    }
    $chunk = substr($content, $pos, 3000);
    $hasConcern = (strpos($chunk, 'concern_id') !== false || strpos($chunk, 'currentConcernId') !== false);
    echo "  - $m: " . ($hasConcern ? "SCOPED (has concern_id)" : "UNSCOPED / MISSING CONCERN FILTER") . "\n";
}
