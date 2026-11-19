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
    </div>
    <div class="d-flex gap-2 flex-wrap">
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
                <div>
                    <h5 class="mb-0 fw-bold"><i class="bi bi-list-check me-2 text-success"></i>Product Lines &amp; Catalog Mapping</h5>
                    <span class="small text-muted">Category is required. MRP and selling price are saved with the mapped catalog variant.</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= url('production/third-party-catalog') ?>" target="_blank" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-collection"></i> Third-Party Catalog
                    </a>
                    <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-xs" data-bs-toggle="modal" data-bs-target="#modalAddBillProduct">
                        <i class="bi bi-plus-circle-fill"></i> Add Product Line to Bill
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 bill-mapping-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;">#</th>
                                <th style="min-width:230px;">Product / Bill Line</th>
                                <th class="text-end" style="min-width:90px;">Bill Qty</th>
                                <th class="text-end" style="min-width:100px;">Purchase Rate</th>
                                <th class="text-end" style="min-width:110px;">Line Amount</th>
                                <th class="text-end" style="min-width:110px;">MRP (₹) *</th>
                                <th class="text-end" style="min-width:110px;">Selling (₹) *</th>
                                <th style="min-width:165px;">Category *</th>
                                <th style="min-width:165px;">Subcategory</th>
                                <th style="min-width:165px;">Sub-Subcategory</th>
                                <th class="text-center" style="min-width:105px;">Attributes</th>
                                <th style="min-width:130px;">QR / GRN</th>
                                <th class="text-center" style="min-width:70px;">Compl.</th>
                                <th class="text-center" style="width:50px;">Action</th>
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
                                    <td>
                                        <select name="items[<?= $itemId ?>][subcategory_id]" id="subcategory_<?= $itemId ?>"
                                                class="form-select form-select-sm" data-subcategory disabled>
                                            <option value="">-- Select Subcategory --</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="items[<?= $itemId ?>][sub_subcategory_id]" id="subsubcategory_<?= $itemId ?>"
                                                class="form-select form-select-sm" data-subsubcategory disabled>
                                            <option value="">-- Select Sub-Subcategory --</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" data-toggle-attributes="<?= $itemId ?>"
                                                title="View / Edit Attributes &amp; Specifications">
                                            <i class="bi bi-sliders"></i>Specs
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
                                    <td class="text-center">
                                        <?php if (empty($item['is_mapped'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger p-1" title="Remove line from purchase bill" onclick="deleteBillProduct(<?= $itemId ?>, '<?= e(addslashes($item['item_name'])) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <tr id="attr_row_<?= $itemId ?>" class="d-none" data-attribute-row="<?= $itemId ?>">
                                    <td colspan="14" class="bg-light p-3">
                                        <div class="border rounded-3 p-3 bg-white shadow-xs">
                                            <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                                                <h6 class="fw-bold mb-0 text-dark">
                                                    <i class="bi bi-sliders text-success me-1.5"></i>Category Attributes &amp; Specifications for <span class="text-primary"><?= e($item['item_name']) ?></span>
                                                </h6>
                                                <button type="button" class="btn-close btn-sm" aria-label="Close" onclick="document.getElementById('attr_row_<?= $itemId ?>').classList.add('d-none')"></button>
                                            </div>
                                            <div data-attributes-notice class="text-muted small text-center py-2">
                                                <i class="bi bi-info-circle me-1"></i>Select a category above to load configured specifications.
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
                                <th colspan="9" class="text-start">
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

<!-- ========================================================================= -->
<!-- MODAL: ADD PRODUCT LINE DIRECTLY TO PURCHASE BILL -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalAddBillProduct" tabindex="-1" aria-labelledby="modalAddBillProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" id="formAddBillProduct" onsubmit="submitAddBillProduct(event)">
            <?= CSRF::field() ?>
            <input type="hidden" name="purchase_id" value="<?= (int)$purchase['id'] ?>">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title fw-bold" id="modalAddBillProductLabel">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Add Product Line to Purchase Bill
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="addBillProductAlert" class="alert alert-danger d-none py-2 px-3 small"></div>

                <div class="row g-3 mb-3">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold small">Product / Item Name <span class="text-danger">*</span></label>
                        <input type="text" name="new_item_name" id="new_item_name" class="form-control" placeholder="e.g. Men's Cotton Polo T-Shirt" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small">Vendor SKU / Code</label>
                        <input type="text" name="new_vendor_sku" id="new_vendor_sku" class="form-control font-monospace text-uppercase" placeholder="e.g. TP-POLO-NAVY-M">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Color / Shade</label>
                        <input type="text" name="new_color" id="new_color" class="form-control" placeholder="e.g. Navy Blue">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Size</label>
                        <input type="text" name="new_size" id="new_size" class="form-control font-monospace" placeholder="e.g. M / 38 / Free Size">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">HSN Code</label>
                        <input type="text" name="new_hsn_code" id="new_hsn_code" class="form-control font-monospace" placeholder="e.g. 61091000">
                    </div>
                </div>

                <div class="card bg-light border-0 p-3 mb-3">
                    <h6 class="fw-bold mb-2 small text-dark"><i class="bi bi-currency-rupee text-success me-1"></i>Pricing, Quantity &amp; Taxes</h6>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Bill Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="new_quantity" id="new_quantity" class="form-control font-monospace text-end" min="0.01" step="0.01" value="1.00" required oninput="calcNewProductTotals()">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Unit</label>
                            <select name="new_unit" id="new_unit" class="form-select">
                                <option value="PCS" selected>PCS</option>
                                <option value="MTR">MTR</option>
                                <option value="KGS">KGS</option>
                                <option value="SET">SET</option>
                                <option value="BOX">BOX</option>
                                <option value="DOZEN">DOZEN</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Purchase Rate (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="new_unit_price" id="new_unit_price" class="form-control font-monospace text-end" min="0.01" step="0.01" placeholder="0.00" required oninput="calcNewProductTotals()">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">MRP (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="new_mrp" id="new_mrp" class="form-control font-monospace text-end" min="0.01" step="0.01" placeholder="0.00" required oninput="calcNewProductTotals()">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Selling (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="new_selling_price" id="new_selling_price" class="form-control font-monospace text-end" min="0.01" step="0.01" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="row g-2 mt-2 align-items-center">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">GST Tax Rate</label>
                            <select name="new_gst_rate" id="new_gst_rate" class="form-select form-select-sm" onchange="calcNewProductTotals()">
                                <option value="5" selected>5% GST (Standard Garments)</option>
                                <option value="12">12% GST</option>
                                <option value="18">18% GST</option>
                                <option value="0">0% (Exempted)</option>
                            </select>
                        </div>
                        <div class="col-md-8 text-end">
                            <span class="small text-muted me-3">Base: <strong id="newProductBaseDisplay">₹0.00</strong></span>
                            <span class="small text-muted me-3">Tax: <strong id="newProductTaxDisplay">₹0.00</strong></span>
                            <span class="fw-bold text-dark">Line Total: <span class="text-success fs-6" id="newProductTotalDisplay">₹0.00</span></span>
                        </div>
                    </div>
                </div>

                <div class="card border border-primary-subtle bg-primary-subtle bg-opacity-10 p-3 mb-3">
                    <h6 class="fw-bold mb-2 small text-primary"><i class="bi bi-diagram-3 me-1"></i>Catalog Category Assignment</h6>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
                            <select name="new_category_id" id="new_category_id" class="form-select form-select-sm" required onchange="loadNewProductSubcategories()">
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Subcategory</label>
                            <select name="new_subcategory_id" id="new_subcategory_id" class="form-select form-select-sm" disabled onchange="loadNewProductSubSubcategories()">
                                <option value="">-- Select Subcategory --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Sub-Subcategory</label>
                            <select name="new_sub_subcategory_id" id="new_sub_subcategory_id" class="form-select form-select-sm" disabled>
                                <option value="">-- Select Sub-Subcategory --</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="new_is_complementary" id="new_is_complementary" value="1">
                    <label class="form-check-label small" for="new_is_complementary">
                        This is a complementary / gift product (packed with main product, does not affect primary pricing)
                    </label>
                </div>
            </div>
            <div class="modal-footer bg-light py-2.5">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitNewBillProduct">
                    <i class="bi bi-check2-circle me-1"></i> Add to Purchase Bill
                </button>
            </div>
        </form>
    </div>
</div>

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
        });
    });

    document.querySelectorAll('[data-mapping-item]').forEach(row => {
        const itemId = row.dataset.itemId;
        const savedItem = itemData.find(item => String(item.id) === itemId) || {};
        const detailRow = document.getElementById('attr_row_' + itemId);
        const category = row.querySelector('[data-category]');
        const subcategory = row.querySelector('[data-subcategory]');
        const subsubcategory = row.querySelector('[data-subsubcategory]');
        const notice = detailRow ? detailRow.querySelector('[data-attributes-notice]') : null;
        const fields = detailRow ? detailRow.querySelector('[data-attributes-fields]') : null;
        const mrp = row.querySelector(`#mrp_${itemId}`);
        const sellingPrice = row.querySelector(`#selling_price_${itemId}`);

        function validatePrices() {
            if (!mrp || !sellingPrice) return;
            const mrpValue = Number(mrp.value);
            const sellingValue = Number(sellingPrice.value);
            sellingPrice.setCustomValidity(
                sellingValue > 0 && mrpValue > 0 && sellingValue > mrpValue
                    ? 'Selling price cannot be greater than MRP.'
                    : ''
            );
        }

        if (mrp) mrp.addEventListener('input', validatePrices);
        if (sellingPrice) sellingPrice.addEventListener('input', validatePrices);

        if (category) {
            category.addEventListener('change', () => loadHierarchy(0, 0));
        }
        if (subcategory) {
            subcategory.addEventListener('change', () => loadSubSubcategories(0));
        }
        if (subsubcategory) {
            subsubcategory.addEventListener('change', () => {
                loadAttributes(parseInt(category.value, 10) || 0, parseInt(subcategory.value, 10) || 0, parseInt(subsubcategory.value, 10) || 0);
            });
        }

        function loadHierarchy(preselectedSubcategory, preselectedSubSubcategory) {
            const categoryId = parseInt(category.value, 10) || 0;
            if (subcategory) {
                subcategory.innerHTML = '<option value="">-- Select Subcategory --</option>';
                subcategory.disabled = true;
            }
            if (subsubcategory) {
                subsubcategory.innerHTML = '<option value="">-- Select Sub-Subcategory --</option>';
                subsubcategory.disabled = true;
            }

            if (!categoryId) {
                if (notice) {
                    notice.classList.remove('d-none');
                    notice.innerHTML = '<i class="bi bi-info-circle me-1"></i>Select a category above to load configured specifications.';
                }
                if (fields) {
                    fields.classList.add('d-none');
                    fields.innerHTML = '';
                }
                return;
            }

            fetch('<?= url("api/subcategories") ?>/' + categoryId)
                .then(response => response.json())
                .then(data => {
                    if (!Array.isArray(data) || !subcategory) return;
                    data.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name + (sub.code ? ' (' + sub.code + ')' : '');
                        option.selected = Number(sub.id) === Number(preselectedSubcategory);
                        subcategory.appendChild(option);
                    });
                    if (data.length > 0) {
                        subcategory.disabled = false;
                    } else {
                        subcategory.innerHTML = '<option value="">-- No Subcategories --</option>';
                        subcategory.disabled = true;
                    }
                    if (preselectedSubcategory) {
                        loadSubSubcategories(preselectedSubSubcategory);
                    }
                })
                .catch(error => console.error(error));

            loadAttributes(categoryId, preselectedSubcategory, preselectedSubSubcategory);
        }

        function loadSubSubcategories(preselectedSubSubcategory) {
            const subcategoryId = subcategory ? (parseInt(subcategory.value, 10) || 0) : 0;
            if (subsubcategory) {
                subsubcategory.innerHTML = '<option value="">-- Select Sub-Subcategory --</option>';
                subsubcategory.disabled = true;
            }
            if (!subcategoryId) {
                loadAttributes(parseInt(category.value, 10) || 0, 0, 0);
                return;
            }

            fetch('<?= url("api/sub-subcategories") ?>/' + subcategoryId)
                .then(response => response.json())
                .then(data => {
                    if (!Array.isArray(data) || !subsubcategory) return;
                    data.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name + (sub.code ? ' (' + sub.code + ')' : '');
                        option.selected = Number(sub.id) === Number(preselectedSubSubcategory);
                        subsubcategory.appendChild(option);
                    });
                    if (data.length > 0) {
                        subsubcategory.disabled = false;
                    } else {
                        subsubcategory.innerHTML = '<option value="">-- No Sub-Subcategories --</option>';
                        subsubcategory.disabled = true;
                    }
                })
                .catch(error => console.error(error));

            loadAttributes(parseInt(category.value, 10) || 0, subcategoryId, preselectedSubSubcategory);
        }

        function loadAttributes(categoryId, subcategoryId, subSubcategoryId) {
            if (!categoryId || !notice || !fields) return;
            notice.classList.remove('d-none');
            notice.innerHTML = '<span class="spinner-border spinner-border-sm text-primary me-2" role="status"></span>Loading category attributes...';
            fields.classList.add('d-none');
            fields.innerHTML = '';

            let endpoint = '<?= url("api/categories/attributes") ?>/' + categoryId;
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
        if (initialCategory && category) {
            category.value = initialCategory;
            loadHierarchy(
                parseInt(savedItem.mapped_subcategory_id, 10) || 0,
                parseInt(savedItem.mapped_sub_subcategory_id, 10) || 0
            );
        }
    });
});

// Modal: Calculate live totals for new product
function calcNewProductTotals() {
    const qty = parseFloat(document.getElementById('new_quantity').value) || 0;
    const rate = parseFloat(document.getElementById('new_unit_price').value) || 0;
    const gstRate = parseFloat(document.getElementById('new_gst_rate').value) || 0;

    const base = qty * rate;
    const tax = base * (gstRate / 100);
    const total = base + tax;

    document.getElementById('newProductBaseDisplay').textContent = '₹' + base.toFixed(2);
    document.getElementById('newProductTaxDisplay').textContent = '₹' + tax.toFixed(2);
    document.getElementById('newProductTotalDisplay').textContent = '₹' + total.toFixed(2);

    // Auto-suggest selling price if empty
    const mrpInput = document.getElementById('new_mrp');
    const spInput = document.getElementById('new_selling_price');
    if (mrpInput && mrpInput.value && (!spInput.value || parseFloat(spInput.value) <= 0)) {
        spInput.value = mrpInput.value;
    }
}

// Modal: Load Subcategories for new product
function loadNewProductSubcategories() {
    const catId = document.getElementById('new_category_id').value;
    const subSelect = document.getElementById('new_subcategory_id');
    const subSubSelect = document.getElementById('new_sub_subcategory_id');

    subSelect.innerHTML = '<option value="">-- Select Subcategory --</option>';
    subSelect.disabled = true;
    subSubSelect.innerHTML = '<option value="">-- Select Sub-Subcategory --</option>';
    subSubSelect.disabled = true;

    if (!catId) return;

    fetch('<?= url("api/subcategories") ?>/' + catId)
        .then(res => res.json())
        .then(data => {
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub.id;
                    opt.textContent = sub.name + (sub.code ? ' (' + sub.code + ')' : '');
                    subSelect.appendChild(opt);
                });
                subSelect.disabled = false;
            } else {
                subSelect.innerHTML = '<option value="">-- No Subcategories --</option>';
            }
        })
        .catch(err => console.error(err));
}

// Modal: Load Sub-Subcategories for new product
function loadNewProductSubSubcategories() {
    const subId = document.getElementById('new_subcategory_id').value;
    const subSubSelect = document.getElementById('new_sub_subcategory_id');

    subSubSelect.innerHTML = '<option value="">-- Select Sub-Subcategory --</option>';
    subSubSelect.disabled = true;

    if (!subId) return;

    fetch('<?= url("api/sub-subcategories") ?>/' + subId)
        .then(res => res.json())
        .then(data => {
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub.id;
                    opt.textContent = sub.name + (sub.code ? ' (' + sub.code + ')' : '');
                    subSubSelect.appendChild(opt);
                });
                subSubSelect.disabled = false;
            } else {
                subSubSelect.innerHTML = '<option value="">-- No Sub-Subcategories --</option>';
            }
        })
        .catch(err => console.error(err));
}

// Submit Modal form to add product to bill
async function submitAddBillProduct(e) {
    e.preventDefault();
    const alertBox = document.getElementById('addBillProductAlert');
    const btn = document.getElementById('btnSubmitNewBillProduct');
    alertBox.classList.add('d-none');
    alertBox.textContent = '';

    const mrp = parseFloat(document.getElementById('new_mrp').value) || 0;
    const sp = parseFloat(document.getElementById('new_selling_price').value) || 0;
    if (sp > mrp) {
        alertBox.textContent = 'Selling price (₹' + sp.toFixed(2) + ') cannot exceed MRP (₹' + mrp.toFixed(2) + ').';
        alertBox.classList.remove('d-none');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Adding...';

    const formData = new FormData(document.getElementById('formAddBillProduct'));

    try {
        const resp = await fetch('<?= url("purchases/mapping/add-product") ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await resp.json();

        if (result.success) {
            // Reload page to reflect updated bill items and totals
            window.location.reload();
        } else {
            alertBox.textContent = result.message || 'Error adding product.';
            alertBox.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Add to Purchase Bill';
        }
    } catch (err) {
        alertBox.textContent = 'Network or server error: ' + err.message;
        alertBox.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Add to Purchase Bill';
    }
}

// Delete an unmapped product from the bill
async function deleteBillProduct(itemId, itemName) {
    if (!confirm('Are you sure you want to remove product "' + itemName + '" from this purchase bill?')) {
        return;
    }

    try {
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);

        const resp = await fetch('<?= url("purchases/mapping/delete-product") ?>/' + itemId, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await resp.json();

        if (result.success) {
            window.location.reload();
        } else {
            alert(result.message || 'Error removing product line.');
        }
    } catch (err) {
        alert('Error: ' + err.message);
    }
}
</script>
