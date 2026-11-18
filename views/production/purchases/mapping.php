<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-diagram-3 me-2 text-primary"></i>Purchase Item Mapping</h2>
        <p class="text-muted small mb-0">Map every purchase line to master categories, subcategories and attributes. Saving a mapping auto-generates its QR batch and GRN.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('purchases') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Purchases</a>
        <a href="<?= url('purchases/create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Record Purchase</a>
    </div>
</div>

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Total Purchase Items</div>
                <div class="fs-3 fw-bold text-dark"><?= number_format((int)($metrics['total_items'] ?? 0)) ?></div>
                <div class="small text-muted"><?= number_format((float)($metrics['total_quantity'] ?? 0)) ?> purchased units (pcs)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Pending Unmapped</div>
                <div class="fs-3 fw-bold text-warning"><?= number_format((int)($metrics['unmapped_count'] ?? 0)) ?></div>
                <div class="small text-muted">Awaiting category &amp; attribute mapping</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Successfully Mapped</div>
                <div class="fs-3 fw-bold text-success"><?= number_format((int)($metrics['mapped_count'] ?? 0)) ?></div>
                <div class="small text-muted">Configured in master catalog</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Mapping Completion</div>
                <?php
                    $tot = (int)($metrics['total_items'] ?? 0);
                    $map = (int)($metrics['mapped_count'] ?? 0);
                    $pct = $tot > 0 ? round(($map / $tot) * 100, 1) : 100;
                ?>
                <div class="fs-3 fw-bold text-primary"><?= $pct ?>%</div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="small text-muted mt-2">
                    QR batches: <?= number_format((int)($metrics['qr_count'] ?? 0)) ?> &nbsp;·&nbsp; GRNs: <?= number_format((int)($metrics['grn_count'] ?? 0)) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Search Toolbar -->
<div class="card border shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('purchases/mapping') ?>" class="row g-2 align-items-center">
            <div class="col-auto">
                <div class="btn-group" role="group">
                    <a href="<?= url('purchases/mapping?status=unmapped' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($status === 'unmapped') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-clock-history me-1"></i>Unmapped (<?= number_format((int)($metrics['unmapped_count'] ?? 0)) ?>)
                    </a>
                    <a href="<?= url('purchases/mapping?status=mapped' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($status === 'mapped') ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-check-circle me-1"></i>Mapped (<?= number_format((int)($metrics['mapped_count'] ?? 0)) ?>)
                    </a>
                    <a href="<?= url('purchases/mapping?status=complementary' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($status === 'complementary') ? 'btn-info text-dark fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-gift me-1"></i>Complementary (<?= number_format((int)($metrics['complementary_count'] ?? 0)) ?>)
                    </a>
                    <a href="<?= url('purchases/mapping?status=qr' . ($search ? '&search=' . urlencode($search) : '')) ?>"
                       class="btn btn-sm <?= ($status === 'qr') ? 'btn-dark fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-qr-code me-1"></i>QR Ready (<?= number_format((int)($metrics['qr_count'] ?? 0)) ?>)
                    </a>
                    <a href="<?= url('purchases/mapping?status=grn' . ($search ? '&search=' . urlencode($search) : '')) ?>"
                       class="btn btn-sm <?= ($status === 'grn') ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-truck me-1"></i>GRN Created (<?= number_format((int)($metrics['grn_count'] ?? 0)) ?>)
                    </a>
                    <a href="<?= url('purchases/mapping?status=all' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($status === 'all') ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
                        All Items (<?= number_format((int)($metrics['total_items'] ?? 0)) ?>)
                    </a>
                </div>
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- All Mapped Categories --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= ((int)$categoryId === (int)$cat['id']) ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?> (<?= e($cat['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="item_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- All Item Types --</option>
                    <?php foreach (['FABRIC_ROLL', 'RAW_MATERIAL', 'THIRD_PARTY', 'OWN_PRODUCT'] as $type): ?>
                        <option value="<?= e($type) ?>" <?= (($itemType ?? '') === $type) ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $type)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 ms-auto">
                <div class="input-group input-group-sm">
                    <input type="hidden" name="status" value="<?= e($status) ?>">
                    <input type="text" name="search" class="form-control" placeholder="Search item, vendor SKU, purchase #, vendor..." value="<?= e($search) ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
                    <?php if ($search || $categoryId > 0 || $status !== 'unmapped'): ?>
                        <a href="<?= url('purchases/mapping') ?>" class="btn btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Items Table -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>Purchase Items Roster</h5>
        <span class="badge bg-secondary-subtle text-secondary border"><?= count($items) ?> Records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13.5px;">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th>#</th>
                    <th>Purchase # / Date</th>
                    <th>Vendor / Brand</th>
                    <th>Product / Item Name</th>
                    <th>Type</th>
                    <th>Vendor SKU / Barcode</th>
                    <th>Size &amp; Color</th>
                    <th class="text-end">Inward Qty</th>
                    <th class="text-end">Cost / MRP</th>
                    <th class="text-center">Status</th>
                    <th>Mapped Hierarchy</th>
                    <th>QR / GRN</th>
                    <th>Attributes</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="14" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                            No purchase items found matching your current filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $idx => $item): 
                        $attrs = !empty($item['mapped_attributes']) ? json_decode($item['mapped_attributes'], true) : [];
                    ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <a href="<?= url('purchases/view/' . $item['purchase_id']) ?>" class="fw-bold text-decoration-none font-monospace">
                                    <?= e($item['purchase_number']) ?>
                                </a>
                                <small class="text-muted d-block"><?= e(date('d M Y', strtotime($item['purchase_date']))) ?></small>
                                <?php if (!empty($item['invoice_number'])): ?>
                                    <small class="text-muted d-block">Inv: <?= e($item['invoice_number']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($item['seller_company'] ?: $item['seller_name']) ?></strong>
                                <?php if (!empty($item['seller_company'])): ?>
                                    <small class="text-muted d-block"><?= e($item['seller_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="text-dark d-block"><?= e($item['item_name']) ?></strong>
                                <span class="badge bg-light text-dark border">Item #<?= (int)$item['id'] ?></span>
                                <?php if (!empty($item['is_complementary'])): ?>
                                    <span class="badge bg-info-subtle text-info border">
                                        <i class="bi bi-gift-fill me-1"></i>Complementary
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border small">
                                    <?= e(str_replace('_', ' ', (string)($item['item_type'] ?? 'THIRD_PARTY'))) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($item['vendor_sku'])): ?>
                                    <span class="badge bg-primary-subtle text-primary border font-monospace"><?= e($item['vendor_sku']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><small class="text-muted">Size:</small> <strong><?= e($item['size'] ?: '—') ?></strong></div>
                                <div><small class="text-muted">Color:</small> <strong><?= e($item['color'] ?: '—') ?></strong></div>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold fs-6"><?= number_format((float)$item['quantity']) ?></span>
                                <small class="text-muted d-block"><?= e($item['unit']) ?></small>
                            </td>
                            <td class="text-end">
                                <div><small class="text-muted">Cost:</small> <strong>₹<?= number_format((float)$item['unit_price'], 2) ?></strong></div>
                                <?php if ((float)$item['mrp'] > 0): ?>
                                    <div><small class="text-muted">MRP:</small> ₹<?= number_format((float)$item['mrp'], 2) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($item['is_mapped'])): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check2-circle me-1"></i>Mapped
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                        <i class="bi bi-exclamation-circle me-1"></i>Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['is_mapped'])): ?>
                                    <div class="fw-semibold text-dark"><?= e($item['category_name']) ?></div>
                                    <?php if (!empty($item['subcategory_name'])): ?>
                                        <small class="text-muted"><i class="bi bi-arrow-return-right me-1"></i><?= e($item['subcategory_name']) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($item['sub_subcategory_name'])): ?>
                                        <small class="text-muted d-block"><i class="bi bi-arrow-return-right ms-2 me-1"></i><?= e($item['sub_subcategory_name']) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['qr_generation_id']) && !empty($item['qr_batch_code'])): ?>
                                    <a href="<?= url('production/qr-generations/view/' . (int)$item['qr_generation_id']) ?>"
                                       class="badge bg-dark-subtle text-dark border text-decoration-none mb-1 d-inline-block"
                                       title="View generated QR batch">
                                        <i class="bi bi-qr-code me-1"></i><?= e($item['qr_batch_code']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border mb-1 d-inline-block">QR pending</span>
                                <?php endif; ?>
                                <?php if (!empty($item['grn_id']) && !empty($item['grn_number'])): ?>
                                    <a href="<?= url('production/inwards/view/' . (int)$item['grn_id']) ?>"
                                       class="badge bg-success-subtle text-success border text-decoration-none d-inline-block"
                                       title="View Goods Receipt Note">
                                        <i class="bi bi-check2-circle me-1"></i><?= e($item['grn_number']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border d-inline-block">GRN pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($attrs) && is_array($attrs)): ?>
                                    <div class="d-flex flex-wrap gap-1" style="max-width: 220px;">
                                        <?php foreach ($attrs as $attrKey => $attrVal): ?>
                                            <span class="badge bg-light text-secondary border" style="font-size: 11px;">
                                                <strong><?= e($attrKey) ?>:</strong> <?= e(is_array($attrVal) ? implode(', ', $attrVal) : (string)$attrVal) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button type="button" 
                                        class="btn btn-sm <?= !empty($item['is_mapped']) ? 'btn-outline-primary' : 'btn-warning text-dark fw-bold' ?> btn-open-map-modal"
                                        data-item-id="<?= (int)$item['id'] ?>"
                                        data-item-name="<?= e($item['item_name']) ?>"
                                        data-item-sku="<?= e($item['vendor_sku'] ?? '') ?>"
                                        data-item-size="<?= e($item['size'] ?? '') ?>"
                                        data-item-color="<?= e($item['color'] ?? '') ?>"
                                        data-item-complementary="<?= (int)($item['is_complementary'] ?? 0) ?>"
                                        data-item-category="<?= (int)($item['mapped_category_id'] ?? 0) ?>"
                                        data-item-subcategory="<?= (int)($item['mapped_subcategory_id'] ?? 0) ?>"
                                        data-item-subsubcategory="<?= (int)($item['mapped_sub_subcategory_id'] ?? 0) ?>">
                                    <i class="bi <?= !empty($item['is_mapped']) ? 'bi-pencil-square' : 'bi-diagram-3-fill' ?> me-1"></i>
                                    <?= !empty($item['is_mapped']) ? 'Edit' : 'Map' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Interactive Mapping Modal -->
<div class="modal fade" id="modalMapProduct" tabindex="-1" aria-labelledby="modalMapProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title fs-6 fw-bold" id="modalMapProductLabel">
                    <i class="bi bi-diagram-3-fill me-2 text-warning"></i> Map Purchase Item to Master Catalog
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formSaveProductMapping" method="POST" action="<?= url('purchases/mapping/save') ?>">
                <?= CSRF::field() ?>
                <input type="hidden" name="item_id" id="mapItemId" value="">

                <div class="modal-body p-4">
                    <!-- Product Header Info Card -->
                    <div class="border rounded-3 p-3 bg-light mb-4">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-7">
                                <small class="text-muted text-uppercase fw-semibold d-block">Purchase Item</small>
                                <h6 class="fw-bold mb-1 text-dark" id="mapItemNameDisplay">—</h6>
                                <div class="small text-muted font-monospace" id="mapItemSkuDisplay">SKU: —</div>
                            </div>
                            <div class="col-md-5 text-md-end">
                                <span class="badge bg-secondary-subtle text-secondary border me-1" id="mapItemSizeBadge">Size: —</span>
                                <span class="badge bg-secondary-subtle text-secondary border" id="mapItemColorBadge">Color: —</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 small mb-4">
                        <i class="bi bi-info-circle me-1"></i> Saving this mapping will sync the catalog and automatically create the QR generation batch and GRN for this line.
                    </div>

                    <!-- Complementary Product Checkbox -->
                    <div class="card border border-info-subtle bg-light p-3 mb-4 rounded-3">
                        <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                            <input class="form-check-input fs-5 mt-0" type="checkbox" name="is_complementary" id="mapIsComplementary" value="1" role="switch">
                            <label class="form-check-label fw-bold text-dark cursor-pointer mb-0 ms-2" for="mapIsComplementary">
                                <i class="bi bi-gift text-info me-1"></i> Complementary Product
                                <span class="d-block small text-muted fw-normal mt-1">
                                    Check this box to classify this product as complementary / free promotional merchandise. In the billing area, it can only be billed when at least one paid product is purchased, and its unit price will be automatically set to ₹0.00.
                                </span>
                            </label>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-tags text-primary me-1"></i> 1. Master Category Hierarchy
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label required fw-bold small text-dark">Category *</label>
                            <select name="category_id" id="mapCategorySelect" class="form-select" required>
                                <option value="">-- Select Master Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?> (<?= e($cat['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-dark">Subcategory</label>
                            <select name="subcategory_id" id="mapSubcategorySelect" class="form-select" disabled>
                                <option value="">-- Select Subcategory --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-dark">Sub-Subcategory</label>
                            <select name="sub_subcategory_id" id="mapSubSubcategorySelect" class="form-select" disabled>
                                <option value="">-- Select Sub-Subcategory --</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-sliders text-success me-1"></i> 2. Category Attributes &amp; Specifications
                    </h6>

                    <div id="attributesContainer" class="p-3 border rounded bg-white min-vh-10">
                        <div class="text-muted small text-center py-3" id="attributesNotice">
                            <i class="bi bi-info-circle me-1"></i> Select a category above to load configured attributes.
                        </div>
                        <div id="attributesDynamicFields" class="row g-3 d-none"></div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-4">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold" id="btnSaveMapping">
                        <i class="bi bi-check-circle me-1"></i> Save Taxonomy Mapping
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('modalMapProduct');
    const modal = new bootstrap.Modal(modalElement);
    const form = document.getElementById('formSaveProductMapping');
    const catSelect = document.getElementById('mapCategorySelect');
    const subcatSelect = document.getElementById('mapSubcategorySelect');
    const subsubcatSelect = document.getElementById('mapSubSubcategorySelect');
    const attributesNotice = document.getElementById('attributesNotice');
    const attributesDynamicFields = document.getElementById('attributesDynamicFields');

    let currentItemData = null;
    let pendingSubcatId = 0;
    let pendingSubsubcatId = 0;

    // Open Mapping Modal
    document.querySelectorAll('.btn-open-map-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const itemName = this.dataset.itemName;
            const itemSku = this.dataset.itemSku;
            const itemSize = this.dataset.itemSize;
            const itemColor = this.dataset.itemColor;
            const itemComplementary = parseInt(this.dataset.itemComplementary, 10) || 0;
            const catId = parseInt(this.dataset.itemCategory, 10) || 0;
            pendingSubcatId = parseInt(this.dataset.itemSubcategory, 10) || 0;
            pendingSubsubcatId = parseInt(this.dataset.itemSubsubcategory, 10) || 0;

            document.getElementById('mapItemId').value = itemId;
            document.getElementById('mapItemNameDisplay').textContent = itemName;
            document.getElementById('mapItemSkuDisplay').textContent = itemSku ? ('SKU: ' + itemSku) : 'SKU: None';
            document.getElementById('mapItemSizeBadge').textContent = 'Size: ' + (itemSize || 'None');
            document.getElementById('mapItemColorBadge').textContent = 'Color: ' + (itemColor || 'None');
            document.getElementById('mapIsComplementary').checked = (itemComplementary === 1);

            // Reset dropdowns
            catSelect.value = catId > 0 ? catId : '';
            subcatSelect.innerHTML = '<option value="">-- Select Subcategory --</option>';
            subcatSelect.disabled = true;
            subsubcatSelect.innerHTML = '<option value="">-- Select Sub-Subcategory --</option>';
            subsubcatSelect.disabled = true;

            attributesNotice.classList.remove('d-none');
            attributesNotice.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Loading attributes...';
            attributesDynamicFields.classList.add('d-none');
            attributesDynamicFields.innerHTML = '';

            // Fetch current item details via AJAX to get existing attributes
            fetch('<?= url("api/purchases/mapping/item/") ?>' + itemId)
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.item) {
                        currentItemData = res.item;
                        if (res.item.is_complementary !== undefined) {
                            document.getElementById('mapIsComplementary').checked = (parseInt(res.item.is_complementary, 10) === 1);
                        }
                        if (catId > 0) {
                            catSelect.value = catId;
                            triggerCategoryChange(catId, pendingSubcatId, pendingSubsubcatId);
                        } else {
                            attributesNotice.innerHTML = '<i class="bi bi-info-circle me-1"></i> Select a category above to load configured attributes.';
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                });

            modal.show();
        });
    });

    // Handle Category Change
    catSelect.addEventListener('change', function() {
        const catId = parseInt(this.value, 10) || 0;
        triggerCategoryChange(catId, 0, 0);
    });

    function triggerCategoryChange(catId, preselectSubcatId, preselectSubsubcatId) {
        subcatSelect.innerHTML = '<option value="">-- Select Subcategory --</option>';
        subcatSelect.disabled = true;
        subsubcatSelect.innerHTML = '<option value="">-- Select Sub-Subcategory --</option>';
        subsubcatSelect.disabled = true;

        if (catId <= 0) {
            attributesNotice.classList.remove('d-none');
            attributesNotice.innerHTML = '<i class="bi bi-info-circle me-1"></i> Select a category above to load configured attributes.';
            attributesDynamicFields.classList.add('d-none');
            attributesDynamicFields.innerHTML = '';
            return;
        }

        // Fetch subcategories
        fetch('<?= url("api/subcategories/") ?>' + catId)
            .then(r => r.json())
            .then(data => {
                if (data && Array.isArray(data) && data.length > 0) {
                    subcatSelect.disabled = false;
                    data.forEach(sc => {
                        const opt = document.createElement('option');
                        opt.value = sc.id;
                        opt.textContent = sc.name + (sc.code ? ' (' + sc.code + ')' : '');
                        if (preselectSubcatId && sc.id == preselectSubcatId) {
                            opt.selected = true;
                        }
                        subcatSelect.appendChild(opt);
                    });

                    if (preselectSubcatId) {
                        triggerSubcategoryChange(preselectSubcatId, preselectSubsubcatId);
                    }
                }
            })
            .catch(err => console.error(err));

        // Load attributes for category
        loadAttributes(catId, preselectSubcatId, preselectSubsubcatId);
    }

    // Handle Subcategory Change
    subcatSelect.addEventListener('change', function() {
        const subcatId = parseInt(this.value, 10) || 0;
        triggerSubcategoryChange(subcatId, 0);
    });

    function triggerSubcategoryChange(subcatId, preselectSubsubcatId) {
        subsubcatSelect.innerHTML = '<option value="">-- Select Sub-Subcategory --</option>';
        subsubcatSelect.disabled = true;

        if (subcatId <= 0) {
            loadAttributes(parseInt(catSelect.value, 10) || 0, 0, 0);
            return;
        }

        // Fetch sub-subcategories
        fetch('<?= url("api/sub-subcategories/") ?>' + subcatId)
            .then(r => r.json())
            .then(data => {
                if (data && Array.isArray(data) && data.length > 0) {
                    subsubcatSelect.disabled = false;
                    data.forEach(ssc => {
                        const opt = document.createElement('option');
                        opt.value = ssc.id;
                        opt.textContent = ssc.name + (ssc.code ? ' (' + ssc.code + ')' : '');
                        if (preselectSubsubcatId && ssc.id == preselectSubsubcatId) {
                            opt.selected = true;
                        }
                        subsubcatSelect.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error(err));

        loadAttributes(parseInt(catSelect.value, 10) || 0, subcatId, preselectSubsubcatId);
    }

    // Handle Sub-Subcategory Change
    subsubcatSelect.addEventListener('change', function() {
        const subsubId = parseInt(this.value, 10) || 0;
        loadAttributes(parseInt(catSelect.value, 10) || 0, parseInt(subcatSelect.value, 10) || 0, subsubId);
    });

    // Load Dynamic Attributes
    function loadAttributes(catId, subcatId, subsubcatId) {
        if (!catId) return;

        attributesNotice.classList.remove('d-none');
        attributesNotice.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Loading category attributes...';
        attributesDynamicFields.classList.add('d-none');
        attributesDynamicFields.innerHTML = '';

        let url = '<?= url("api/categories/attributes/") ?>' + catId;
        const params = [];
        if (subcatId) params.push('subcategory_id=' + subcatId);
        if (subsubcatId) params.push('sub_subcategory_id=' + subsubcatId);
        if (params.length) url += '?' + params.join('&');

        fetch(url)
            .then(r => r.json())
            .then(res => {
                const attrs = res.attributes || res;
                if (!Array.isArray(attrs) || attrs.length === 0) {
                    attributesNotice.innerHTML = '<i class="bi bi-check-circle text-muted me-1"></i> No custom attributes configured for this category hierarchy.';
                    return;
                }

                attributesNotice.classList.add('d-none');
                attributesDynamicFields.classList.remove('d-none');

                const savedValues = (currentItemData && currentItemData.parsed_attributes) ? currentItemData.parsed_attributes : {};

                attrs.forEach(attr => {
                    const col = document.createElement('div');
                    col.className = 'col-md-6';

                    const label = document.createElement('label');
                    label.className = 'form-label fw-bold small text-dark';
                    label.textContent = attr.attr_name + (attr.is_required ? ' *' : '');
                    col.appendChild(label);

                    const savedVal = savedValues[attr.attr_name] !== undefined ? savedValues[attr.attr_name] : '';

                    if (attr.attr_type === 'SELECT') {
                        const sel = document.createElement('select');
                        sel.name = `attributes[${attr.attr_name}]`;
                        sel.className = 'form-select form-select-sm';
                        if (attr.is_required) sel.required = true;

                        const defOpt = document.createElement('option');
                        defOpt.value = '';
                        defOpt.textContent = `-- Select ${attr.attr_name} --`;
                        sel.appendChild(defOpt);

                        const opts = (attr.attr_options || '').split(',').map(s => s.trim()).filter(Boolean);
                        opts.forEach(optVal => {
                            const opt = document.createElement('option');
                            opt.value = optVal;
                            opt.textContent = optVal;
                            if (savedVal == optVal) opt.selected = true;
                            sel.appendChild(opt);
                        });
                        col.appendChild(sel);
                    } else if (attr.attr_type === 'MULTI_SELECT') {
                        const boxDiv = document.createElement('div');
                        boxDiv.className = 'border rounded p-2 bg-light d-flex flex-wrap gap-2';
                        const opts = (attr.attr_options || '').split(',').map(s => s.trim()).filter(Boolean);
                        const savedArr = Array.isArray(savedVal) ? savedVal : [savedVal];

                        opts.forEach((optVal, optIdx) => {
                            const chkWrapper = document.createElement('div');
                            chkWrapper.className = 'form-check form-check-inline mb-0';
                            const chk = document.createElement('input');
                            chk.type = 'checkbox';
                            chk.className = 'form-check-input';
                            chk.name = `attributes[${attr.attr_name}][]`;
                            chk.value = optVal;
                            chk.id = `attr_${attr.id}_${optIdx}`;
                            if (savedArr.includes(optVal)) chk.checked = true;

                            const chkLbl = document.createElement('label');
                            chkLbl.className = 'form-check-label small';
                            chkLbl.htmlFor = chk.id;
                            chkLbl.textContent = optVal;

                            chkWrapper.appendChild(chk);
                            chkWrapper.appendChild(chkLbl);
                            boxDiv.appendChild(chkWrapper);
                        });
                        col.appendChild(boxDiv);
                    } else if (attr.attr_type === 'NUMBER') {
                        const num = document.createElement('input');
                        num.type = 'number';
                        num.step = 'any';
                        num.name = `attributes[${attr.attr_name}]`;
                        num.className = 'form-control form-control-sm';
                        num.value = savedVal;
                        if (attr.is_required) num.required = true;
                        col.appendChild(num);
                    } else {
                        // TEXT default
                        const txt = document.createElement('input');
                        txt.type = 'text';
                        txt.name = `attributes[${attr.attr_name}]`;
                        txt.className = 'form-control form-control-sm';
                        txt.value = savedVal;
                        if (attr.is_required) txt.required = true;
                        col.appendChild(txt);
                    }

                    attributesDynamicFields.appendChild(col);
                });
            })
            .catch(err => {
                console.error(err);
                attributesNotice.innerHTML = '<i class="bi bi-exclamation-triangle text-danger me-1"></i> Failed to load attributes.';
            });
    }
});
</script>
