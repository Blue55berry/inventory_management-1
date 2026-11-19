<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1 d-flex align-items-center gap-2">
            <span class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 d-inline-flex align-items-center justify-content-center">
                <i class="bi bi-file-earmark-arrow-down-fill fs-4"></i>
            </span>
            <span>Data Export Center</span>
        </h2>
        <span class="text-muted">Export comprehensive production pipelines, supplier procurement, financial P&amp;L, and warehouse logistics.</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('export/quick-csv') ?>" class="btn btn-outline-primary btn-sm fw-semibold shadow-sm px-3 d-flex align-items-center gap-1.5" title="Instantly download full CSV for current month">
            <i class="bi bi-file-earmark-excel"></i>
            <span>Current Month CSV</span>
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold shadow-sm px-3 d-flex align-items-center gap-1.5" onclick="submitQuickPrint()">
            <i class="bi bi-printer"></i>
            <span>Print Current Month</span>
        </button>
    </div>
</div>

<!-- Quick Stats & Facility Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary-subtle text-primary p-3">
                    <i class="bi bi-buildings fs-4"></i>
                </div>
                <div>
                    <div class="small text-muted fw-semibold">Active Production Facility</div>
                    <div class="fw-bold fs-6 text-dark"><?= e($activeFacility['name'] ?? 'All Production Facilities') ?></div>
                    <small class="text-muted"><?= !empty($activeFacility['code']) ? 'Unit Code: ' . e($activeFacility['code']) : 'Central Enterprise' ?></small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success-subtle text-success p-3">
                    <i class="bi bi-file-earmark-spreadsheet fs-4"></i>
                </div>
                <div>
                    <div class="small text-muted fw-semibold">Export Format Support</div>
                    <div class="fw-bold fs-6 text-dark">Excel CSV &amp; Printable Summary</div>
                    <small class="text-muted">Native decimal formatting with UTF-8 BOM</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info-subtle text-info p-3">
                    <i class="bi bi-lightning-charge fs-4"></i>
                </div>
                <div>
                    <div class="small text-muted fw-semibold">Query Engine Optimization</div>
                    <div class="fw-bold fs-6 text-dark">High-Speed SARGable Range Scan</div>
                    <small class="text-muted">Indexed date predicates &amp; pre-joined aggregations</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Export Configuration Form -->
<form method="GET" action="<?= url('export/download') ?>" target="_blank" id="exportMainForm">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-sliders text-primary"></i>
                    <span>Configure Export Dataset</span>
                </h5>
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1.5 rounded-pill">
                    <?= count($modules) ?> Data Modules Available
                </span>
            </div>
        </div>

        <div class="card-body p-4">
            <!-- 1. Report Period Selection -->
            <div class="mb-4 pb-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="form-label fw-bold text-dark mb-0 fs-6">
                        <span class="badge bg-primary text-white rounded-circle me-1" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">1</span>
                        Select Export Date Period
                    </label>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="period_type" id="periodTypeMonth" value="MONTH" checked onchange="togglePeriodInputs()">
                        <label class="btn btn-outline-primary fw-semibold" for="periodTypeMonth">Month-Wise</label>

                        <input type="radio" class="btn-check" name="period_type" id="periodTypeCustom" value="CUSTOM" onchange="togglePeriodInputs()">
                        <label class="btn btn-outline-primary fw-semibold" for="periodTypeCustom">Custom Date Range</label>
                    </div>
                </div>

                <!-- Month-wise Picker -->
                <div class="row g-3" id="monthPickerRow">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1 fw-semibold">Select Month</label>
                        <select name="selected_month" class="form-select fw-semibold">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= ($curMonth === $m) ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1 fw-semibold">Select Year</label>
                        <select name="selected_year" class="form-select fw-semibold">
                            <?php for ($y = $curYear - 3; $y <= $curYear + 2; $y++): ?>
                                <option value="<?= $y ?>" <?= ($y === $curYear) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <!-- Custom Date Picker -->
                <div class="row g-3 d-none" id="customPickerRow">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1 fw-semibold">From Date (Start)</label>
                        <input type="date" name="date_from" class="form-control" value="<?= e($firstDayOfMonth) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1 fw-semibold">To Date (End)</label>
                        <input type="date" name="date_to" class="form-control" value="<?= e($today) ?>">
                    </div>
                </div>
            </div>

            <div class="mb-4 pb-3 border-bottom">
                <label class="form-label fw-bold text-dark mb-2 fs-6">
                    <i class="bi bi-building me-1 text-primary"></i>
                    Filter by Consignee / Destination Warehouse
                </label>
                <select name="warehouse_ids[]" class="form-select" multiple size="5" aria-describedby="warehouseFilterHelp">
                    <?php foreach ($warehouses as $warehouse): ?>
                        <option value="<?= (int)$warehouse['id'] ?>">
                            <?= e($warehouse['name']) ?><?= !empty($warehouse['code']) ? ' (' . e($warehouse['code']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="warehouseFilterHelp" class="form-text">
                    Select one or more warehouses to filter the outward dispatch and detailed stock ledger data. Leave all unselected to include every warehouse.
                    The current dispatch records identify the destination warehouse; they do not have a separate consignee-name field.
                </div>
            </div>

            <!-- 2. Data Modules Selection -->
            <div class="mb-4 pb-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="form-label fw-bold text-dark mb-0 fs-6">
                        <span class="badge bg-primary text-white rounded-circle me-1" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">2</span>
                        Choose Data Modules to Include
                    </label>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none fw-semibold" onclick="toggleAllExportModules(true)">Select All</button>
                        <span class="text-muted">|</span>
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-secondary fw-semibold" onclick="toggleAllExportModules(false)">Clear</button>
                    </div>
                </div>

                <div class="row g-3">
                    <?php 
                    $categories = [];
                    foreach ($modules as $modKey => $modInfo) {
                        $cat = $modInfo['category'] ?? 'General';
                        $categories[$cat][$modKey] = $modInfo;
                    }
                    ?>
                    <?php foreach ($categories as $catName => $catModules): ?>
                        <div class="col-12 mt-3">
                            <span class="text-uppercase tracking-wider text-muted fw-bold small" style="letter-spacing: 0.05em; font-size: 11px;">
                                <i class="bi bi-tag-fill me-1 text-primary"></i> <?= e($catName) ?>
                            </span>
                        </div>
                        <?php foreach ($catModules as $modKey => $mod): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="p-3 bg-light bg-opacity-50 rounded-3 border h-100 hover-shadow transition">
                                    <div class="form-check d-flex align-items-start gap-2">
                                        <input class="form-check-input export-mod-cb mt-1" type="checkbox" name="modules[]" value="<?= e($modKey) ?>" id="mod_<?= e($modKey) ?>" checked>
                                        <label class="form-check-label w-100 cursor-pointer" for="mod_<?= e($modKey) ?>">
                                            <div class="fw-bold text-dark d-flex align-items-center gap-1.5 mb-1">
                                                <i class="<?= e($mod['icon']) ?> text-<?= e($mod['color']) ?>"></i>
                                                <span><?= e($mod['name']) ?></span>
                                            </div>
                                            <div class="text-muted small" style="font-size: 11.5px; line-height: 1.35;">
                                                <?= e($mod['desc']) ?>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Export Format Selection -->
            <div>
                <label class="form-label fw-bold text-dark mb-3 fs-6">
                    <span class="badge bg-primary text-white rounded-circle me-1" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">3</span>
                    Choose Export Format
                </label>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="card border p-3 rounded-3 cursor-pointer h-100 transition format-card active" for="formatCsv" style="border-width: 2px !important;">
                            <div class="form-check d-flex align-items-start gap-3">
                                <input class="form-check-input mt-1" type="radio" name="format" id="formatCsv" value="CSV" checked onchange="highlightFormat(this)">
                                <div>
                                    <div class="fw-bold text-success fs-6 d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-file-earmark-excel-fill fs-5"></i>
                                        <span>CSV Spreadsheet (Microsoft Excel Compatible)</span>
                                    </div>
                                    <div class="text-muted small">
                                        Exports raw dataset tables, executive summaries, numeric totals, and clean decimal numbers compatible with Microsoft Excel, Google Sheets, and ERP imports.
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="card border p-3 rounded-3 cursor-pointer h-100 transition format-card" for="formatPrint" style="border-width: 2px !important;">
                            <div class="form-check d-flex align-items-start gap-3">
                                <input class="form-check-input mt-1" type="radio" name="format" id="formatPrint" value="PRINT" onchange="highlightFormat(this)">
                                <div>
                                    <div class="fw-bold text-primary fs-6 d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-printer-fill fs-5"></i>
                                        <span>Printable Master Summary Report (HTML / PDF)</span>
                                    </div>
                                    <div class="text-muted small">
                                        Generates a formatted executive visual presentation with KPI cards, P&amp;L margin tables, operational yields, and ready-to-print PDF layout.
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-light py-3 px-4 d-flex justify-content-between align-items-center">
            <span class="text-muted small">
                <i class="bi bi-info-circle me-1 text-primary"></i> Data is retrieved with high-speed indexed range scans.
            </span>
            <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold shadow-sm d-flex align-items-center gap-2">
                <i class="bi bi-download"></i>
                <span>Generate &amp; Download Export</span>
            </button>
        </div>
    </div>
</form>

<script>
function togglePeriodInputs() {
    const isMonth = document.getElementById('periodTypeMonth').checked;
    const mRow = document.getElementById('monthPickerRow');
    const cRow = document.getElementById('customPickerRow');
    if (isMonth) {
        mRow.classList.remove('d-none');
        cRow.classList.add('d-none');
    } else {
        mRow.classList.add('d-none');
        cRow.classList.remove('d-none');
    }
}

function toggleAllExportModules(check) {
    document.querySelectorAll('.export-mod-cb').forEach(cb => cb.checked = check);
}

function highlightFormat(radio) {
    document.querySelectorAll('.format-card').forEach(c => c.classList.remove('active', 'border-primary', 'border-success'));
    if (radio.value === 'CSV') {
        radio.closest('.format-card').classList.add('active', 'border-success');
    } else {
        radio.closest('.format-card').classList.add('active', 'border-primary');
    }
}

function submitQuickPrint() {
    const form = document.getElementById('exportMainForm');
    const oldFormat = form.querySelector('input[name="format"]:checked').value;
    document.getElementById('formatPrint').checked = true;
    form.submit();
    setTimeout(() => {
        document.getElementById('format' + (oldFormat === 'CSV' ? 'Csv' : 'Print')).checked = true;
    }, 100);
}
</script>
