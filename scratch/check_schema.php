<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

$db = Database::getInstance();
echo "--- PRODUCTION_BILL_ITEMS ---\n";
$stmt = $db->query('DESCRIBE production_bill_items');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
