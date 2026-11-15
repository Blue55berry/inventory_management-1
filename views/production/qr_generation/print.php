<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($batch['brand_name']) ?> — QR Labels (<?= count($batch['items']) ?> pcs)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .print-sheet-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .labels-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 1100px) {
            .labels-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            }
        }

        /* 50x30mm Standard Garment Thermal Sticker Card */
        .garment-label-card {
            background: #fff;
            border: 2px solid #000;
            border-radius: 6px;
            padding: 8px 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .label-header {
            border-bottom: 1.5px solid #000;
            padding-bottom: 3px;
            margin-bottom: 5px;
            text-align: center;
        }

        /* GIVEN BRAND NAME PROMINENTLY PRINTED AT TOP */
        .brand-name-title {
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #000;
            line-height: 1.2;
        }

        .hierarchy-sub {
            font-size: 8.5px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            margin-top: 1px;
        }

        .qr-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qr-canvas-box {
            width: 72px;
            height: 72px;
            flex-shrink: 0;
        }

        .qr-canvas-box canvas, .qr-canvas-box img {
            width: 72px !important;
            height: 72px !important;
        }

        .label-meta {
            font-size: 9.5px;
            line-height: 1.35;
        }

        .label-meta strong {
            font-weight: 800;
        }

        .label-footer {
            border-top: 1.5px solid #000;
            padding-top: 3px;
            margin-top: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            font-weight: 800;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .garment-label-card {
                box-shadow: none;
                border: 1.5px solid #000;
            }
            .labels-grid {
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="print-sheet-container">
        <!-- Control Header (hidden during print) -->
        <div class="no-print d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm border">
            <div>
                <h5 class="mb-0 fw-bold"><i class="bi bi-printer me-2 text-primary"></i>Batch Print Thermal Labels</h5>
                <small class="text-muted">
                    Batch: <strong><?= e($batch['batch_code']) ?></strong> · Brand: <strong class="text-uppercase"><?= e($batch['brand_name']) ?></strong> · Total: <strong><?= count($batch['items']) ?> labels</strong>
                </small>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-primary fw-bold">
                    <i class="bi bi-printer-fill me-1"></i> Print All <?= count($batch['items']) ?> Labels
                </button>
                <button onclick="window.close()" class="btn btn-outline-secondary">
                    Close
                </button>
            </div>
        </div>

        <!-- Labels Grid -->
        <div class="labels-grid">
            <?php foreach ($batch['items'] as $item): ?>
            <div class="garment-label-card">
                <!-- Top Brand Header: Given Brand Name Print -->
                <div class="label-header">
                    <div class="brand-name-title"><?= e($batch['brand_name']) ?></div>
                    <div class="hierarchy-sub">
                        <?= e($batch['category_name'] ?: 'APPAREL') ?>
                        <?php if (!empty($batch['subcategory_name'])): ?>
                        › <?= e($batch['subcategory_name']) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Middle: QR and Attributes -->
                <div class="qr-row">
                    <div class="qr-canvas-box print-qr-item" data-code="<?= e($item['qr_code']) ?>"></div>
                    <div class="label-meta flex-grow-1">
                        <div class="font-monospace fw-bold text-dark" style="font-size: 10px; word-break: break-all;">
                            <?= e($item['qr_code']) ?>
                        </div>
                        <?php if (!empty($batch['color_name'])): ?>
                        <div><strong>Col:</strong> <?= e($batch['color_name']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($batch['design_name'])): ?>
                        <div><strong>Des:</strong> <?= e($batch['design_name']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($batch['size_name'])): ?>
                        <div><strong>Size:</strong> <span class="badge bg-dark text-white px-1 py-0"><?= e($batch['size_name']) ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Footer: Pricing -->
                <div class="label-footer">
                    <?php if ((float)$batch['mrp'] > 0): ?>
                    <div class="text-muted">MRP: ₹<?= number_format((float)$batch['mrp'], 2) ?></div>
                    <?php endif; ?>
                    <div class="text-dark">PRICE: ₹<?= number_format((float)$batch['selling_price'], 2) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.print-qr-item').forEach(function(el) {
                new QRCode(el, {
                    text: el.dataset.code,
                    width: 72,
                    height: 72,
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        });
    </script>
</body>
</html>
