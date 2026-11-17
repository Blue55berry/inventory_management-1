<?php
/**
 * QrGenerationService
 * Handles automated & manual QR generation with custom brand formatting and batch sticker generation.
 */

if (defined('BASE_PATH') && file_exists(BASE_PATH . '/app/Helpers/Auth.php')) {
    require_once BASE_PATH . '/app/Helpers/Auth.php';
}

class QrGenerationService {

    /**
     * Generate next batch code (e.g. QRG-20261115-0001).
     */
    public static function generateBatchCode(): string {
        $prefix = 'QRG-' . date('Ymd') . '-';
        $max = (int)Database::fetchOne(
            "SELECT COUNT(*) as c FROM qr_generations WHERE batch_code LIKE ?",
            [$prefix . '%']
        )['c'];
        return $prefix . str_pad((string)($max + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Format a clean uppercase code from text (e.g. "Kids Wear" -> "KW" or "KID").
     */
    public static function extractCode(string $text, int $maxLen = 4): string {
        $cleaned = strtoupper(trim(preg_replace('/[^A-Za-z0-9\s]/', '', $text)));
        $words = preg_split('/\s+/', $cleaned);

        if (count($words) >= 2 && $maxLen <= 4) {
            $initials = '';
            foreach ($words as $w) {
                if ($w !== '') $initials .= $w[0];
            }
            if (strlen($initials) >= 2) {
                return substr($initials, 0, $maxLen);
            }
        }

        $alphanumeric = preg_replace('/[^A-Z0-9]/', '', $cleaned);
        return substr($alphanumeric, 0, $maxLen) ?: 'GEN';
    }

    /**
     * Generate Manual QR Batch:
     * Format: [BRAND]-[CATEGORY]-[SUBCATEGORY]-[SUBSUBCATEGORY]-[SEQ]
     */
    public static function generateManualQrs(array $data): array {
        $brandName = mb_strtoupper(trim($data['brand_name'] ?? 'GRM'));
        if (empty($brandName)) {
            $brandName = 'GRM';
        }

        // 1. Resolve or Quick-create Category
        $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $categoryName = '';
        $categoryCode = '';

        if (!empty($data['new_category_name'])) {
            $newCatName = trim($data['new_category_name']);
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $newCatName), '-'));
            $existing = Database::fetchOne("SELECT id, name, code FROM categories WHERE name = ? OR slug = ?", [$newCatName, $slug]);
            if ($existing) {
                $categoryId = (int)$existing['id'];
                $categoryName = $existing['name'];
                $categoryCode = $existing['code'] ?: self::extractCode($newCatName);
            } else {
                $categoryCode = self::extractCode($newCatName, 3);
                Database::execute(
                    "INSERT INTO categories (name, code, slug, is_active) VALUES (?, ?, ?, 1)",
                    [$newCatName, $categoryCode, $slug]
                );
                $categoryId = (int)Database::lastInsertId();
                $categoryName = $newCatName;
            }
        } elseif ($categoryId) {
            $c = Database::fetchOne("SELECT id, name, code FROM categories WHERE id = ?", [$categoryId]);
            if ($c) {
                $categoryName = $c['name'];
                $categoryCode = $c['code'] ?: self::extractCode($c['name'], 3);
            }
        }

        // 2. Resolve or Quick-create Subcategory
        $subcategoryId = !empty($data['subcategory_id']) ? (int)$data['subcategory_id'] : null;
        $subcategoryName = '';
        $subcategoryCode = '';

        if (!empty($data['new_subcategory_name']) && $categoryId) {
            $newSubName = trim($data['new_subcategory_name']);
            $existing = Database::fetchOne("SELECT id, name, code FROM subcategories WHERE category_id = ? AND name = ?", [$categoryId, $newSubName]);
            if ($existing) {
                $subcategoryId = (int)$existing['id'];
                $subcategoryName = $existing['name'];
                $subcategoryCode = $existing['code'] ?: self::extractCode($newSubName);
            } else {
                $subcategoryCode = self::extractCode($newSubName, 3);
                $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $newSubName));
                Database::execute(
                    "INSERT INTO subcategories (category_id, name, code, slug, is_active) VALUES (?, ?, ?, ?, 1)",
                    [$categoryId, $newSubName, $subcategoryCode, $slug]
                );
                $subcategoryId = (int)Database::lastInsertId();
                $subcategoryName = $newSubName;
            }
        } elseif ($subcategoryId) {
            $sc = Database::fetchOne("SELECT id, name, code FROM subcategories WHERE id = ?", [$subcategoryId]);
            if ($sc) {
                $subcategoryName = $sc['name'];
                $subcategoryCode = $sc['code'] ?: self::extractCode($sc['name'], 3);
            }
        }

        // 3. Resolve or Quick-create Sub-Subcategory
        $subSubcategoryId = !empty($data['sub_subcategory_id']) ? (int)$data['sub_subcategory_id'] : null;
        $subSubcategoryName = '';
        $subSubcategoryCode = '';

        if (!empty($data['new_sub_subcategory_name']) && $subcategoryId) {
            $newSscName = trim($data['new_sub_subcategory_name']);
            $existing = Database::fetchOne("SELECT id, name, code FROM sub_subcategories WHERE subcategory_id = ? AND name = ?", [$subcategoryId, $newSscName]);
            if ($existing) {
                $subSubcategoryId = (int)$existing['id'];
                $subSubcategoryName = $existing['name'];
                $subSubcategoryCode = $existing['code'] ?: self::extractCode($newSscName);
            } else {
                $subSubcategoryCode = self::extractCode($newSscName, 3);
                $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $newSscName));
                Database::execute(
                    "INSERT INTO sub_subcategories (subcategory_id, name, code, slug, is_active) VALUES (?, ?, ?, ?, 1)",
                    [$subcategoryId, $newSscName, $subSubcategoryCode, $slug]
                );
                $subSubcategoryId = (int)Database::lastInsertId();
                $subSubcategoryName = $newSscName;
            }
        } elseif ($subSubcategoryId) {
            $ssc = Database::fetchOne("SELECT id, name, code FROM sub_subcategories WHERE id = ?", [$subSubcategoryId]);
            if ($ssc) {
                $subSubcategoryName = $ssc['name'];
                $subSubcategoryCode = $ssc['code'] ?: self::extractCode($ssc['name'], 3);
            }
        }

        // 4. Resolve Brand Code
        $brandCode = self::extractCode($brandName, 4);

        // Build Prefix: [BRAND]-[CATEGORY]-[SUBCATEGORY]-[SUBSUBCATEGORY]
        $prefixParts = [$brandCode];
        if (!empty($categoryCode)) $prefixParts[] = $categoryCode;
        if (!empty($subcategoryCode)) $prefixParts[] = $subcategoryCode;
        if (!empty($subSubcategoryCode)) $prefixParts[] = $subSubcategoryCode;

        $qrPrefix = implode('-', $prefixParts);

        // 5. Quantity & Prices
        $quantity = max(1, min(1000, (int)($data['quantity'] ?? 1)));
        $mrp = max(0.0, (float)($data['mrp'] ?? 0));
        $sellingPrice = max(0.0, (float)($data['selling_price'] ?? 0));
        if ($sellingPrice <= 0) $sellingPrice = $mrp;

        $colorId = !empty($data['color_id']) ? (int)$data['color_id'] : null;
        $colorName = $colorId ? (Database::fetchOne("SELECT name FROM colors WHERE id = ?", [$colorId])['name'] ?? null) : null;

        $designId = !empty($data['design_id']) ? (int)$data['design_id'] : null;
        $designName = $designId ? (Database::fetchOne("SELECT name FROM designs WHERE id = ?", [$designId])['name'] ?? null) : null;

        $sizeId = !empty($data['size_id']) ? (int)$data['size_id'] : null;
        $sizeName = $sizeId ? (Database::fetchOne("SELECT name FROM sizes WHERE id = ?", [$sizeId])['name'] ?? null) : null;

        $batchCode = self::generateBatchCode();

        // 6. Calculate starting sequence number
        $lastSeqRow = Database::fetchOne(
            "SELECT MAX(sequence_num) as max_seq FROM qr_generation_items qri
             JOIN qr_generations qrg ON qrg.id = qri.generation_id
             WHERE qrg.qr_prefix = ?",
            [$qrPrefix]
        );
        $startSeq = (int)($lastSeqRow['max_seq'] ?? 0) + 1;

        Database::beginTransaction();
        try {
            Database::execute(
                "INSERT INTO qr_generations 
                 (batch_code, generation_type, brand_name, category_id, subcategory_id, sub_subcategory_id,
                  category_name, subcategory_name, sub_subcategory_name, color_id, design_id, size_id,
                  color_name, design_name, size_name, mrp, selling_price, quantity, qr_prefix, notes, created_by)
                 VALUES (?, 'MANUAL', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $batchCode,
                    $brandName,
                    $categoryId,
                    $subcategoryId,
                    $subSubcategoryId,
                    $categoryName,
                    $subcategoryName,
                    $subSubcategoryName,
                    $colorId,
                    $designId,
                    $sizeId,
                    $colorName,
                    $designName,
                    $sizeName,
                    $mrp,
                    $sellingPrice,
                    $quantity,
                    $qrPrefix,
                    trim($data['notes'] ?? ''),
                    (session_status() === PHP_SESSION_ACTIVE && class_exists('Auth') && Auth::check()) ? Auth::id() : null
                ]
            );
            $generationId = (int)Database::lastInsertId();

            $generatedCodes = [];
            $insertItemStmt = Database::getInstance()->prepare(
                "INSERT INTO qr_generation_items (generation_id, qr_code, sequence_num, status) VALUES (?, ?, ?, 'ACTIVE')"
            );

            for ($i = 0; $i < $quantity; $i++) {
                $currSeq = $startSeq + $i;
                $seqStr = str_pad((string)$currSeq, 4, '0', STR_PAD_LEFT);
                $qrCode = $qrPrefix . '-' . $seqStr;

                // Handle any unexpected collision
                while (Database::fetchOne("SELECT id FROM qr_generation_items WHERE qr_code = ?", [$qrCode])) {
                    $currSeq++;
                    $seqStr = str_pad((string)$currSeq, 4, '0', STR_PAD_LEFT);
                    $qrCode = $qrPrefix . '-' . $seqStr;
                }

                $insertItemStmt->execute([$generationId, $qrCode, $currSeq]);
                $generatedCodes[] = $qrCode;
            }

            Database::commit();

            return [
                'generation_id'   => $generationId,
                'batch_code'      => $batchCode,
                'brand_name'      => $brandName,
                'qr_prefix'       => $qrPrefix,
                'quantity'        => $quantity,
                'generated_codes' => $generatedCodes,
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Generate QR Batch from existing Catalog / Variant:
     */
    public static function generateCatalogQrs(array $data): array {
        $productId = (int)($data['product_id'] ?? 0);
        $variantId = !empty($data['variant_id']) ? (int)$data['variant_id'] : null;

        $product = Database::fetchOne(
            "SELECT p.*, c.name as category_name, c.code as category_code,
                    sc.name as subcategory_name, sc.code as subcategory_code,
                    ssc.name as sub_subcategory_name, ssc.code as sub_subcategory_code,
                    b.name as brand_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN subcategories sc ON sc.id = p.subcategory_id
             LEFT JOIN sub_subcategories ssc ON ssc.id = p.sub_subcategory_id
             LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.id = ?",
            [$productId]
        );
        if (!$product) {
            throw new InvalidArgumentException("Catalog / Product not found.");
        }

        $brandName = !empty($data['brand_name']) ? mb_strtoupper(trim($data['brand_name'])) : ($product['brand_name'] ?: 'GRM');
        $brandCode = self::extractCode($brandName, 4);

        $variant = null;
        if ($variantId) {
            $variant = Database::fetchOne(
                "SELECT pv.*, col.name as color_name, d.name as design_name, sz.name as size_name
                 FROM product_variants pv
                 LEFT JOIN colors col ON col.id = pv.color_id
                 LEFT JOIN designs d ON d.id = pv.design_id
                 LEFT JOIN sizes sz ON sz.id = pv.size_id
                 WHERE pv.id = ?",
                [$variantId]
            );
        }

        // Form Prefix
        $catCode = $product['category_code'] ?: self::extractCode($product['category_name'] ?? '', 3);
        $subcatCode = $product['subcategory_code'] ?: self::extractCode($product['subcategory_name'] ?? '', 3);
        $subSubcatCode = $product['sub_subcategory_code'] ?: self::extractCode($product['sub_subcategory_name'] ?? '', 3);

        $prefixParts = [$brandCode];
        if ($catCode) $prefixParts[] = $catCode;
        if ($subcatCode) $prefixParts[] = $subcatCode;
        if ($subSubcatCode) $prefixParts[] = $subSubcatCode;

        if ($variant && !empty($variant['sku'])) {
            $qrPrefix = $brandCode . '-' . $variant['sku'];
        } else {
            $qrPrefix = implode('-', $prefixParts) . '-' . $product['code'];
        }

        $quantity = max(1, min(1000, (int)($data['quantity'] ?? 1)));
        $mrp = $variant ? (float)$variant['mrp'] : 0.00;
        $sellingPrice = $variant ? (float)$variant['selling_price'] : 0.00;

        $batchCode = self::generateBatchCode();

        $lastSeqRow = Database::fetchOne(
            "SELECT MAX(sequence_num) as max_seq FROM qr_generation_items qri
             JOIN qr_generations qrg ON qrg.id = qri.generation_id
             WHERE qrg.qr_prefix = ?",
            [$qrPrefix]
        );
        $startSeq = (int)($lastSeqRow['max_seq'] ?? 0) + 1;

        Database::beginTransaction();
        try {
            Database::execute(
                "INSERT INTO qr_generations 
                 (batch_code, generation_type, brand_name, category_id, subcategory_id, sub_subcategory_id,
                  category_name, subcategory_name, sub_subcategory_name, product_id, variant_id,
                  color_id, design_id, size_id, color_name, design_name, size_name, mrp, selling_price, quantity, qr_prefix, notes, created_by)
                 VALUES (?, 'CATALOG_VARIANT', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $batchCode,
                    $brandName,
                    $product['category_id'],
                    $product['subcategory_id'],
                    $product['sub_subcategory_id'],
                    $product['category_name'],
                    $product['subcategory_name'],
                    $product['sub_subcategory_name'],
                    $productId,
                    $variantId,
                    $variant['color_id'] ?? null,
                    $variant['design_id'] ?? null,
                    $variant['size_id'] ?? null,
                    $variant['color_name'] ?? null,
                    $variant['design_name'] ?? null,
                    $variant['size_name'] ?? null,
                    $mrp,
                    $sellingPrice,
                    $quantity,
                    $qrPrefix,
                    trim($data['notes'] ?? ''),
                    (session_status() === PHP_SESSION_ACTIVE && class_exists('Auth') && Auth::check()) ? Auth::id() : null
                ]
            );
            $generationId = (int)Database::lastInsertId();

            $generatedCodes = [];
            $insertItemStmt = Database::getInstance()->prepare(
                "INSERT INTO qr_generation_items (generation_id, qr_code, sequence_num, status) VALUES (?, ?, ?, 'ACTIVE')"
            );

            for ($i = 0; $i < $quantity; $i++) {
                $currSeq = $startSeq + $i;
                $seqStr = str_pad((string)$currSeq, 4, '0', STR_PAD_LEFT);
                $qrCode = $qrPrefix . '-' . $seqStr;

                while (Database::fetchOne("SELECT id FROM qr_generation_items WHERE qr_code = ?", [$qrCode])) {
                    $currSeq++;
                    $seqStr = str_pad((string)$currSeq, 4, '0', STR_PAD_LEFT);
                    $qrCode = $qrPrefix . '-' . $seqStr;
                }

                $insertItemStmt->execute([$generationId, $qrCode, $currSeq]);
                $generatedCodes[] = $qrCode;
            }

            Database::commit();

            return [
                'generation_id'   => $generationId,
                'batch_code'      => $batchCode,
                'brand_name'      => $brandName,
                'qr_prefix'       => $qrPrefix,
                'quantity'        => $quantity,
                'generated_codes' => $generatedCodes,
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Get a generation batch with items.
     */
    public static function getBatch(int $id): ?array {
        $gen = Database::fetchOne(
            "SELECT qrg.*, pv.variant_code, pv.sku as variant_sku, p.name as product_name, p.code as product_code
             FROM qr_generations qrg
             LEFT JOIN product_variants pv ON pv.id = qrg.variant_id
             LEFT JOIN products p ON p.id = qrg.product_id
             WHERE qrg.id = ?",
            [$id]
        );
        if (!$gen) return null;

        $items = Database::fetchAll("SELECT * FROM qr_generation_items WHERE generation_id = ? ORDER BY sequence_num ASC", [$id]);
        return array_merge($gen, ['items' => $items]);
    }

    /**
     * List generation batches with filtering.
     */
    public static function listBatches(array $filters = []): array {
        $sql = "SELECT qrg.*,
                       u.full_name as created_by_name,
                       COUNT(qri.id) as generated_count
                FROM qr_generations qrg
                LEFT JOIN users u ON u.id = qrg.created_by
                LEFT JOIN qr_generation_items qri ON qri.generation_id = qrg.id
                WHERE 1 = 1";
        $params = [];

        if (!empty($filters['search'])) {
            $s = '%' . trim($filters['search']) . '%';
            $sql .= " AND (qrg.batch_code LIKE ? OR qrg.brand_name LIKE ? OR qrg.qr_prefix LIKE ? OR qrg.category_name LIKE ?)";
            $params = array_merge($params, [$s, $s, $s, $s]);
        }

        if (!empty($filters['brand_name'])) {
            $sql .= " AND qrg.brand_name = ?";
            $params[] = trim($filters['brand_name']);
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND qrg.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND qrg.generation_type = ?";
            $params[] = trim($filters['type']);
        }

        $sql .= " GROUP BY qrg.id ORDER BY qrg.id DESC";

        return Database::fetchAll($sql, $params);
    }
}
