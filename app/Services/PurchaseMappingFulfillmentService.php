<?php
/**
 * PurchaseMappingFulfillmentService
 *
 * Completes the post-purchase flow for every production purchase item type:
 *   1. Save category / subcategory / sub-subcategory / attributes mapping
 *   2. Sync the mapped taxonomy back to the product + variant catalog
 *   3. Auto-create (or extend) the Goods Receipt Note (GRN)
 *
 * Mapping and GRN creation run inside one database transaction. QR generation is
 * a separate manual step after mapping. Re-saving a mapping is idempotent.
 */

require_once BASE_PATH . '/app/Helpers/Database.php';
require_once BASE_PATH . '/app/Services/VariantService.php';
require_once BASE_PATH . '/app/Services/AuditService.php';
require_once BASE_PATH . '/app/Models/ProductVariant.php';
require_once BASE_PATH . '/app/Models/PurchaseInward.php';

class PurchaseMappingFulfillmentService {

    private static bool $schemaEnsured = false;

    /**
     * Add fulfillment tracking columns without requiring a manual migration first.
     * A matching additive SQL migration is stored in database/migrations/.
     */
    public static function ensureSchema(): void {
        if (self::$schemaEnsured) {
            return;
        }

        Database::ensureColumn('production_purchase_items', 'qr_generation_id', 'INT UNSIGNED DEFAULT NULL AFTER mapped_by');
        Database::ensureColumn('production_purchase_items', 'qr_batch_code', 'VARCHAR(50) DEFAULT NULL AFTER qr_generation_id');
        Database::ensureColumn('production_purchase_items', 'grn_id', 'INT UNSIGNED DEFAULT NULL AFTER qr_batch_code');
        Database::ensureColumn('production_purchase_items', 'grn_number', 'VARCHAR(50) DEFAULT NULL AFTER grn_id');

        Database::ensureColumn('production_purchases', 'grn_id', 'INT UNSIGNED DEFAULT NULL AFTER total_amount');
        Database::ensureColumn('production_purchases', 'grn_number', 'VARCHAR(50) DEFAULT NULL AFTER grn_id');

        Database::ensureColumn('purchase_orders', 'production_purchase_id', 'INT UNSIGNED DEFAULT NULL AFTER id');
        Database::ensureColumn('purchase_inward_items', 'production_purchase_item_id', 'INT UNSIGNED DEFAULT NULL AFTER purchase_inward_id');

        Database::ensureColumn('products', 'attributes', 'TEXT DEFAULT NULL AFTER description');
        Database::ensureColumn('product_variants', 'attributes', 'TEXT DEFAULT NULL AFTER image');

        // Repair legacy rows that were flagged as mapped without any taxonomy.
        Database::execute(
            "UPDATE production_purchase_items
             SET is_mapped = 0
             WHERE is_mapped = 1 AND mapped_category_id IS NULL"
        );

        self::$schemaEnsured = true;
    }

    /**
     * Fetch one purchase item with the context needed by the fulfillment steps.
     */
    public static function getPurchaseItem(int $itemId): ?array {
        return Database::fetchOne(
            "SELECT ppi.*,
                    pp.purchase_number, pp.purchase_date, pp.invoice_number, pp.total_amount,
                    pp.seller_id, pp.grn_id AS purchase_grn_id, pp.grn_number AS purchase_grn_number,
                    s.name AS seller_name, s.company AS seller_company,
                    c.name AS category_name, c.code AS category_code,
                    sc.name AS subcategory_name, sc.code AS subcategory_code,
                    ssc.name AS sub_subcategory_name, ssc.code AS sub_subcategory_code,
                    qg.batch_code AS existing_batch_code
             FROM production_purchase_items ppi
             INNER JOIN production_purchases pp ON pp.id = ppi.purchase_id
             LEFT JOIN sellers s ON s.id = pp.seller_id
             LEFT JOIN categories c ON c.id = ppi.mapped_category_id
             LEFT JOIN subcategories sc ON sc.id = ppi.mapped_subcategory_id
             LEFT JOIN sub_subcategories ssc ON ssc.id = ppi.mapped_sub_subcategory_id
             LEFT JOIN qr_generations qg ON qg.id = ppi.qr_generation_id
             WHERE ppi.id = ?",
            [$itemId]
        );
    }

    /**
     * Main entry point called by ProductionController::saveThirdPartyMapping().
     *
     * @param array $mapping keys: category_id, subcategory_id, sub_subcategory_id,
     *                       attributes (array|string), is_complementary, mrp, selling_price
     */
    public static function fulfill(int $itemId, array $mapping, int $userId): array {
        self::ensureSchema();

        $item = self::getPurchaseItem($itemId);
        if (!$item) {
            throw new InvalidArgumentException('Purchase item not found.');
        }

        $categoryId = (int)($mapping['category_id'] ?? 0);
        $subcategoryId = !empty($mapping['subcategory_id']) ? (int)$mapping['subcategory_id'] : null;
        $subSubcategoryId = !empty($mapping['sub_subcategory_id']) ? (int)$mapping['sub_subcategory_id'] : null;
        $isComplementary = !empty($mapping['is_complementary']) ? 1 : 0;
        $mrpValue = $mapping['mrp'] ?? null;
        $sellingPriceValue = $mapping['selling_price'] ?? null;

        if (!is_numeric($mrpValue) || !is_numeric($sellingPriceValue)) {
            throw new InvalidArgumentException('Enter valid MRP and selling prices.');
        }
        $mrp = (float)$mrpValue;
        $sellingPrice = (float)$sellingPriceValue;
        if (!is_finite($mrp) || !is_finite($sellingPrice) || $mrp <= 0 || $sellingPrice <= 0) {
            throw new InvalidArgumentException('MRP and selling price must be valid positive amounts.');
        }
        if ($sellingPrice > $mrp) {
            throw new InvalidArgumentException('Selling price cannot be greater than MRP.');
        }

        $category = Database::fetchOne("SELECT id, name, code FROM categories WHERE id = ?", [$categoryId]);
        if (!$category) {
            throw new InvalidArgumentException('Selected category does not exist.');
        }
        if ($subcategoryId) {
            $validSub = Database::fetchOne(
                "SELECT id FROM subcategories WHERE id = ? AND category_id = ?",
                [$subcategoryId, $categoryId]
            );
            if (!$validSub) {
                throw new InvalidArgumentException('Selected subcategory does not belong to the chosen category.');
            }
        }
        if ($subSubcategoryId) {
            $validSubSub = Database::fetchOne(
                "SELECT id FROM sub_subcategories WHERE id = ? AND subcategory_id = ?",
                [$subSubcategoryId, $subcategoryId ?: 0]
            );
            if (!$validSubSub) {
                throw new InvalidArgumentException('Selected sub-subcategory does not belong to the chosen subcategory.');
            }
        }

        $rawAttributes = $mapping['attributes'] ?? [];
        $encodedAttributes = null;
        if (!empty($rawAttributes)) {
            $encodedAttributes = is_string($rawAttributes)
                ? $rawAttributes
                : json_encode($rawAttributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        Database::beginTransaction();
        try {
            Database::execute(
                "UPDATE production_purchase_items
                 SET mrp = ?,
                     selling_price = ?,
                     mapped_category_id = ?,
                     mapped_subcategory_id = ?,
                     mapped_sub_subcategory_id = ?,
                     mapped_attributes = ?,
                     is_complementary = ?,
                     is_mapped = 1,
                     mapped_at = NOW(),
                     mapped_by = ?
                 WHERE id = ?",
                [$mrp, $sellingPrice, $categoryId, $subcategoryId, $subSubcategoryId, $encodedAttributes,
                 $isComplementary, $userId ?: null, $itemId]
            );

            $catalog = self::syncCatalog($itemId, $encodedAttributes, $isComplementary);
            $grn = self::createOrAppendGrn($itemId, $catalog, $userId);

            AuditService::log('purchases', 'UPDATE', 'production_purchase_items', $itemId, null, [
                'action'                => 'purchase_item_mapping_fulfillment',
                'item_type'             => $item['item_type'],
                'category_id'           => $categoryId,
                'subcategory_id'        => $subcategoryId,
                'sub_subcategory_id'    => $subSubcategoryId,
                'mrp'                   => $mrp,
                'selling_price'         => $sellingPrice,
                'is_complementary'      => $isComplementary,
                'grn_id'                => $grn['grn_id'],
                'grn_number'            => $grn['grn_number'],
            ]);

            Database::commit();

            return [
                'item'           => self::getPurchaseItem($itemId),
                'catalog'        => $catalog,
                'grn'            => $grn,
                'grn_line_reused'=> !empty($grn['line_reused']),
            ];
        } catch (Throwable $e) {
            // Inner services open their own nested transactions. Roll back twice
            // so a non-Exception Throwable inside one of them cannot leave the
            // outer transaction open.
            Database::rollback();
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Write the mapped hierarchy/attributes to products + product_variants and
     * make sure the purchase line has a variant that the QR/GRN flow can use.
     */
    private static function syncCatalog(int $itemId, ?string $attributes, int $isComplementary): array {
        $item = self::getPurchaseItem($itemId);
        if (!$item) {
            throw new RuntimeException('Purchase item disappeared during mapping.');
        }

        $categoryId = (int)$item['mapped_category_id'];
        $subcategoryId = !empty($item['mapped_subcategory_id']) ? (int)$item['mapped_subcategory_id'] : null;
        $subSubcategoryId = !empty($item['mapped_sub_subcategory_id']) ? (int)$item['mapped_sub_subcategory_id'] : null;

        $hierarchy = Database::fetchOne(
            "SELECT c.name AS category_name, c.code AS category_code,
                    sc.name AS subcategory_name, sc.code AS subcategory_code,
                    ssc.name AS sub_subcategory_name, ssc.code AS sub_subcategory_code
             FROM categories c
             LEFT JOIN subcategories sc ON sc.id = ?
             LEFT JOIN sub_subcategories ssc ON ssc.id = ?
             WHERE c.id = ?",
            [$subcategoryId, $subSubcategoryId, $categoryId]
        ) ?: [];

        $productId = (int)($item['product_id'] ?? 0);
        $itemName = mb_strtoupper(trim((string)$item['item_name'])) ?: ('PURCHASE ITEM ' . $itemId);

        if ($productId <= 0) {
            $baseCode = 'PP-' . substr(strtoupper(preg_replace('/[^A-Z0-9]/', '', $itemName)), 0, 36);
            $baseCode = rtrim($baseCode, '-') ?: ('PP-ITEM-' . $itemId);
            $code = $baseCode;
            $suffix = 1;
            while (Database::fetchOne("SELECT id FROM products WHERE code = ?", [$code])) {
                $code = $baseCode . '-' . $suffix++;
            }
            Database::execute(
                "INSERT INTO products
                    (name, code, category_id, subcategory_id, sub_subcategory_id,
                     category_code, subcategory_code, sub_subcategory_code,
                     source_type, hsn_code, attributes, is_complementary, is_active, seller_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'EXTERNAL_PURCHASE', ?, ?, ?, 1, ?)",
                [
                    $itemName, $code, $categoryId, $subcategoryId, $subSubcategoryId,
                    $hierarchy['category_code'] ?? null,
                    $hierarchy['subcategory_code'] ?? null,
                    $hierarchy['sub_subcategory_code'] ?? null,
                    $item['hsn_code'] ?: null,
                    $attributes,
                    $isComplementary,
                    (int)($item['seller_id'] ?? 0) ?: null,
                ]
            );
            $productId = (int)Database::lastInsertId();
        } else {
            Database::execute(
                "UPDATE products
                 SET category_id = ?, subcategory_id = ?, sub_subcategory_id = ?,
                     category_code = COALESCE(?, category_code),
                     subcategory_code = COALESCE(?, subcategory_code),
                     sub_subcategory_code = COALESCE(?, sub_subcategory_code),
                     attributes = ?, is_complementary = ?,
                     hsn_code = COALESCE(hsn_code, ?)
                 WHERE id = ?",
                [
                    $categoryId, $subcategoryId, $subSubcategoryId,
                    $hierarchy['category_code'] ?? null,
                    $hierarchy['subcategory_code'] ?? null,
                    $hierarchy['sub_subcategory_code'] ?? null,
                    $attributes, $isComplementary, $item['hsn_code'] ?: null, $productId,
                ]
            );
        }

        $variantId = (int)($item['variant_id'] ?? 0);
        if ($variantId <= 0) {
            $variant = VariantService::findOrCreateVariantForInward($productId, [
                'color'         => $item['color'] ?? null,
                'size'          => $item['size'] ?? null,
                'design_name'   => $item['design_name'] ?? null,
                'hsn_code'      => $item['hsn_code'] ?: null,
                'mrp'           => (float)($item['mrp'] ?? 0),
                'selling_price' => (float)($item['selling_price'] ?? 0),
                'unit_price'    => (float)($item['unit_price'] ?? 0),
            ]);
            $variantId = (int)($variant['id'] ?? 0);
            if ($variantId <= 0) {
                throw new RuntimeException('Unable to create a catalog variant for this purchase item.');
            }
        }

        Database::execute(
            "UPDATE product_variants
             SET mrp = ?, selling_price = ?,
                 is_complementary = ?, attributes = COALESCE(?, attributes),
                 hsn_code = COALESCE(hsn_code, ?)
             WHERE id = ?",
            [(float)$item['mrp'], (float)$item['selling_price'],
             $isComplementary, $attributes, $item['hsn_code'] ?: null, $variantId]
        );

        if ($productId !== (int)($item['product_id'] ?? 0) || $variantId !== (int)($item['variant_id'] ?? 0)) {
            Database::execute(
                "UPDATE production_purchase_items SET product_id = ?, variant_id = ? WHERE id = ?",
                [$productId, $variantId, $itemId]
            );
        }

        $variant = Database::fetchOne(
            "SELECT pv.id, pv.sku, pv.variant_code, pv.mrp, pv.selling_price, pv.color_id, pv.design_id, pv.size_id,
                    col.name AS color_name, d.name AS design_name, sz.name AS size_name
             FROM product_variants pv
             LEFT JOIN colors col ON col.id = pv.color_id
             LEFT JOIN designs d ON d.id = pv.design_id
             LEFT JOIN sizes sz ON sz.id = pv.size_id
             WHERE pv.id = ?",
            [$variantId]
        );

        return [
            'product_id'            => $productId,
            'variant_id'            => $variantId,
            'variant_sku'           => $variant['sku'] ?? null,
            'item_name'             => $itemName,
            'quantity'              => (float)$item['quantity'],
            'mrp'                   => (float)($variant['mrp'] ?? $item['mrp'] ?? 0),
            'selling_price'         => (float)($variant['selling_price'] ?? $item['selling_price'] ?? 0),
            'hsn_code'              => $item['hsn_code'] ?: null,
            'purchase_id'           => (int)$item['purchase_id'],
            'purchase_number'       => $item['purchase_number'],
            'purchase_date'         => $item['purchase_date'],
            'invoice_number'        => $item['invoice_number'] ?: null,
            'item_type'             => $item['item_type'],
            'category_name'         => $hierarchy['category_name'] ?? $item['category_name'] ?? null,
            'category_code'         => $hierarchy['category_code'] ?? $item['category_code'] ?? null,
            'subcategory_name'      => $hierarchy['subcategory_name'] ?? $item['subcategory_name'] ?? null,
            'sub_subcategory_name'  => $hierarchy['sub_subcategory_name'] ?? $item['sub_subcategory_name'] ?? null,
            'brand_name'            => trim((string)($item['seller_company'] ?: ($item['seller_name'] ?? ''))) ?: 'GRM',
            'color_name'            => $variant['color_name'] ?? ($item['color'] ?? null),
            'size_name'             => $variant['size_name'] ?? ($item['size'] ?? null),
            'design_name'           => $variant['design_name'] ?? ($item['design_name'] ?? null),
        ];
    }

    /**
     * Create (once per purchase) a GRN with a matching PO, then append one line
     * per mapped purchase item. Stock is posted separately from a generated QR batch.
     */
    private static function createOrAppendGrn(int $itemId, array $catalog, int $userId): array {
        $item = self::getPurchaseItem($itemId);
        $purchaseId = (int)$catalog['purchase_id'];

        $warehouse = Database::fetchOne("SELECT id FROM warehouses WHERE is_active = 1 ORDER BY id ASC LIMIT 1")
            ?: Database::fetchOne("SELECT id FROM warehouses ORDER BY id ASC LIMIT 1");
        if (!$warehouse) {
            throw new RuntimeException('No warehouse is configured for the auto-generated GRN.');
        }
        $warehouseId = (int)$warehouse['id'];

        $grnId = (int)($item['purchase_grn_id'] ?? 0);
        $grnNumber = (string)($item['purchase_grn_number'] ?? '');

        if ($grnId <= 0) {
            $poId = self::ensurePurchaseOrder($purchaseId, $catalog, $warehouseId, $userId);

            $grnNumber = PurchaseInward::generateGrnNumber();
            Database::execute(
                "INSERT INTO purchase_inwards
                    (purchase_order_id, grn_number, warehouse_id, received_date,
                     received_by, verified_by, notes, invoice_number, invoice_date,
                     status, total_received_qty, total_accepted_qty, total_rejected_qty)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, 'STOCKED', 0, 0, 0)",
                [
                    $poId,
                    $grnNumber,
                    $warehouseId,
                    $catalog['purchase_date'] ?: date('Y-m-d'),
                    $userId ?: null,
                    $userId ?: null,
                    "Auto-generated after taxonomy mapping for production purchase {$catalog['purchase_number']}.",
                    $catalog['invoice_number'] ?: null,
                ]
            );
            $grnId = (int)Database::lastInsertId();

            Database::execute(
                "UPDATE production_purchases SET grn_id = ?, grn_number = ? WHERE id = ?",
                [$grnId, $grnNumber, $purchaseId]
            );
        }

        $lineAlreadyExists = Database::fetchOne(
            "SELECT id FROM purchase_inward_items WHERE purchase_inward_id = ? AND production_purchase_item_id = ?",
            [$grnId, $itemId]
        );

        $lineReused = false;
        if (!$lineAlreadyExists) {
            $qty = max(1, (int)round((float)$catalog['quantity']));
            $batchCode = trim((string)($item['qr_batch_code'] ?? $item['existing_batch_code'] ?? ''));
            Database::execute(
                "INSERT INTO purchase_inward_items
                    (purchase_inward_id, production_purchase_item_id, variant_id,
                     ordered_qty, received_qty, damaged_qty, accepted_qty,
                     received_quantity, accepted_quantity, rejected_quantity,
                     mrp, batch_lot_no, notes)
                 VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, 0, ?, ?, ?)",
                [
                    $grnId,
                    $itemId,
                    (int)$catalog['variant_id'],
                    $qty,
                    $qty,
                    $qty,
                    $qty,
                    $qty,
                    (float)$catalog['mrp'],
                    $batchCode !== '' ? $batchCode : null,
                    "Mapped {$catalog['item_type']} purchase item.",
                ]
            );

            Database::execute(
                "UPDATE purchase_inwards
                 SET total_received_qty = total_received_qty + ?,
                     total_accepted_qty = total_accepted_qty + ?
                 WHERE id = ?",
                [$qty, $qty, $grnId]
            );

            // The GRN records receipt/QC data only. Stock is NOT credited and the
            // line is NOT marked inwarded here: that happens exclusively when the
            // admin clicks "Add to Stock" on the generated QR batch.
            Database::execute(
                "UPDATE production_purchase_items
                 SET grn_id = ?, grn_number = ?
                 WHERE id = ?",
                [$grnId, $grnNumber, $itemId]
            );
        } else {
            $lineReused = true;
            Database::execute(
                "UPDATE purchase_inward_items SET mrp = ? WHERE id = ?",
                [(float)$catalog['mrp'], (int)$lineAlreadyExists['id']]
            );
            Database::execute(
                "UPDATE production_purchase_items SET grn_id = ?, grn_number = ? WHERE id = ?",
                [$grnId, $grnNumber, $itemId]
            );
        }

        return [
            'grn_id'       => $grnId,
            'grn_number'   => $grnNumber,
            'line_reused'  => $lineReused,
            'warehouse_id' => $warehouseId,
        ];
    }

    /**
     * Create one matching purchase order per production purchase so the GRN can
     * satisfy the existing purchase_order_id foreign key.
     */
    private static function ensurePurchaseOrder(int $purchaseId, array $catalog, int $warehouseId, int $userId): int {
        $po = Database::fetchOne(
            "SELECT id FROM purchase_orders WHERE production_purchase_id = ? OR po_number = ? ORDER BY id ASC LIMIT 1",
            [$purchaseId, 'PO-' . $catalog['purchase_number']]
        );
        if ($po) {
            return (int)$po['id'];
        }

        $poNumber = 'PO-' . $catalog['purchase_number'];
        $suffix = 1;
        while (Database::fetchOne("SELECT id FROM purchase_orders WHERE po_number = ?", [$poNumber])) {
            $poNumber = 'PO-' . $catalog['purchase_number'] . '-' . $suffix++;
        }

        $purchase = Database::fetchOne(
            "SELECT seller_id, total_amount FROM production_purchases WHERE id = ?",
            [$purchaseId]
        );

        Database::execute(
            "INSERT INTO purchase_orders
                (po_number, production_purchase_id, seller_id, warehouse_id, order_date,
                 total_amount, status, is_direct_purchase, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, 'RECEIVED', 1, ?, ?)",
            [
                $poNumber,
                $purchaseId,
                (int)($purchase['seller_id'] ?? 0) ?: null,
                $warehouseId,
                $catalog['purchase_date'] ?: date('Y-m-d'),
                (float)($purchase['total_amount'] ?? 0),
                "Auto-created from production purchase {$catalog['purchase_number']} for GRN generation.",
                $userId ?: null,
            ]
        );

        return (int)Database::lastInsertId();
    }
}
