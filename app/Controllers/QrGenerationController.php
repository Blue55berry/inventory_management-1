<?php
/**
 * QrGenerationController
 * Dedicated Controller for Manual and Catalog-linked QR Code Generations.
 */

require_once BASE_PATH . '/app/Services/QrGenerationService.php';
require_once BASE_PATH . '/app/Models/Design.php';

class QrGenerationController {

    /**
     * List all generated QR batches.
     */
    public function index(): void {
        $filters = [
            'search'      => trim($_GET['search'] ?? ''),
            'brand_name'  => trim($_GET['brand_name'] ?? ''),
            'category_id' => !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null,
            'type'        => trim($_GET['type'] ?? ''),
        ];

        $batches = QrGenerationService::listBatches($filters);
        $categories = Database::fetchAll("SELECT id, name, code FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $brands = Database::fetchAll("SELECT DISTINCT brand_name FROM qr_generations WHERE brand_name IS NOT NULL AND brand_name != '' ORDER BY brand_name ASC");

        $totalQrs = (int)Database::fetchOne("SELECT COUNT(*) as c FROM qr_generation_items")['c'];

        Response::view('production.qr_generation.index', [
            'pageTitle'    => 'QR Code Generation | GRM Inventory',
            'activeModule' => 'production',
            'activeMenu'   => 'prod-qr-generation',
            'batches'      => $batches,
            'filters'      => $filters,
            'categories'   => $categories,
            'brands'       => $brands,
            'totalQrs'     => $totalQrs,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'QR Code Generations'],
            ],
        ]);
    }

    /**
     * Show generator interface with Manual and Catalog/Variant modes.
     */
    public function create(): void {
        $categories = Database::fetchAll("SELECT id, name, code FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $subcategories = Database::fetchAll("SELECT id, category_id, name, code FROM subcategories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $subSubcategories = Database::fetchAll("SELECT id, subcategory_id, name, code FROM sub_subcategories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $catalogs = Database::fetchAll("SELECT id, category_id, subcategory_id, name, code, brand_id FROM products WHERE is_active = 1 ORDER BY name ASC");
        $brands = Database::fetchAll("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name ASC");
        $colors = Database::fetchAll("SELECT id, name, hex_code FROM colors WHERE is_active = 1 ORDER BY name ASC");
        $designs = Design::allActive();
        $sizes = Database::fetchAll("SELECT id, name, code FROM sizes WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");

        Response::view('production.qr_generation.create', [
            'pageTitle'        => 'Generate QR Labels | GRM Inventory',
            'activeModule'     => 'production',
            'activeMenu'       => 'prod-qr-generation',
            'categories'       => $categories,
            'subcategories'    => $subcategories,
            'subSubcategories' => $subSubcategories,
            'catalogs'         => $catalogs,
            'brands'           => $brands,
            'colors'           => $colors,
            'designs'          => $designs,
            'sizes'            => $sizes,
            'breadcrumb'       => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'QR Generations', 'url' => url('production/qr-generations')],
                ['label' => 'Generate QR Labels'],
            ],
        ]);
    }

    /**
     * Handle Manual QR Generation.
     */
    public function storeManual(): void {
        try {
            $result = QrGenerationService::generateManualQrs($_POST);

            Session::flash('success', "Batch {$result['batch_code']} generated successfully with {$result['quantity']} QR labels!");
            Response::redirect(url('production/qr-generations/view/' . $result['generation_id']));
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(url('production/qr-generations/create'));
        }
    }

    /**
     * Handle Catalog / Variant QR Generation.
     */
    public function storeCatalog(): void {
        try {
            $result = QrGenerationService::generateCatalogQrs($_POST);

            Session::flash('success', "Catalog QR Batch {$result['batch_code']} generated with {$result['quantity']} QR labels!");
            Response::redirect(url('production/qr-generations/view/' . $result['generation_id']));
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(url('production/qr-generations/create?mode=catalog'));
        }
    }

    /**
     * View batch details and item list.
     */
    public function view(string $id): void {
        $batch = QrGenerationService::getBatch((int)$id);
        if (!$batch) {
            Response::error(404, 'QR Generation batch not found.');
            return;
        }

        Response::view('production.qr_generation.view', [
            'pageTitle'    => 'QR Batch ' . $batch['batch_code'],
            'activeModule' => 'production',
            'activeMenu'   => 'prod-qr-generation',
            'batch'        => $batch,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'QR Generations', 'url' => url('production/qr-generations')],
                ['label' => $batch['batch_code']],
            ],
        ]);
    }

    /**
     * Print thermal QR stickers for this batch.
     * Brand Name given by user is printed prominently at the top.
     */
    public function printLabels(string $id): void {
        $batch = QrGenerationService::getBatch((int)$id);
        if (!$batch) {
            Response::error(404, 'QR Generation batch not found.');
            return;
        }

        Response::viewOnly('production.qr_generation.print', [
            'batch' => $batch,
        ]);
    }

    /**
     * Delete a generation batch.
     */
    public function delete(string $id): void {
        $batchId = (int)$id;
        $batch = Database::fetchOne("SELECT batch_code FROM qr_generations WHERE id = ?", [$batchId]);
        if (!$batch) {
            Response::error(404, 'Batch not found.');
            return;
        }

        Database::execute("DELETE FROM qr_generations WHERE id = ?", [$batchId]);

        Session::flash('success', "QR Batch {$batch['batch_code']} deleted successfully.");
        Response::redirect(url('production/qr-generations'));
    }
}
