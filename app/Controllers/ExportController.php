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
            'purchases' => [
                'name' => 'Fabric Purchases',
                'desc' => 'Purchase orders, invoices, roll counts, weights & supplier procurement costs',
                'icon' => 'bi bi-bag-check',
                'color' => 'primary',
                'category' => 'Procurement & Materials'
            ],
            'rolls' => [
                'name' => 'Fabric Rolls & Inwards',
                'desc' => 'Individual roll serials, barcodes, colors, meters, kg & per-kg valuation',
                'icon' => 'bi bi-stack',
                'color' => 'primary',
                'category' => 'Procurement & Materials'
            ],
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
            'stock_requests' => [
                'name' => 'Stock Requirements',
                'desc' => 'Warehouse requisition orders, priority levels, fulfillment statuses',
                'icon' => 'bi bi-box-arrow-in-down-right',
                'color' => 'info',
                'category' => 'Logistics & Warehousing'
            ],
            'wastage' => [
                'name' => 'Scrap & Wastage Sales',
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
        ];

        // Active production facilities
        $facilities = Database::fetchAll("SELECT id, name, code, is_active FROM production_units WHERE is_active = 1 ORDER BY name ASC");

        Response::view('export.index', [
            'pageTitle'        => 'Data Export Center',
            'activeModule'     => 'export',
            'activeMenu'       => 'export-hub',
            'breadcrumb'       => [
                ['label' => 'Data Export Center']
            ],
            'modules'          => $modules,
            'facilities'       => $facilities,
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

        $allModules = ['purchases', 'rolls', 'programs', 'cuttings', 'stitching', 'qc', 'batches', 'outward', 'billing', 'returns', 'stock_requests', 'wastage', 'expenses'];
        $selectedModules = !empty($_REQUEST['modules']) && is_array($_REQUEST['modules'])
            ? array_values(array_intersect($_REQUEST['modules'], $allModules))
            : $allModules;

        if (empty($selectedModules)) {
            $selectedModules = $allModules;
        }

        $activeFacility = class_exists('ProductionUnit') ? ProductionUnit::getActiveFacility() : null;
        $data = ExportService::getReportData($startDate, $endDate, $selectedModules);

        if ($format === 'PRINT') {
            if (file_exists(BASE_PATH . '/views/export/print.php')) {
                Response::viewOnly('export.print', [
                    'periodLabel'     => $periodLabel,
                    'startDate'       => $startDate,
                    'endDate'         => $endDate,
                    'selectedModules' => $selectedModules,
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
