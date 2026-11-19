<?php
$totalQty = 0.0;
$totalAmount = 0.0;
$mappedCount = 0;
foreach ($items as $summaryItem) {
    $totalQty += (float)($summaryItem['quantity'] ?? 0);
    $totalAmount += (float)($summaryItem['total_price'] ?? 0);
    if (!empty($summaryItem['is_mapped'])) {
        $mappedCount++;
    }
}

$qrTabForType = static function (string $itemType): string {
    switch (strtoupper($itemType)) {
        case 'FABRIC_ROLL':
            return 'fabric';
        case 'RAW_MATERIAL':
            return 'raw_material';
        case 'OWN_PRODUCT':
            return '';
        default:
            return 'third_party';
    }
};

$firstQrTab = '';
foreach ($items as $billItem) {
    $candidateTab = $qrTabForType((string)($billItem['item_type'] ?? ''));
    if ($candidateTab !== '') {
        $firstQrTab = $candidateTab;
        break;
    }
}
?>

<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Map Purchase Bill</h2>
        <p class="text-muted small mb-0">Full bill view — map every product and set prices. Mapping creates GRNs; eligible mapped lines can be generated afterward from QR Generations.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($firstQrTab !== ''): ?>
            <a href="<?= url('production/qr-generations?tab=' . e($firstQrTab)) ?>" class="btn btn-outline-info">
                <i class="bi bi-qr-code me-1"></i> QR Generations
            </a>
        <?php endif; ?>
        <a href="<?= url('purchases/mapping') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Mapping List
        </a>
    </div>
</div>

<!-- Bill header — mirrors the purchase bill create/view layout -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Purchase Bill &amp; Supplier Details</h5>
        <span class="badge bg-primary-subtle text-primary border fs-6">
            <?= e($purchase['purchase_number']) ?>
        </span>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 align-items-stretch">
            <div class="col-lg-3">
                <div class="border rounded-3 bg-light h-100 p-3">
                    <div class="text-muted small fw-semibold text-uppercase">Purchase Date</div>
                    <div class="fw-bold fs-6 mt-1"><?= e(date('d M Y', strtotime($purchase['purchase_date']))) ?></div>
                    <div class="small text-muted mt-2">Invoice / Bill No.</div>
                    <div class="fw-semibold"><?= e($purchase['invoice_number'] ?: '—') ?></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded-3 bg-light h-100 p-3">
                    <div class="text-muted small fw-semibold text-uppercase">Supplier / Vendor</div>
                    <div class="fw-bold fs-6 mt-1"><?= e($purchase['seller_company'] ?: $purchase['seller_name']) ?></div>
                    <?php if (!empty($purchase['seller_company'])): ?>
                        <div class="small text-muted"><?= e($purchase['seller_name']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($purchase['notes'])): ?>
                        <div class="small text-muted mt-2 fst-italic">“<?= e($purchase['notes']) ?>”</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="border rounded-3 bg-white h-100 p-3 text-center">
                    <div class="text-muted small fw-semibold text-uppercase">Bill Lines</div>
                    <div class="fw-bold fs-3 text-primary mt-1"><?= count($items) ?></div>
                    <div class="small text-muted"><?= $mappedCount ?> mapped</div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="border rounded-3 bg-success-subtle h-100 p-3 text-center">
                    <div class="text-muted small fw-semibold text-uppercase">Purchased Quantity</div>
                    <div class="fw-bold fs-3 text-success mt-1"><?= number_format($totalQty, 2) ?></div>
                    <div class="small text-muted">Line total: ₹<?= number_format($totalAmount, 2) ?></div>
                </div>
            </div>
        </div>

        <?php if (!empty($items)): ?>
        <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
            <button type="button" class="btn btn-outline-info btn-sm"
                    data-bs-toggle="collapse" data-bs-target="#complementaryProductPanel"
                    aria-expanded="false" aria-controls="complementaryProductPanel">
                <i class="bi bi-gift me-1"></i>Select Complementary Products
            </button>
            <span class="small text-muted"><i class="bi bi-info-circle me-1"></i>Complementary products are packed with the main product and do not change the bill total.</span>
        </div>
        <div class="collapse mt-3" id="complementaryProductPanel">
            <div class="border rounded-3 bg-light p-3">
                <div class="row g-2">
                    <?php foreach ($items as $item): ?>
                        <?php $itemId = (int)$item['id']; ?>
                        <div class="col-md-4 col-xl-3">
                            <div class="form-check border rounded bg-white p-2 ps-5 h-100">
                                <input class="form-check-input" type="checkbox"
                                       name="items[<?= $itemId ?>][is_complementary]"
                                       id="bill_complementary_<?= $itemId ?>" value="1"
                                       form="formSavePurchaseBillMapping"
                                       data-complementary-item="<?= $itemId ?>"
                                       <?= !empty($item['is_complementary']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="bill_complementary_<?= $itemId ?>">
                                    <span class="fw-semibold"><?= e($item['item_name']) ?></span>
                                    <small class="text-muted d-block">Qty: <?= number_format((float)$item['quantity']) ?> <?= e($item['unit']) ?></small>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($items)): ?>
    <div class="alert alert-warning">This purchase bill has no product lines to map.</div>
<?php else: ?>
    <form method="POST" action="<?= url('purchases/mapping/save') ?>" id="formSavePurchaseBillMapping">
        <?= CSRF::field() ?>
        <input type="hidden" name="purchase_id" value="<?= (int)$purchase['id'] ?>">

        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="bi bi-list-check me-2 text-success"></i>Product Lines &amp; Catalog Mapping</h5>
                <span class="small text-muted">Category is required. MRP and selling price are saved with the mapped catalog variant.</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 bill-mapping-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;">#</th>
                                <th style="min-width:250px;">Product / Bill Line</th>
                                <th class="text-end" style="min-width:100px;">Bill Qty</th>
                                <th class="text-end" style="min-width:110px;">Purchase Rate</th>
                                <th class="text-end" style="min-width:120px;">Line Amount</th>
                                <th class="text-end" style="min-width:120px;">MRP (₹) *</th>
                                <th class="text-end" style="min-width:130px;">Selling (₹) *</th>
                                <th style="min-width:170px;">Category *</th>
                                <th style="min-width:170px;">Subcategory</th>
                                <th style="min-width:180px;">Sub-Subcategory</th>
                                <th class="text-center" style="min-width:120px;">Attributes</th>
                                <th style="min-width:145px;">QR / GRN</th>
                                <th class="text-center" style="min-width:90px;">Compl.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $index => $item): ?>
                                <?php
                                    $itemId = (int)$item['id'];
                                    $itemTab = $qrTabForType((string)($item['item_type'] ?? 'THIRD_PARTY'));
                                    $itemTypeLabel = str_replace('_', ' ', (string)$item['item_type']);
                                ?>
                                <tr data-mapping-item data-item-id="<?= $itemId ?>" class="fw-normal">
                                    <td class="text-muted fw-bold"><?= $index + 1 ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($item['item_name']) ?></div>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            <span class="badge bg-secondary-subtle text-secondary border"><?= e($itemTypeLabel) ?></span>
                                            <?php if (!empty($item['vendor_sku'])): ?>
                                                <span class="badge bg-primary-subtle text-primary border font-monospace">SKU: <?= e($item['vendor_sku']) ?></span>
                                            <?php elseif (!empty($item['product_code'])): ?>
                                                <span class="badge bg-primary-subtle text-primary border font-monospace"><?= e($item['product_code']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['roll_number'])): ?>
                                                <span class="badge bg-light text-dark border">Roll: <?= e($item['roll_number']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['color'])): ?><span class="badge bg-light text-dark border"><?= e($item['color']) ?></span><?php endif; ?>
                                            <?php if (!empty($item['size'])): ?><span class="badge bg-light text-dark border"><?= e($item['size']) ?></span><?php endif; ?>
                                            <?php if (!empty($item['is_mapped'])): ?>
                                                <span class="badge bg-success-subtle text-success border"><i class="bi bi-check2-circle me-1"></i>Mapped</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border"><i class="bi bi-clock-history me-1"></i>Pending</span>
                                            <?php endif; ?>
                                            <span class="badge bg-info-subtle text-info border <?= !empty($item['is_complementary']) ? '' : 'd-none' ?>"
                                                  data-complementary-status="<?= $itemId ?>">Complementary</span>
                                        </div>
                                        <?php if (!empty($item['roll_number']) && (!empty($item['start_code']) || !empty($item['end_code']))): ?>
                                            <div class="small text-muted mt-1 font-monospace">
                                                QR Range: <?= e($item['start_code'] ?: '—') ?> → <?= e($item['end_code'] ?: '—') ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold"><?= number_format((float)$item['quantity'], 2) ?> <small class="text-muted"><?= e($item['unit']) ?></small></td>
                                    <td class="text-end">₹<?= number_format((float)$item['unit_price'], 2) ?></td>
                                    <td class="text-end fw-semibold">₹<?= number_format((float)$item['total_price'], 2) ?></td>
                                    <td>
                                        <input type="number" name="items[<?= $itemId ?>][mrp]" id="mrp_<?= $itemId ?>"
                                               class="form-control form-control-sm text-end" min="0.01" step="0.01" required
                                               value="<?= (float)$item['mrp'] > 0 ? e(number_format((float)$item['mrp'], 2, '.', '')) : '' ?>"
                                               placeholder="0.00">
                                    </td>
                                    <td>
                                        <input type="number" name="items[<?= $itemId ?>][selling_price]" id="selling_price_<?= $itemId ?>"
                                               class="form-control form-control-sm text-end" min="0.01" step="0.01" required
                                               value="<?= (float)$item['selling_price'] > 0 ? e(number_format((float)$item['selling_price'], 2, '.', '')) : '' ?>"
                                               placeholder="0.00">
                                    </td>
                                    <td>
                                        <select name="items[<?= $itemId ?>][category_id]" id="category_<?= $itemId ?>"
                                                class="form-select form-select-sm" data-category required>
                                            <option value="">-- Select Category --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= (int)$cat['id'] ?>" <?= ((int)$item['mapped_category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                                                    <?= e($cat['name']) ?> (<?= e($cat['code']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td data-subcategory-label="<?= $itemId ?>"><span class="text-muted small">—</span></td>
                                    <td data-subsubcategory-label="<?= $itemId ?>"><span class="text-muted small">—</span></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle-attributes="<?= $itemId ?>"
                                                title="Show category attributes">
                                            <i class="bi bi-sliders me-1"></i>Attributes
                                        </button>
                                    </td>
                                    <td>
                                        <?php if (strtoupper((string)($item['item_type'] ?? '')) !== 'OWN_PRODUCT'): ?>
                                        <?php if (!empty($item['qr_generation_id'])): ?>
                                            <a href="<?= url('production/qr-generations/view/' . (int)$item['qr_generation_id'] . ($itemTab !== '' ? '?tab=' . e($itemTab) : '')) ?>"
                                               class="badge bg-dark-subtle text-dark border text-decoration-none d-block mb-1">
                                                <i class="bi bi-qr-code me-1"></i><?= e($item['qr_batch_code'] ?: 'QR #' . (int)$item['qr_generation_id']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border d-block mb-1">No QR batch</span>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (!empty($item['grn_id'])): ?>
                                            <a href="<?= url('production/inwards/view/' . (int)$item['grn_id']) ?>"
                                               class="badge bg-primary-subtle text-primary border text-decoration-none">
                                                <i class="bi bi-truck me-1"></i><?= e($item['grn_number'] ?: 'GRN #' . (int)$item['grn_id']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border">No GRN</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <input class="form-check-input" type="checkbox"
                                               name="items[<?= $itemId ?>][is_complementary]"
                                               id="row_complementary_<?= $itemId ?>" value="1"
                                               data-complementary-item="<?= $itemId ?>"
                                               <?= !empty($item['is_complementary']) ? 'checked' : '' ?>>
                                    </td>
                                </tr>

                                <tr id="attr_row_<?= $itemId ?>" class="d-none" data-attribute-row="<?= $itemId ?>">
                                    <td colspan="13" class="bg-light p-4">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-lg-6">
                                                <label class="form-label fw-bold small" for="subcategory_<?= $itemId ?>">Subcategory</label>
                                                <select name="items[<?= $itemId ?>][subcategory_id]" id="subcategory_<?= $itemId ?>"
                                                        class="form-select form-select-sm" data-subcategory disabled>
                                                    <option value="">-- Select Subcategory --</option>
                                                </select>
                                            </div>
                                            <div class="col-lg-6">
                                                <label class="form-label fw-bold small" for="subsubcategory_<?= $itemId ?>">Sub-Subcategory</label>
                                                <select name="items[<?= $itemId ?>][sub_subcategory_id]" id="subsubcategory_<?= $itemId ?>"
                                                        class="form-select form-select-sm" data-subsubcategory disabled>
                                                    <option value="">-- Select Sub-Subcategory --</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <h6 class="fw-bold border-bottom pb-2 mb-3">
                                                <i class="bi bi-sliders text-success me-1"></i>Category Attributes &amp; Specifications
                                            </h6>
                                            <div data-attributes-notice class="text-muted small text-center py-3">
                                                <i class="bi bi-info-circle me-1"></i>Select a category to load configured attributes.
                                            </div>
                                            <div data-attributes-fields class="row g-3 d-none"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end">Bill Totals</th>
                                <th class="text-end"><?= number_format($totalQty, 2) ?></th>
                                <th colspan="2"></th>
                                <th colspan="8" class="text-start">
                                    <span class="small text-muted">Total line value: <strong class="text-dark">₹<?= number_format($totalAmount, 2) ?></strong></span>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-5">
            <div class="small text-muted">
                <i class="bi bi-shield-check me-1 text-success"></i>
                Saving creates/reuses GRNs. Mapped items are available for manual QR generation; inventory is posted later from the QR batch’s <strong>Add to Stock</strong> button.
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('purchases/mapping') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary fw-semibold btn-lg">
                    <i class="bi bi-check-circle me-1"></i>Save All Mappings
                </button>
            </div>
        </div>
    </form>
<?php endif; ?>

<style>
.bill-mapping-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.bill-mapping-table tbody tr[data-mapping-item]:hover > td {
    background-color: rgba(13, 110, 253, .035);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemData = <?= json_encode($items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    document.querySelectorAll('[data-complementary-item]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const id = this.dataset.complementaryItem;
            const isChecked = this.checked;
            document.querySelectorAll(`[data-complementary-item="${id}"]`).forEach(input => {
                input.checked = isChecked;
            });
            const badge = document.querySelector(`[data-complementary-status="${id}"]`);
            if (badge) badge.classList.toggle('d-none', !isChecked);
        });
    });

    document.querySelectorAll('[data-edit-mapping], [data-toggle-attributes]').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.editMapping || this.dataset.toggleAttributes;
            const row = document.getElementById('attr_row_' + id);
            if (!row) return;
            row.classList.toggle('d-none');
            if (!row.classList.contains('d-none')) {
                const select = document.querySelector(`#category_${id}`);
                if (select) select.focus();
            }
        });
    });

    document.querySelectorAll('[data-mapping-item]').forEach(row => {
        const itemId = row.dataset.itemId;
        const savedItem = itemData.find(item => String(item.id) === itemId) || {};
        const detailRow = document.getElementById('attr_row_' + itemId);
        const category = row.querySelector('[data-category]');
        const subcategory = detailRow.querySelector('[data-subcategory]');
        const subsubcategory = detailRow.querySelector('[data-subsubcategory]');
        const notice = detailRow.querySelector('[data-attributes-notice]');
        const fields = detailRow.querySelector('[data-attributes-fields]');
        const mrp = row.querySelector(`#mrp_${itemId}`);
        const sellingPrice = row.querySelector(`#selling_price_${itemId}`);
        const subcategoryLabel = row.querySelector(`[data-subcategory-label="${itemId}"]`);
        const subsubcategoryLabel = row.querySelector(`[data-subsubcategory-label="${itemId}"]`);

        function validatePrices() {
            const mrpValue = Number(mrp.value);
            const sellingValue = Number(sellingPrice.value);
            sellingPrice.setCustomValidity(
                sellingValue > 0 && mrpValue > 0 && sellingValue > mrpValue
                    ? 'Selling price cannot be greater than MRP.'
                    : ''
            );
        }

        function updateHierarchyLabels() {
            if (subcategoryLabel) {
                subcategoryLabel.innerHTML = subcategory.value
                    ? `<span class="badge bg-info-subtle text-info border">${subcategory.options[subcategory.selectedIndex].text}</span>`
                    : '<span class="text-muted small">—</span>';
            }
            if (subsubcategoryLabel) {
                subsubcategoryLabel.innerHTML = subsubcategory.value
                    ? `<span class="badge bg-light text-dark border">${subsubcategory.options[subsubcategory.selectedIndex].text}</span>`
                    : '<span class="text-muted small">—</span>';
            }
        }

        mrp.addEventListener('input', validatePrices);
        sellingPrice.addEventListener('input', validatePrices);
        category.addEventListener('change', () => loadHierarchy(0, 0));
        subcategory.addEventListener('change', () => loadSubSubcategories(0));
        subsubcategory.addEventListener('change', () => {
            loadAttributes(parseInt(category.value, 10) || 0, parseInt(subcategory.value, 10) || 0, parseInt(subsubcategory.value, 10) || 0);
            updateHierarchyLabels();
        });

        function loadHierarchy(preselectedSubcategory, preselectedSubSubcategory) {
            const categoryId = parseInt(category.value, 10) || 0;
            subcategory.innerHTML = '<option value="">-- Select Subcategory --</option>';
            subcategory.disabled = true;
            subsubcategory.innerHTML = '<option value="">-- Select Sub-Subcategory --</option>';
            subsubcategory.disabled = true;
            updateHierarchyLabels();

            if (!categoryId) {
                notice.classList.remove('d-none');
                notice.innerHTML = '<i class="bi bi-info-circle me-1"></i>Select a category to load configured attributes.';
                fields.classList.add('d-none');
                fields.innerHTML = '';
                return;
            }

            fetch('<?= url("api/subcategories/") ?>' + categoryId)
                .then(response => response.json())
                .then(data => {
                    if (!Array.isArray(data)) return;
                    data.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name + (sub.code ? ' (' + sub.code + ')' : '');
                        option.selected = Number(sub.id) === Number(preselectedSubcategory);
                        subcategory.appendChild(option);
                    });
                    subcategory.disabled = data.length === 0;
                    if (preselectedSubcategory) loadSubSubcategories(preselectedSubSubcategory);
                    updateHierarchyLabels();
                })
                .catch(error => console.error(error));

            loadAttributes(categoryId, preselectedSubcategory, preselectedSubSubcategory);
        }

        function loadSubSubcategories(preselectedSubSubcategory) {
            const subcategoryId = parseInt(subcategory.value, 10) || 0;
            subsubcategory.innerHTML = '<option value="">-- Select Sub-Subcategory --</option>';
            subsubcategory.disabled = true;
            if (!subcategoryId) {
                loadAttributes(parseInt(category.value, 10) || 0, 0, 0);
                updateHierarchyLabels();
                return;
            }

            fetch('<?= url("api/sub-subcategories/") ?>' + subcategoryId)
                .then(response => response.json())
                .then(data => {
                    if (!Array.isArray(data)) return;
                    data.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name + (sub.code ? ' (' + sub.code + ')' : '');
                        option.selected = Number(sub.id) === Number(preselectedSubSubcategory);
                        subsubcategory.appendChild(option);
                    });
                    subsubcategory.disabled = data.length === 0;
                    updateHierarchyLabels();
                })
                .catch(error => console.error(error));

            loadAttributes(parseInt(category.value, 10) || 0, subcategoryId, preselectedSubSubcategory);
        }

        function loadAttributes(categoryId, subcategoryId, subSubcategoryId) {
            if (!categoryId) return;
            notice.classList.remove('d-none');
            notice.innerHTML = '<span class="spinner-border spinner-border-sm text-primary me-2" role="status"></span>Loading category attributes...';
            fields.classList.add('d-none');
            fields.innerHTML = '';

            let endpoint = '<?= url("api/categories/attributes/") ?>' + categoryId;
            const params = [];
            if (subcategoryId) params.push('subcategory_id=' + subcategoryId);
            if (subSubcategoryId) params.push('sub_subcategory_id=' + subSubcategoryId);
            if (params.length) endpoint += '?' + params.join('&');

            fetch(endpoint)
                .then(response => response.json())
                .then(result => {
                    const attributes = result.attributes || result;
                    if (!Array.isArray(attributes) || attributes.length === 0) {
                        notice.innerHTML = '<i class="bi bi-check-circle text-muted me-1"></i>No custom attributes configured for this category hierarchy.';
                        return;
                    }

                    notice.classList.add('d-none');
                    fields.classList.remove('d-none');
                    const savedValues = savedItem.parsed_attributes || {};

                    attributes.forEach(attribute => {
                        const col = document.createElement('div');
                        col.className = 'col-md-6';
                        const label = document.createElement('label');
                        label.className = 'form-label fw-bold small';
                        label.textContent = attribute.attr_name + (attribute.is_required ? ' *' : '');
                        col.appendChild(label);

                        const savedValue = savedValues[attribute.attr_name] !== undefined ? savedValues[attribute.attr_name] : '';
                        const fieldName = `items[${itemId}][attributes][${attribute.attr_name}]`;
                        if (attribute.attr_type === 'SELECT') {
                            const select = document.createElement('select');
                            select.name = fieldName;
                            select.className = 'form-select form-select-sm';
                            if (attribute.is_required) select.required = true;
                            const placeholder = document.createElement('option');
                            placeholder.value = '';
                            placeholder.textContent = `-- Select ${attribute.attr_name} --`;
                            select.appendChild(placeholder);
                            (attribute.attr_options || '').split(',').map(value => value.trim()).filter(Boolean).forEach(value => {
                                const option = document.createElement('option');
                                option.value = value;
                                option.textContent = value;
                                option.selected = savedValue == value;
                                select.appendChild(option);
                            });
                            col.appendChild(select);
                        } else if (attribute.attr_type === 'MULTI_SELECT') {
                            const values = (attribute.attr_options || '').split(',').map(value => value.trim()).filter(Boolean);
                            const selectedValues = Array.isArray(savedValue) ? savedValue : [savedValue];
                            const wrapper = document.createElement('div');
                            wrapper.className = 'border rounded p-2 bg-white d-flex flex-wrap gap-2';
                            values.forEach((value, index) => {
                                const checkbox = document.createElement('input');
                                checkbox.type = 'checkbox';
                                checkbox.className = 'form-check-input';
                                checkbox.name = fieldName + '[]';
                                checkbox.value = value;
                                checkbox.id = `attr_${itemId}_${attribute.id}_${index}`;
                                checkbox.checked = selectedValues.includes(value);
                                const optionLabel = document.createElement('label');
                                optionLabel.className = 'form-check-label small';
                                optionLabel.htmlFor = checkbox.id;
                                optionLabel.textContent = value;
                                const group = document.createElement('div');
                                group.className = 'form-check form-check-inline mb-0';
                                group.append(checkbox, optionLabel);
                                wrapper.appendChild(group);
                            });
                            col.appendChild(wrapper);
                        } else {
                            const input = document.createElement('input');
                            input.type = attribute.attr_type === 'NUMBER' ? 'number' : 'text';
                            if (attribute.attr_type === 'NUMBER') input.step = 'any';
                            input.name = fieldName;
                            input.className = 'form-control form-control-sm';
                            input.value = savedValue;
                            if (attribute.is_required) input.required = true;
                            col.appendChild(input);
                        }
                        fields.appendChild(col);
                    });
                })
                .catch(error => {
                    console.error(error);
                    notice.innerHTML = '<i class="bi bi-exclamation-triangle text-danger me-1"></i>Failed to load attributes.';
                });
        }

        validatePrices();
        const initialCategory = parseInt(savedItem.mapped_category_id, 10) || 0;
        if (initialCategory) {
            category.value = initialCategory;
            loadHierarchy(
                parseInt(savedItem.mapped_subcategory_id, 10) || 0,
                parseInt(savedItem.mapped_sub_subcategory_id, 10) || 0
            );
        }
    });
});
</script>
