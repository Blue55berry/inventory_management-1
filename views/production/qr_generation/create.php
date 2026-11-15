<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-qr-code me-2 text-primary"></i>Generate QR Code Batch</h2>
        <p class="text-muted small mb-0">Generate bulk scannable QR labels with your custom brand name and category code structure.</p>
    </div>
    <div>
        <a href="<?= url('production/qr-generations') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Generations List
        </a>
    </div>
</div>

<!-- Tabs: Manual vs Catalog Linked -->
<ul class="nav nav-tabs nav-fill mb-4 border-bottom-0 gap-2" id="generatorTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold py-3 border shadow-sm rounded" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manualMode" type="button" role="tab">
            <i class="bi bi-pencil-square me-2 text-primary fs-5"></i>Option 1: Manual QR Generation (Custom Hierarchy &amp; Brand)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold py-3 border shadow-sm rounded" id="catalog-tab" data-bs-toggle="tab" data-bs-target="#catalogMode" type="button" role="tab">
            <i class="bi bi-journal-bookmark me-2 text-warning fs-5"></i>Option 2: From Existing Catalog / Product Variant
        </button>
    </li>
</ul>

<div class="tab-content" id="generatorTabsContent">
    <!-- OPTION 1: MANUAL QR GENERATOR -->
    <div class="tab-pane fade show active" id="manualMode" role="tabpanel">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="bi bi-sliders me-2 text-primary"></i>Manual Generation Parameters
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="<?= url('production/qr-generations/store-manual') ?>" id="formManualQr">
                            <?= csrf_field() ?>

                            <!-- Brand Name (Mandatory) -->
                            <div class="mb-4 pb-3 border-bottom">
                                <label class="form-label fw-bold text-dark fs-6">
                                    Brand Name to Print on Labels <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light text-primary"><i class="bi bi-award-fill"></i></span>
                                    <input type="text" name="brand_name" id="manBrandName" class="form-control text-uppercase fw-bold" placeholder="e.g. GRM APPARELS, MEKHA, RMT" value="GRM APPARELS" required oninput="updateManualPreview()">
                                </div>
                                <div class="form-text small">This exact brand name will be printed prominently at the top of every generated QR label sticker.</div>
                            </div>

                            <!-- Category Hierarchy (Select or Quick Create) -->
                            <div class="mb-4 pb-3 border-bottom">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-diagram-3 me-2 text-primary"></i>Category Hierarchy &amp; Short Codes</h6>
                                
                                <div class="row g-3 mb-3">
                                    <!-- Category -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                                        <select name="category_id" id="manCategoryId" class="form-select" onchange="onManualCategoryChanged()">
                                            <option value="">-- Select Existing Category --</option>
                                            <?php foreach ($categories as $c): ?>
                                            <option value="<?= $c['id'] ?>" data-code="<?= e($c['code'] ?? '') ?>"><?= e($c['name']) ?> (<?= e($c['code'] ?? '—') ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-semibold">...or Create New Category Name</label>
                                        <input type="text" name="new_category_name" id="manNewCatName" class="form-control" placeholder="e.g. Kids Wear, Men's Wear" oninput="updateManualPreview()">
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <!-- Subcategory -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Subcategory</label>
                                        <select name="subcategory_id" id="manSubcategoryId" class="form-select" onchange="onManualSubcategoryChanged()">
                                            <option value="">-- Select Existing Subcategory --</option>
                                            <?php foreach ($subcategories as $sc): ?>
                                            <option value="<?= $sc['id'] ?>" data-cat-id="<?= $sc['category_id'] ?>" data-code="<?= e($sc['code'] ?? '') ?>"><?= e($sc['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-semibold">...or Create New Subcategory Name</label>
                                        <input type="text" name="new_subcategory_name" id="manNewSubcatName" class="form-control" placeholder="e.g. T-Shirt, Shirt, Jeans" oninput="updateManualPreview()">
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <!-- Sub-Subcategory -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Sub-Subcategory</label>
                                        <select name="sub_subcategory_id" id="manSubSubcategoryId" class="form-select" onchange="updateManualPreview()">
                                            <option value="">-- Select Existing Sub-Subcategory --</option>
                                            <?php foreach ($subSubcategories as $ssc): ?>
                                            <option value="<?= $ssc['id'] ?>" data-subcat-id="<?= $ssc['subcategory_id'] ?>" data-code="<?= e($ssc['code'] ?? '') ?>"><?= e($ssc['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-semibold">...or Create New Sub-Subcategory Name</label>
                                        <input type="text" name="new_sub_subcategory_name" id="manNewSubSubcatName" class="form-control" placeholder="e.g. Round Neck, Full Sleeve" oninput="updateManualPreview()">
                                    </div>
                                </div>
                            </div>

                            <!-- Optional Garment Attributes -->
                            <div class="mb-4 pb-3 border-bottom">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-palette me-2 text-primary"></i>Garment Attributes (Optional)</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Color</label>
                                        <select name="color_id" id="manColorId" class="form-select" onchange="updateManualPreview()">
                                            <option value="">-- None / Standard --</option>
                                            <?php foreach ($colors as $col): ?>
                                            <option value="<?= $col['id'] ?>"><?= e($col['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Design / Pattern</label>
                                        <select name="design_id" id="manDesignId" class="form-select" onchange="updateManualPreview()">
                                            <option value="">-- None / Solid --</option>
                                            <?php foreach ($designs as $d): ?>
                                            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Size</label>
                                        <select name="size_id" id="manSizeId" class="form-select" onchange="updateManualPreview()">
                                            <option value="">-- None / Free Size --</option>
                                            <?php foreach ($sizes as $s): ?>
                                            <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Quantity & Pricing -->
                            <div class="mb-4 pb-3 border-bottom">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-calculator me-2 text-primary"></i>Quantity &amp; Label Pricing</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark">Number of QR Labels <span class="text-danger">*</span></label>
                                        <input type="number" name="quantity" id="manQuantity" class="form-control form-control-lg fw-bold border-primary" min="1" max="1000" value="50" required oninput="updateManualPreview()">
                                        <div class="form-text small">Creates sequential QRs (e.g. 0001 to 0050).</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">MRP (₹)</label>
                                        <input type="number" step="0.01" min="0" name="mrp" id="manMrp" class="form-control form-control-lg" value="499.00" oninput="updateManualPreview()">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Selling Price (₹)</label>
                                        <input type="number" step="0.01" min="0" name="selling_price" id="manSellingPrice" class="form-control form-control-lg fw-bold text-success" value="299.00" oninput="updateManualPreview()">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="<?= url('production/qr-generations') ?>" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary btn-lg px-4 fw-bold">
                                    <i class="bi bi-qr-code-scan me-2"></i> Generate &amp; Save QR Batch
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Live Label Sticker Mockup Column -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                    <div class="card-header bg-dark text-white py-3">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-eye-fill me-2 text-warning"></i>Live Label Sticker Mockup</h6>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="text-muted small mb-2">Thermal Sticker Layout (50mm x 30mm)</div>
                        
                        <!-- Realistic Thermal Sticker Card -->
                        <div class="thermal-preview-card p-3 border border-dark rounded bg-white shadow-sm mx-auto text-center" style="max-width: 280px; border-width: 2px !important;">
                            <!-- Given Brand Name Print -->
                            <div class="fw-bold text-uppercase fs-6 pb-1 border-bottom border-dark text-truncate" id="stickerBrand">
                                GRM APPARELS
                            </div>
                            <div class="small fw-semibold text-muted text-uppercase mt-1" id="stickerHierarchy">
                                KIDS › T-SHIRT
                            </div>

                            <!-- QR Canvas -->
                            <div class="d-flex justify-content-center my-2">
                                <div id="stickerQrBox" class="p-1 bg-white border rounded"></div>
                            </div>

                            <!-- Generated QR Code String -->
                            <div class="font-monospace fw-bold text-dark" style="font-size: 11px;" id="stickerCode">
                                GRM-KW-TSH-0001
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2 pt-1 border-top border-dark small fw-bold">
                                <div>MRP: ₹<span id="stickerMrp">499.00</span></div>
                                <div class="text-primary">SALE: ₹<span id="stickerPrice">299.00</span></div>
                            </div>
                        </div>

                        <div class="alert alert-light border mt-3 text-start small text-muted mb-0">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                            <strong>New QR Format:</strong>
                            <div class="font-monospace fw-bold text-primary mt-1" id="formatFormula">
                                [BRAND]-[CATEGORY]-[SUBCATEGORY]-[SEQ]
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OPTION 2: FROM CATALOG / VARIANT -->
    <div class="tab-pane fade" id="catalogMode" role="tabpanel">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="bi bi-journal-bookmark me-2 text-warning"></i>Catalog &amp; Product Variant Link
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="<?= url('production/qr-generations/store-catalog') ?>">
                            <?= csrf_field() ?>

                            <!-- Brand Name Override -->
                            <div class="mb-4 pb-3 border-bottom">
                                <label class="form-label fw-bold text-dark fs-6">
                                    Brand Name to Print on Labels <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="brand_name" class="form-control form-control-lg text-uppercase fw-bold" placeholder="e.g. GRM APPARELS" value="GRM APPARELS" required>
                                <div class="form-text small">Defaults to GRM APPARELS or product's brand. Printed on all stickers.</div>
                            </div>

                            <!-- Catalog Select -->
                            <div class="mb-4 pb-3 border-bottom">
                                <label class="form-label fw-bold text-dark">Select Parent Catalog / Style <span class="text-danger">*</span></label>
                                <select name="product_id" id="catModeProduct" class="form-select form-select-lg" required onchange="onCatalogModeSelected()">
                                    <option value="">-- Choose Catalog / Style --</option>
                                    <?php foreach ($catalogs as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-code="<?= e($p['code']) ?>"><?= e($p['code']) ?> — <?= e($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Variant Select (Optional) -->
                            <div class="mb-4 pb-3 border-bottom">
                                <label class="form-label fw-semibold">Specific Variant (Optional)</label>
                                <select name="variant_id" id="catModeVariant" class="form-select">
                                    <option value="">-- Whole Catalog / Base Style --</option>
                                </select>
                                <div class="form-text small">Leave as Whole Catalog to generate base style QR labels.</div>
                            </div>

                            <!-- Quantity -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">Number of QR Labels <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control form-control-lg fw-bold" min="1" max="1000" value="50" required>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <a href="<?= url('production/qr-generations') ?>" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-warning btn-lg px-4 fw-bold">
                                    <i class="bi bi-qr-code me-2"></i> Generate Catalog QR Batch
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 p-4 bg-light">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle text-primary me-2"></i>Catalog Mode Features</h6>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-2">Inherits HSN, GST, and Category Hierarchy from your master Catalog.</li>
                        <li class="mb-2">Links directly to inventory and production batches.</li>
                        <li>Generates serialized labels sharing the catalog's stable identity.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
function onManualCategoryChanged() {
    const catId = document.getElementById('manCategoryId').value;
    const subcatSelect = document.getElementById('manSubcategoryId');

    for (let opt of subcatSelect.options) {
        if (!opt.value) continue;
        const optCat = opt.getAttribute('data-cat-id');
        opt.style.display = (!catId || optCat === catId) ? '' : 'none';
    }
    subcatSelect.value = '';
    updateManualPreview();
}

function onManualSubcategoryChanged() {
    const subcatId = document.getElementById('manSubcategoryId').value;
    const sscSelect = document.getElementById('manSubSubcategoryId');

    for (let opt of sscSelect.options) {
        if (!opt.value) continue;
        const optSubcat = opt.getAttribute('data-subcat-id');
        opt.style.display = (!subcatId || optSubcat === subcatId) ? '' : 'none';
    }
    sscSelect.value = '';
    updateManualPreview();
}

function updateManualPreview() {
    const brand = document.getElementById('manBrandName').value.trim() || 'BRAND';
    const catSelect = document.getElementById('manCategoryId');
    const newCat = document.getElementById('manNewCatName').value.trim();
    const catName = newCat || (catSelect.selectedIndex > 0 ? catSelect.options[catSelect.selectedIndex].text.split('(')[0].trim() : 'CAT');

    const subcatSelect = document.getElementById('manSubcategoryId');
    const newSubcat = document.getElementById('manNewSubcatName').value.trim();
    const subcatName = newSubcat || (subcatSelect.selectedIndex > 0 ? subcatSelect.options[subcatSelect.selectedIndex].text : '');

    const newSsc = document.getElementById('manNewSubSubcatName').value.trim();
    const sscSelect = document.getElementById('manSubSubcategoryId');
    const sscName = newSsc || (sscSelect.selectedIndex > 0 ? sscSelect.options[sscSelect.selectedIndex].text : '');

    const mrp = parseFloat(document.getElementById('manMrp').value || 0).toFixed(2);
    const price = parseFloat(document.getElementById('manSellingPrice').value || 0).toFixed(2);

    // Update sticker visual elements
    document.getElementById('stickerBrand').innerText = brand.toUpperCase();
    
    let hierParts = [catName];
    if (subcatName) hierParts.push(subcatName);
    if (sscName) hierParts.push(sscName);
    document.getElementById('stickerHierarchy').innerText = hierParts.join(' › ').toUpperCase();

    // Compute prefix code
    function toCode(txt) {
        return txt.replace(/[^A-Za-z0-9]/g, '').substr(0, 3).toUpperCase() || 'GEN';
    }

    const bCode = brand.replace(/[^A-Za-z0-9]/g, '').substr(0, 4).toUpperCase() || 'BRD';
    const cCode = toCode(catName);
    const scCode = subcatName ? toCode(subcatName) : '';
    const sscCode = sscName ? toCode(sscName) : '';

    let codeParts = [bCode, cCode];
    if (scCode) codeParts.push(scCode);
    if (sscCode) codeParts.push(sscCode);

    const fullCode = codeParts.join('-') + '-0001';
    document.getElementById('stickerCode').innerText = fullCode;
    document.getElementById('stickerMrp').innerText = mrp;
    document.getElementById('stickerPrice').innerText = price;
    document.getElementById('formatFormula').innerText = '[' + bCode + ']-[' + cCode + ']' + (scCode ? '-[' + scCode + ']' : '') + '-[SEQ]';

    // Render QR
    const qrBox = document.getElementById('stickerQrBox');
    qrBox.innerHTML = '';
    new QRCode(qrBox, {
        text: fullCode,
        width: 90,
        height: 90,
        correctLevel: QRCode.CorrectLevel.M
    });
}

function onCatalogModeSelected() {
    const prodId = document.getElementById('catModeProduct').value;
    const varSelect = document.getElementById('catModeVariant');
    varSelect.innerHTML = '<option value="">-- Whole Catalog / Base Style --</option>';

    if (!prodId) return;

    fetch('<?= url("api/catalog/variants?product_id=") ?>' + prodId)
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success' && res.data && res.data.variants) {
            res.data.variants.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.text = (v.sku || ('Variant #' + v.id)) + (v.size_name ? ' (' + v.size_name + ')' : '');
                varSelect.appendChild(opt);
            });
        }
    })
    .catch(e => console.error(e));
}

document.addEventListener('DOMContentLoaded', function() {
    updateManualPreview();
});
</script>
