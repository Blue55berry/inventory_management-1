<?php
/** Third-Party QR generation: brand name is entered empty by the admin. */
$purchaseData = json_encode(array_values($purchases), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="bi bi-box-seam me-2 text-info"></i>Generate Third-Party Product QR Batch
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= url('production/qr-generations/store-purchase') ?>" id="formThirdPartyQr">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="third_party">
                    <input type="hidden" name="qr_source" value="<?= e(QrGenerationService::SOURCE_THIRD_PARTY) ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Purchase Bill <span class="text-danger">*</span></label>
                            <select name="purchase_id" id="tpPurchase" class="form-select form-select-lg" required onchange="onTpPurchaseChanged()">
                                <option value="">-- Select Purchase Bill --</option>
                                <?php foreach ($purchases as $purchase): ?>
                                    <option value="<?= (int)$purchase['id'] ?>">
                                        <?= e($purchase['purchase_number']) ?> · <?= e(date('d M Y', strtotime($purchase['purchase_date']))) ?> · <?= e($purchase['seller_label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Third-Party Product <span class="text-danger">*</span></label>
                            <select name="purchase_item_id" id="tpItem" class="form-select form-select-lg" required onchange="onTpItemChanged()">
                                <option value="">-- Select product after choosing a bill --</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4 pb-3 border-bottom">
                        <label class="form-label fw-bold text-dark fs-6">
                            Brand Name to Show on QR <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light text-info"><i class="bi bi-award-fill"></i></span>
                            <input type="text" name="brand_name" id="tpBrandName" class="form-control fw-bold"
                                   placeholder="Type the brand exactly as it should appear on the QR label"
                                   value="" required maxlength="150" oninput="updateTpPreview()">
                        </div>
                        <div class="form-text small">Field starts empty. The text you type is printed exactly as entered on every QR label.</div>
                    </div>

                    <div class="alert alert-light border mb-4" id="tpSummary">
                        <div class="small text-muted">Select a purchased product to view mapping, quantity and GRN status.</div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Notes (optional)</label>
                            <input type="text" name="notes" class="form-control" placeholder="e.g. Batch for retail packing">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-info btn-lg w-100 fw-bold text-dark">
                                <i class="bi bi-qr-code-scan me-2"></i> Generate QR Batch
                            </button>
                        </div>
                    </div>

                    <div class="border-top pt-3 small text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Quantity is taken automatically from the purchase line. Map the product first so the QR uses its category hierarchy and variant identity.
                    </div>
                </form>
            </div>
        </div>

        <?php require __DIR__ . '/_legacy_forms.php'; ?>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
            <div class="card-header bg-dark text-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-eye-fill me-2 text-warning"></i>Third-Party Label Preview</h6>
            </div>
            <div class="card-body p-4 text-center">
                <div class="thermal-preview-card p-3 border border-dark rounded bg-white shadow-sm mx-auto text-center" style="max-width: 280px; border-width: 2px !important;">
                    <div class="fw-bold text-uppercase fs-6 pb-1 border-bottom border-dark text-truncate" id="tpStickerBrand">YOUR BRAND</div>
                    <div class="small fw-semibold text-muted text-uppercase mt-1" id="tpStickerItem">THIRD-PARTY PRODUCT</div>
                    <div class="d-flex justify-content-center my-2">
                        <div id="tpStickerQrBox" class="p-1 bg-white border rounded"></div>
                    </div>
                    <div class="font-monospace fw-bold text-dark" style="font-size: 11px;" id="tpStickerCode">BRAND-SKU-0001</div>
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-1 border-top border-dark small fw-bold">
                        <div>MRP: ₹<span id="tpStickerMrp">0.00</span></div>
                        <div class="text-primary">SALE: ₹<span id="tpStickerPrice">0.00</span></div>
                    </div>
                </div>
                <div class="alert alert-light border mt-3 text-start small text-muted mb-0">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                    The brand text above is printed exactly as typed in the form.
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="tpPurchaseData"><?= $purchaseData ?></script>
<script>
(function () {
    const node = document.getElementById('tpPurchaseData');
    window.tpPurchases = node ? JSON.parse(node.textContent || '[]') : [];
})();

function onTpPurchaseChanged() {
    const purchaseId = document.getElementById('tpPurchase').value;
    const itemSelect = document.getElementById('tpItem');
    const summary = document.getElementById('tpSummary');
    itemSelect.innerHTML = '<option value="">-- Select product --</option>';
    summary.innerHTML = '<div class="small text-muted">Select a purchased product to view mapping, quantity and GRN status.</div>';

    const purchase = window.tpPurchases.find(p => String(p.id) === String(purchaseId));
    if (!purchase) return;
    (purchase.items || []).forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.item_id;
        opt.textContent = item.item_name + ' — ' + Number(item.quantity).toLocaleString() + ' ' + (item.unit || 'pcs') + (item.is_mapped ? ' [Mapped]' : ' [Not mapped]');
        opt.dataset.item = JSON.stringify(item);
        itemSelect.appendChild(opt);
    });
}

function onTpItemChanged() {
    const select = document.getElementById('tpItem');
    const summary = document.getElementById('tpSummary');
    const option = select.options[select.selectedIndex];
    const raw = option && option.dataset.item;
    if (!raw) {
        summary.innerHTML = '<div class="small text-muted">Select a purchased product to view mapping, quantity and GRN status.</div>';
        updateTpPreview();
        return;
    }
    const item = JSON.parse(raw);
    const mapLink = '<a href="<?= url('purchases/mapping?search=') ?>' + encodeURIComponent(item.item_name || '') + '" class="btn btn-sm btn-warning text-dark ms-2"><i class="bi bi-diagram-3-fill me-1"></i>Map Now</a>';
    const status = item.is_mapped
        ? '<span class="badge bg-success-subtle text-success border"><i class="bi bi-check2-circle me-1"></i>Mapped</span>'
        : '<span class="badge bg-warning-subtle text-warning border"><i class="bi bi-clock-history me-1"></i>Not mapped</span>' + mapLink;
    const qr = item.qr_batch_code
        ? '<a href="<?= url('production/qr-generations/view/') ?>' + Number(item.qr_generation_id) + '" class="badge bg-dark-subtle text-dark border text-decoration-none"><i class="bi bi-qr-code me-1"></i>' + item.qr_batch_code + '</a>'
        : '<span class="badge bg-secondary-subtle text-secondary border">No QR batch</span>';
    const grn = item.grn_number
        ? '<a href="<?= url('production/inwards/view/') ?>' + Number(item.grn_id || 0) + '" class="badge bg-primary-subtle text-primary border text-decoration-none"><i class="bi bi-truck me-1"></i>' + item.grn_number + '</a>'
        : '<span class="badge bg-secondary-subtle text-secondary border">No GRN</span>';

    summary.innerHTML =
        '<div class="row g-2 small align-items-center">' +
            '<div class="col-md-4"><span class="text-muted">Product</span><div class="fw-bold">' + (item.item_name || '') + '</div></div>' +
            '<div class="col-md-2"><span class="text-muted">Qty</span><div class="fw-bold text-primary">' + Number(item.quantity).toLocaleString() + ' ' + (item.unit || 'pcs') + '</div></div>' +
            '<div class="col-md-2"><span class="text-muted">SKU</span><div class="fw-bold font-monospace">' + (item.vendor_sku || item.product_code || '—') + '</div></div>' +
            '<div class="col-md-4"><span class="text-muted">Status</span><div>' + status + '</div></div>' +
            '<div class="col-md-4 mt-2"><span class="text-muted">QR / GRN</span><div>' + qr + ' ' + grn + '</div></div>' +
        '</div>';
    window.tpCurrentItem = item;
    updateTpPreview();
}

function updateTpPreview() {
    const brand = (document.getElementById('tpBrandName').value || '').trim();
    document.getElementById('tpStickerBrand').textContent = brand || 'YOUR BRAND';
    const item = window.tpCurrentItem || {};
    document.getElementById('tpStickerItem').textContent = (item.item_name || 'THIRD-PARTY PRODUCT').toUpperCase();
    document.getElementById('tpStickerMrp').textContent = Number(item.mrp || 0).toFixed(2);
    document.getElementById('tpStickerPrice').textContent = Number(item.selling_price || 0).toFixed(2);
    document.getElementById('tpStickerCode').textContent = (brand ? brand.replace(/[^A-Za-z0-9]/g, '').substr(0, 4).toUpperCase() : 'BRAND') + '-SKU-0001';

    const qrBox = document.getElementById('tpStickerQrBox');
    if (!qrBox || typeof QRCode === 'undefined') return;
    qrBox.innerHTML = '';
    new QRCode(qrBox, {
        text: document.getElementById('tpStickerCode').textContent,
        width: 90,
        height: 90,
        correctLevel: QRCode.CorrectLevel.M
    });
}
</script>
