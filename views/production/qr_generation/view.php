<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h2 class="mb-1"><i class="bi bi-qr-code me-2 text-primary"></i>Batch <?= e($batch['batch_code']) ?></h2>
            <?php if ($batch['generation_type'] === 'MANUAL'): ?>
            <span class="badge bg-info-subtle text-info border border-info-subtle">Manual Custom</span>
            <?php else: ?>
            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">Catalog Linked</span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0">Brand: <strong class="text-dark"><?= e($batch['brand_name']) ?></strong> · Generated <?= count($batch['items']) ?> QR Codes</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('production/qr-generations/print/' . $batch['id']) ?>" target="_blank" class="btn btn-primary fw-bold">
            <i class="bi bi-printer-fill me-1"></i> Print All <?= count($batch['items']) ?> Labels
        </a>
        <a href="<?= url('production/qr-generations') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<!-- Batch Metadata Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small fw-semibold text-uppercase">Printed Brand Name</div>
                <div class="fs-5 fw-bold text-dark mt-1 text-uppercase"><?= e($batch['brand_name']) ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small fw-semibold text-uppercase">Category Hierarchy</div>
                <div class="fw-semibold text-dark mt-1">
                    <?= e($batch['category_name'] ?: 'General') ?>
                    <?php if (!empty($batch['subcategory_name'])): ?>
                    › <?= e($batch['subcategory_name']) ?>
                    <?php endif; ?>
                    <?php if (!empty($batch['sub_subcategory_name'])): ?>
                    › <?= e($batch['sub_subcategory_name']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small fw-semibold text-uppercase">QR Code Prefix</div>
                <div class="font-monospace fw-bold text-primary mt-1"><?= e($batch['qr_prefix']) ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small fw-semibold text-uppercase">Pricing on Label</div>
                <div class="mt-1">
                    <span class="text-muted text-decoration-line-through">₹<?= number_format((float)$batch['mrp'], 2) ?></span>
                    <strong class="text-success ms-2 fs-6">₹<?= number_format((float)$batch['selling_price'], 2) ?></strong>
                </div>
            </div>
            <?php if (!empty($batch['variant_id']) || !empty($batch['variant_code']) || !empty($batch['variant_sku'])): ?>
            <div class="col-12 pt-3 border-top d-flex gap-3 flex-wrap align-items-center">
                <span class="badge bg-primary fs-6 font-monospace">Variant ID: <?= e($batch['variant_code'] ?? ('V' . $batch['variant_id'])) ?></span>
                <span class="badge bg-dark-subtle text-dark border font-monospace">SKU: <?= e($batch['variant_sku'] ?? '—') ?></span>
                <?php if (!empty($batch['color_name'])): ?><span class="badge bg-light text-dark border">Color: <?= e($batch['color_name']) ?></span><?php endif; ?>
                <?php if (!empty($batch['design_name'])): ?><span class="badge bg-light text-dark border">Design: <?= e($batch['design_name']) ?></span><?php endif; ?>
                <?php if (!empty($batch['size_name'])): ?><span class="badge bg-light text-dark border">Size: <?= e($batch['size_name']) ?></span><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Generated QR Code Items Grid -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Generated QR Items (<?= count($batch['items']) ?>)</h5>
        <a href="<?= url('production/qr-generations/print/' . $batch['id']) ?>" target="_blank" class="btn btn-sm btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Thermal Sheet
        </a>
    </div>
    <div class="card-body p-4">
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-3">
            <?php foreach ($batch['items'] as $item): ?>
            <div class="col">
                <div class="p-3 border rounded bg-white text-center shadow-sm h-100 d-flex flex-direction-column justify-content-between align-items-center">
                    <div class="w-100">
                        <div class="fw-bold text-uppercase small text-dark pb-1 border-bottom text-truncate">
                            <?= e($batch['brand_name']) ?>
                        </div>
                        <div class="d-flex justify-content-center my-2">
                            <div class="batch-qr-canvas p-1 bg-white border rounded" data-code="<?= e($item['qr_code']) ?>"></div>
                        </div>
                        <div class="font-monospace fw-bold text-primary text-truncate" style="font-size: 11px;">
                            <?= e($item['qr_code']) ?>
                        </div>
                    </div>
                    <div class="w-100 d-flex justify-content-between align-items-center mt-2 pt-1 border-top small">
                        <span class="badge bg-light text-muted border">#<?= $item['sequence_num'] ?></span>
                        <span class="fw-bold text-success">₹<?= number_format((float)$batch['selling_price'], 2) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.batch-qr-canvas').forEach(function(el) {
        new QRCode(el, {
            text: el.dataset.code,
            width: 80,
            height: 80,
            correctLevel: QRCode.CorrectLevel.M
        });
    });
});
</script>
