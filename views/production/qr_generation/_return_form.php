<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-semibold text-dark">
            <i class="bi bi-arrow-return-left me-2 text-danger"></i>Return QR Labels
        </h5>
        <span class="badge bg-danger-subtle text-danger border">Interface preview — workflow to be confirmed</span>
    </div>
    <div class="card-body p-4">
        <div class="alert alert-warning border mb-4">
            <i class="bi bi-info-circle-fill me-2"></i>
            The return QR workflow is not enabled yet. This screen shows the planned layout only; we will finalise the scan, validation and stock-reversal rules together before enabling generation.
        </div>

        <form id="formReturnQr" onsubmit="return false;">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Original QR Code / Scan</label>
                    <input type="text" class="form-control form-control-lg" placeholder="Scan or enter the QR being returned" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Return Quantity</label>
                    <input type="number" class="form-control form-control-lg" min="1" value="1" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Return Type</label>
                    <select class="form-select form-select-lg" disabled>
                        <option>Customer Return</option>
                        <option>Supplier Return</option>
                        <option>Damaged / Spoiled</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Reason</label>
                    <select class="form-select" disabled>
                        <option>-- Select reason --</option>
                        <option>Wrong item supplied</option>
                        <option>Quality issue</option>
                        <option>Customer changed mind</option>
                        <option>Damaged in transit</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Destination Warehouse / Shelf</label>
                    <input type="text" class="form-control" placeholder="Where the returned piece will be placed" disabled>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Remarks</label>
                <textarea class="form-control" rows="3" placeholder="Optional notes about the return" disabled></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center border-top pt-3">
                <span class="small text-muted"><i class="bi bi-lock me-1"></i>Fields are disabled until the return flow is approved.</span>
                <button type="button" class="btn btn-danger btn-lg fw-bold" disabled>
                    <i class="bi bi-arrow-return-left me-2"></i> Generate Return QR (Coming Soon)
                </button>
            </div>
        </form>
    </div>
</div>
