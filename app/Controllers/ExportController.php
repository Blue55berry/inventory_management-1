<?php
/**
 * Export Controller — Handles Dedicated Data Export Center, Multi-Module CSV Streaming, and Printable Reports.
 */

require_once BASE_PATH . '/app/Services/ExportService.php';
require_once BASE_PATH . '/app/Services/SettingsService.php';
require_once BASE_PATH . '/app/Models/ProductionUnit.php';

class ExportController {

    /**
     * Dedicated Data Export Hub Page
     */
    public function index(): void {
        $activeFacility = class_exists('ProductionUnit') ? ProductionUnit::getActiveFacility() : null;

        // Current period defaults
        $curMonth = (int)date('n');
        $curYear  = (int)date('Y');
        $firstDayOfMonth = date('Y-m-01');
        $today = date('Y-m-d');

        // Available Export Modules Metadata
        $modules = [
            // PROCUREMENT & MATERIALS
            'purchases' => [
                'name' => 'Purchase Orders & Invoices',
                'desc' => 'Procurement orders across fabric rolls, raw materials, third-party goods, supplier invoices & valuations',
                'icon' => 'bi bi-bag-check',
                'color' => 'primary',
                'category' => 'Procurement & Materials'
            ],
            'rolls' => [
                'name' => 'Fabric Rolls & Inwards',
                'desc' => 'Individual fabric roll serials, barcodes, colors, meters, kg & per-kg valuation',
                'icon' => 'bi bi-stack',
                'color' => 'primary',
                'category' => 'Procurement & Materials'
            ],
            'raw_materials' => [
                'name' => 'Raw Materials Supply & Stock',
                'desc' => 'Procured yarn counts, sewing threads, trims, accessories, pricing & tax breakdowns',
                'icon' => 'bi bi-boxes',
                'color' => 'warning',
                'category' => 'Procurement & Materials'
            ],
            'third_party_products' => [
                'name' => 'Third-Party Products & Mapping',
                'desc' => 'Ready-to-sell vendor SKUs, retail catalog mapping, 3-tier taxonomy, sizes, colors, MRP & SP',
                'icon' => 'bi bi-shop',
                'color' => 'info',
                'category' => 'Procurement & Materials'
            ],
            'suppliers' => [
                'name' => 'Suppliers & Mills Directory',
                'desc' => 'Registered vendors, mill profiles, contact details, GSTIN, payment terms & procurement volumes',
                'icon' => 'bi bi-person-lines-fill',
                'color' => 'secondary',
                'category' => 'Procurement & Materials'
            ],
            'goods_inward' => [
                'name' => 'Goods Inward Notes (GRN)',
                'desc' => 'Warehouse & factory receipts, challan verification, received, accepted & rejected quantities',
                'icon' => 'bi bi-box-arrow-in-down',
                'color' => 'success',
                'category' => 'Procurement & Materials'
            ],

            // MANUFACTURING
            'programs' => [
                'name' => 'Production Programs',
                'desc' => 'Manufacturing targets, product styles, scheduled dates & planned volumes',
                'icon' => 'bi bi-kanban',
                'color' => 'info',
                'category' => 'Manufacturing'
            ],
            'cuttings' => [
                'name' => 'Cutting Batches',
                'desc' => 'Cutting master logs, fabric consumption, layer yields & wastage kg',
                'icon' => 'bi bi-scissors',
                'color' => 'secondary',
                'category' => 'Manufacturing'
            ],
            'stitching' => [
                'name' => 'Stitching Operations',
                'desc' => 'Tailor & operator completions, stitched outputs, reworks & rejections',
                'icon' => 'bi bi-bezier',
                'color' => 'warning',
                'category' => 'Manufacturing'
            ],
            'qc' => [
                'name' => 'Quality Control (QC)',
                'desc' => 'Inspection audits, pass percentage yield, defects & rework logging',
                'icon' => 'bi bi-patch-check',
                'color' => 'success',
                'category' => 'Manufacturing'
            ],
            'damages' => [
                'name' => 'Damage & Rejection Management',
                'desc' => 'Roll damages, defect logs, loss amounts, resolutions, debit notes & replacement rolls',
                'icon' => 'bi bi-exclamation-octagon',
                'color' => 'danger',
                'category' => 'Manufacturing'
            ],
            'catalog' => [
                'name' => 'Product Catalog & Style Master',
                'desc' => 'Master finished garment styles, category hierarchy, HSN codes, default specs & active variant counts',
                'icon' => 'bi bi-grid-3x3-gap',
                'color' => 'primary',
                'category' => 'Manufacturing'
            ],
            'qr_generations' => [
                'name' => 'QR Code Generation Batches',
                'desc' => 'Thermal QR/barcode generation batches, brand names, variant codes & printed serial sequences',
                'icon' => 'bi bi-qr-code-scan',
                'color' => 'dark',
                'category' => 'Manufacturing'
            ],

            // LOGISTICS & WAREHOUSING
            'batches' => [
                'name' => 'Packing Batches (Cartons)',
                'desc' => 'Master cartons, packaging batches, piece counts & production costing',
                'icon' => 'bi bi-box-seam',
                'color' => 'success',
                'category' => 'Logistics & Warehousing'
            ],
            'outward' => [
                'name' => 'Outward Dispatches',
                'desc' => 'Warehouse transfers, gate passes, transporter details, vehicle & LR logs',
                'icon' => 'bi bi-truck',
                'color' => 'primary',
                'category' => 'Logistics & Warehousing'
            ],
            'inventory' => [
                'name' => 'Finished Goods Inventory (On-Hand)',
                'desc' => 'Real-time available stock levels by SKU, style, color, size, location & stock states',
                'icon' => 'bi bi-layers',
                'color' => 'info',
                'category' => 'Logistics & Warehousing'
            ],
            'stock_ledger' => [
                'name' => 'Detailed Warehouse Stock Ledger',
                'desc' => 'Monthly stock-in/out history with QR, variant, size, color, design, and pricing details',
                'icon' => 'bi bi-arrow-left-right',
                'color' => 'success',
                'category' => 'Logistics & Warehousing'
            ],
            'stock_requests' => [
                'name' => 'Stock Requirements',
                'desc' => 'Warehouse requisition orders, priority levels, fulfillment statuses',
                'icon' => 'bi bi-box-arrow-in-down-right',
                'color' => 'info',
                'category' => 'Logistics & Warehousing'
            ],
            'warehouse_rework' => [
                'name' => 'Warehouse Rework & Repairs',
                'desc' => 'Warehouse repair orders, defect types, repair notes, repaired vs scrapped quantities',
                'icon' => 'bi bi-wrench-adjustable',
                'color' => 'warning',
                'category' => 'Logistics & Warehousing'
            ],

            // SALES & FINANCE
            'billing' => [
                'name' => 'Direct Factory Billing (POS)',
                'desc' => 'Tax invoices, customer details, HSN codes, GST amounts & payment types',
                'icon' => 'bi bi-receipt',
                'color' => 'primary',
                'category' => 'Sales & Finance'
            ],
            'returns' => [
                'name' => 'Customer Returns & Credit Notes',
                'desc' => 'Returned merchandise, condition inspection, refund modes & tax reversals',
                'icon' => 'bi bi-arrow-return-left',
                'color' => 'danger',
                'category' => 'Sales & Finance'
            ],
            'wastage' => [
                'name' => 'Fabric & Stitching Scrap Sales',
                'desc' => 'Cutting scrap & stitching fabric waste monetized sales and buyer invoices',
                'icon' => 'bi bi-recycle',
                'color' => 'success',
                'category' => 'Sales & Finance'
            ],
            'expenses' => [
                'name' => 'Operating & Machine Expenses',
                'desc' => 'Plant maintenance, operating costs, category breakdown & settlement status',
                'icon' => 'bi bi-wallet2',
                'color' => 'warning',
                'category' => 'Sales & Finance'
            ],

            // ASSETS & INFRASTRUCTURE
            'assets' => [
                'name' => 'Fixed Assets Register',
                'desc' => 'Plant machinery, asset codes, serial numbers, purchase value, current book value & condition',
                'icon' => 'bi bi-tools',
                'color' => 'primary',
                'category' => 'Assets & Infrastructure'
            ],
            'asset_maintenance' => [
                'name' => 'Asset Maintenance & Service Logs',
                'desc' => 'Servicing logs, downtime hours, technician info, parts replaced & maintenance expenditures',
                'icon' => 'bi bi-gear',
                'color' => 'secondary',
                'category' => 'Assets & Infrastructure'
            ],
        ];

        // Active production facilities
        $facilities = Database::fetchAll("SELECT id, name, code, is_active FROM production_units WHERE is_active = 1 ORDER BY name ASC");
        $warehouses = Database::fetchAll("SELECT id, name, code FROM warehouses WHERE is_active = 1 ORDER BY name ASC");

        Response::view('export.index', [
            'pageTitle'        => 'Data Export Center',
            'activeModule'     => 'export',
            'activeMenu'       => 'export-hub',
            'breadcrumb'       => [
                ['label' => 'Data Export Center']
            ],
            'modules'          => $modules,
            'facilities'       => $facilities,
            'warehouses'       => $warehouses,
            'activeFacility'   => $activeFacility,
            'curMonth'         => $curMonth,
            'curYear'          => $curYear,
            'firstDayOfMonth'  => $firstDayOfMonth,
            'today'            => $today,
        ]);
    }

    /**
     * Process export download (CSV or Printable Summary)
     */
    public function download(): void {
        $periodType = trim((string)($_REQUEST['period_type'] ?? 'MONTH'));
        $format     = strtoupper(trim((string)($_REQUEST['format'] ?? 'CSV')));

        if ($periodType === 'MONTH') {
            $month = (int)($_REQUEST['selected_month'] ?? date('n'));
            $year  = (int)($_REQUEST['selected_year'] ?? date('Y'));
            $month = max(1, min(12, $month));
            $year  = max(2020, min(2035, $year));
            $startDate   = sprintf('%04d-%02d-01', $year, $month);
            $endDate     = date('Y-m-t', strtotime($startDate));
            $periodLabel = date('F Y', strtotime($startDate));
        } else {
            $startDate   = trim((string)($_REQUEST['date_from'] ?? date('Y-m-01')));
            $endDate     = trim((string)($_REQUEST['date_to'] ?? date('Y-m-d')));
            $periodLabel = date('d M Y', strtotime($startDate)) . ' to ' . date('d M Y', strtotime($endDate));
        }

        $allModules = [
            'purchases', 'rolls', 'raw_materials', 'third_party_products', 'suppliers', 'goods_inward',
            'programs', 'cuttings', 'stitching', 'qc', 'damages', 'catalog', 'qr_generations',
            'batches', 'outward', 'inventory', 'stock_ledger', 'stock_requests', 'warehouse_rework',
            'billing', 'returns', 'wastage', 'expenses', 'assets', 'asset_maintenance'
        ];
        $selectedModules = !empty($_REQUEST['modules']) && is_array($_REQUEST['modules'])
            ? array_values(array_intersect($_REQUEST['modules'], $allModules))
            : $allModules;

        if (empty($selectedModules)) {
            $selectedModules = $allModules;
        }

        $activeFacility = class_exists('ProductionUnit') ? ProductionUnit::getActiveFacility() : null;
        $requestedWarehouseIds = $_REQUEST['warehouse_ids'] ?? [];
        $selectedWarehouseIds = is_array($requestedWarehouseIds)
            ? array_values(array_unique(array_filter(array_map('intval', $requestedWarehouseIds), static function (int $id): bool {
                return $id > 0;
            })))
            : [];
        $data = ExportService::getReportData($startDate, $endDate, $selectedModules, null, $selectedWarehouseIds);

        if ($format === 'PRINT') {
            if (file_exists(BASE_PATH . '/views/export/print.php')) {
                Response::viewOnly('export.print', [
                    'periodLabel'     => $periodLabel,
                    'startDate'       => $startDate,
                    'endDate'         => $endDate,
                    'selectedModules' => $selectedModules,
                    'selectedWarehouseIds' => $selectedWarehouseIds,
                    'data'            => $data,
                    'prodUnit'        => $activeFacility,
                    'sellerCompany'   => SettingsService::getGroup('company'),
                ]);
            } else {
                Response::viewOnly('production.reports.print', [
                    'periodLabel'     => $periodLabel,
                    'startDate'       => $startDate,
                    'endDate'         => $endDate,
                    'selectedModules' => $selectedModules,
                    'selectedWarehouseIds' => $selectedWarehouseIds,
                    'data'            => $data,
                    'prodUnit'        => $activeFacility,
                    'sellerCompany'   => SettingsService::getGroup('company'),
                ]);
            }
            return;
        }

        ExportService::streamCsv($periodLabel, $startDate, $endDate, $data, $activeFacility);
    }

    /**
     * Instant 1-click CSV download for current month
     */
    public function quickCsv(): void {
        $_REQUEST['period_type'] = 'MONTH';
        $_REQUEST['selected_month'] = date('n');
        $_REQUEST['selected_year'] = date('Y');
        $_REQUEST['format'] = 'CSV';
        $this->download();
    }
}
