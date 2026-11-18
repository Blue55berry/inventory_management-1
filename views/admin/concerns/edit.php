<div class="page-header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Concern: <?= e($concern['name']) ?></h2>
        <p class="text-muted small mb-0">Update corporate profile, tax registrations, document series prefixes, and active status.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/concerns/modules/' . $concern['id']) ?>" class="btn btn-outline-info">
            <i class="bi bi-shield-check me-1"></i> Configure Modules
        </a>
        <a href="<?= url('admin/concerns') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Concerns
        </a>
    </div>
</div>

<form method="POST" action="<?= url('admin/concerns/update/' . $concern['id']) ?>">
    <?= CSRF::field() ?>

    <div class="row g-4">
        <!-- Left Column: Business & Tax Credentials -->
        <div class="col-lg-8">
            <!-- Basic Identity -->
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>1. Legal Identity &amp; Branding</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Concern / Firm Display Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= e($concern['name']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Short Unique Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control text-uppercase font-monospace" value="<?= e($concern['code']) ?>" maxlength="20" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Registered Legal Company Name</label>
                            <input type="text" name="legal_name" class="form-control" value="<?= e($concern['legal_name'] ?? '') ?>" placeholder="Registered corporate name">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tax Identifiers -->
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-receipt me-2 text-success"></i>2. Tax &amp; Registration Identifiers</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">GSTIN (15-Digit Goods &amp; Services Tax Number)</label>
                            <input type="text" name="gst_number" class="form-control text-uppercase font-monospace" value="<?= e($concern['gst_number'] ?? '') ?>" maxlength="15">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Permanent Account Number (PAN)</label>
                            <input type="text" name="pan_number" class="form-control text-uppercase font-monospace" value="<?= e($concern['pan_number'] ?? '') ?>" maxlength="10">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address & Contact -->
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-geo-alt me-2 text-danger"></i>3. Contact &amp; Operating Address</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Official Phone / Mobile</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($concern['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Official Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($concern['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Registered Physical Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= e($concern['address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">City</label>
                            <input type="text" name="city" class="form-control" value="<?= e($concern['city'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">State</label>
                            <input type="text" name="state" class="form-control" value="<?= e($concern['state'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Pincode</label>
                            <input type="text" name="pincode" class="form-control font-monospace" value="<?= e($concern['pincode'] ?? '') ?>" maxlength="10">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. Authorized Staff Members & Operating Team -->
            <input type="hidden" name="staff_sync_submitted" value="1">
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-people me-2 text-primary"></i>6. Authorized Staff Members &amp; Operating Team</h6>
                        <small class="text-muted">Manage staff members authorized to view, switch into, and transact under this business concern.</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border" id="staffSelectedBadge">
                            <span id="selectedStaffCount"><?= count($assignedStaffIds ?? []) ?></span> / <?= count($allStaff ?? []) ?> Staff Assigned
                        </span>
                        <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-xs" data-bs-toggle="modal" data-bs-target="#inlineCreateStaffModal">
                            <i class="bi bi-person-plus-fill"></i> + Create New Staff
                        </button>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="selectAllStaff(true)">Select All</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="selectAllStaff(false)">Clear</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" id="staffFilterInput" class="form-control" placeholder="Search staff by name, role, department or username..." oninput="filterStaffCards(this.value)">
                        </div>
                    </div>

                    <div class="row g-2.5" id="staffCardsContainer">
                        <?php if (empty($allStaff)): ?>
                            <div class="col-12 text-center text-muted py-4">No active staff members found.</div>
                        <?php else: foreach ($allStaff as $st): 
                            $stId = (int)$st['id'];
                            $isAssigned = in_array($stId, $assignedStaffIds ?? [], true);
                            $isPrimary = ($stId === (int)($primaryStaffId ?? 0));
                            $initials = strtoupper(substr($st['full_name'], 0, 1) . (strpos($st['full_name'], ' ') !== false ? substr(strchr($st['full_name'], ' '), 1, 1) : ''));
                        ?>
                            <div class="col-12 col-md-6 staff-card-item mb-2" data-search="<?= strtolower(e($st['full_name'] . ' ' . $st['username'] . ' ' . ($st['role_name'] ?? '') . ' ' . ($st['department'] ?? ''))) ?>">
                                <div class="card h-100 border rounded-3 p-3 staff-select-card cursor-pointer transition-all <?= $isAssigned ? 'border-primary bg-primary bg-opacity-10' : 'bg-light bg-opacity-25' ?>" id="staffCard_<?= $stId ?>" onclick="handleStaffCardClick(event, <?= $stId ?>)">
                                    <div class="d-flex align-items-start gap-2.5">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input staff-checkbox" type="checkbox" name="staff_ids[]" value="<?= $stId ?>" id="chk_staff_<?= $stId ?>" <?= $isAssigned ? 'checked' : '' ?> onchange="syncStaffSelection(<?= $stId ?>)">
                                        </div>
                                        <div class="avatar-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 38px; height: 38px; font-size: 14px;">
                                            <?= e($initials) ?>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-center justify-content-between gap-1">
                                                <span class="fw-bold text-dark text-truncate small" title="<?= e($st['full_name']) ?>"><?= e($st['full_name']) ?></span>
                                                <span class="badge bg-secondary-subtle text-secondary border small" style="font-size: 10px;"><?= e($st['role_name'] ?? 'Staff') ?></span>
                                            </div>
                                            <div class="text-muted small text-truncate" style="font-size: 11px;">
                                                <i class="bi bi-person me-1"></i>@<?= e($st['username']) ?> &bull; <?= e($st['department'] ?? 'GENERAL') ?>
                                            </div>
                                            <?php if (!empty($st['phone'])): ?>
                                                <div class="text-muted small text-truncate" style="font-size: 11px;">
                                                    <i class="bi bi-telephone me-1"></i><?= e($st['phone']) ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Primary Manager Radio -->
                                            <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                                                <label class="form-check-label small text-muted cursor-pointer mb-0" for="primary_manager_<?= $stId ?>" style="font-size: 11px;">
                                                    <input class="form-check-input me-1 primary-manager-radio" type="radio" name="primary_staff_id" value="<?= $stId ?>" id="primary_manager_<?= $stId ?>" <?= $isPrimary ? 'checked' : '' ?>>
                                                    <span class="fw-semibold text-dark"><i class="bi bi-star-fill text-warning me-1"></i>Primary Manager</span>
                                                </label>
                                                <?php if ($isPrimary): ?>
                                                    <span class="badge bg-success-subtle text-success border" style="font-size: 9px;"><i class="bi bi-check2 me-1"></i>Primary</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Document Prefixes & Status -->
        <div class="col-lg-4">
            <!-- Document Prefixes -->
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-hash me-2 text-info"></i>4. Document Number Prefixes</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Invoice Prefix</label>
                        <input type="text" name="invoice_prefix" class="form-control text-uppercase font-monospace" value="<?= e($concern['invoice_prefix'] ?? 'INV-') ?>" required>
                        <div class="form-text">Prefix for POS sales and tax bills.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Purchase Order Prefix</label>
                        <input type="text" name="po_prefix" class="form-control text-uppercase font-monospace" value="<?= e($concern['po_prefix'] ?? 'PO-') ?>" required>
                        <div class="form-text">Prefix for fabric and yarn purchases.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dispatch / Gate Pass Prefix</label>
                        <input type="text" name="dispatch_prefix" class="form-control text-uppercase font-monospace" value="<?= e($concern['dispatch_prefix'] ?? 'DSP-') ?>" required>
                        <div class="form-text">Prefix for outward gate passes.</div>
                    </div>
                </div>
            </div>

            <!-- Status Control -->
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-toggle-on me-2 text-success"></i>5. Operating Status</h6>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch fs-6">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" <?= $concern['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="isActiveSwitch">Entity Active &amp; Operational</label>
                    </div>
                    <div class="small text-muted mt-2">When deactivated, users cannot record new transactions under this concern.</div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card border-0 shadow-sm bg-white p-3">
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                    <i class="bi bi-check2-circle me-1"></i> Save Changes
                </button>
                <a href="<?= url('admin/concerns') ?>" class="btn btn-outline-secondary w-100 mt-2">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</form>

<!-- ========================================================================= -->
<!-- INLINE QUICK-CREATE STAFF MEMBER MODAL -->
<!-- ========================================================================= -->
<div class="modal fade" id="inlineCreateStaffModal" tabindex="-1" aria-labelledby="inlineStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="quickStaffForm" onsubmit="submitInlineCreateStaff(event)">
            <?= CSRF::field() ?>
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title fw-bold" id="inlineStaffModalLabel">
                    <i class="bi bi-person-plus text-primary me-2"></i>Quick Create Staff Member
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="inlineStaffAlert" class="alert alert-danger d-none py-2 px-3 small"></div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="quick_full_name" id="quick_full_name" class="form-control" placeholder="e.g. Anandha Kumar" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Username <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">@</span>
                            <input type="text" name="quick_username" id="quick_username" class="form-control font-monospace" placeholder="anand_k" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Phone (10 Digits)</label>
                        <input type="tel" name="quick_phone" id="quick_phone" class="form-control font-monospace" placeholder="9876543210" maxlength="10">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email Address</label>
                    <input type="email" name="quick_email" id="quick_email" class="form-control" placeholder="anand@example.com">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">System Role <span class="text-danger">*</span></label>
                    <select name="quick_role_id" id="quick_role_id" class="form-select" required>
                        <option value="">-- Select Role --</option>
                        <?php foreach (($roles ?? []) as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold small">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="quick_password" id="quick_password" class="form-control" placeholder="Minimum 6 characters" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="toggleQuickPassword()">
                            <i class="bi bi-eye" id="quickEyeIcon"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2.5">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitQuickStaff">
                    <i class="bi bi-check2-circle me-1"></i> Create &amp; Add to Concern
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleQuickPassword() {
    const input = document.getElementById('quick_password');
    const icon = document.getElementById('quickEyeIcon');
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Inline quick staff AJAX creation
async function submitInlineCreateStaff(e) {
    e.preventDefault();
    const alertBox = document.getElementById('inlineStaffAlert');
    const btn = document.getElementById('btnSubmitQuickStaff');
    alertBox.classList.add('d-none');
    alertBox.textContent = '';

    const fullName = document.getElementById('quick_full_name').value.trim();
    const username = document.getElementById('quick_username').value.trim();
    const email = document.getElementById('quick_email').value.trim();
    const phone = document.getElementById('quick_phone').value.trim();
    const roleId = document.getElementById('quick_role_id').value;
    const password = document.getElementById('quick_password').value;

    if (!fullName || !username || !roleId || !password) {
        alertBox.textContent = 'Please fill all required fields.';
        alertBox.classList.remove('d-none');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';

    const formData = new FormData();
    formData.append('full_name', fullName);
    formData.append('username', username);
    formData.append('email', email);
    formData.append('phone', phone);
    formData.append('role_id', roleId);
    formData.append('password', password);

    // Append CSRF token
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    if (csrfInput) formData.append('csrf_token', csrfInput.value);

    try {
        const resp = await fetch('<?= url("admin/concerns/quick-create-staff") ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await resp.json();

        if (data.success && data.staff) {
            const st = data.staff;
            const container = document.getElementById('staffCardsContainer');
            const initials = fullName.substring(0, 2).toUpperCase();

            // Append newly created staff card
            const newCardHtml = `
                <div class="col-12 col-md-6 staff-card-item mb-2" data-search="${(st.full_name + ' ' + st.username + ' ' + (st.role_name || '') + ' ' + (st.department || '')).toLowerCase()}">
                    <div class="card h-100 border rounded-3 p-3 staff-select-card cursor-pointer transition-all border-primary bg-primary bg-opacity-10" id="staffCard_${st.id}" onclick="handleStaffCardClick(event, ${st.id})">
                        <div class="d-flex align-items-start gap-2.5">
                            <div class="form-check mt-1">
                                <input class="form-check-input staff-checkbox" type="checkbox" name="staff_ids[]" value="${st.id}" id="chk_staff_${st.id}" checked onchange="syncStaffSelection(${st.id})">
                            </div>
                            <div class="avatar-circle bg-success text-white fw-bold d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 38px; height: 38px; font-size: 14px;">
                                ${initials}
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center justify-content-between gap-1">
                                    <span class="fw-bold text-dark text-truncate small">${escapeHtml(st.full_name)}</span>
                                    <span class="badge bg-success-subtle text-success border small" style="font-size: 10px;">${escapeHtml(st.role_name || 'Staff')}</span>
                                </div>
                                <div class="text-muted small text-truncate" style="font-size: 11px;">
                                    <i class="bi bi-person me-1"></i>@${escapeHtml(st.username)} &bull; ${escapeHtml(st.department || 'GENERAL')}
                                </div>
                                ${st.phone ? `<div class="text-muted small text-truncate" style="font-size: 11px;"><i class="bi bi-telephone me-1"></i>${escapeHtml(st.phone)}</div>` : ''}
                                
                                <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                                    <label class="form-check-label small text-muted cursor-pointer mb-0" for="primary_manager_${st.id}" style="font-size: 11px;">
                                        <input class="form-check-input me-1 primary-manager-radio" type="radio" name="primary_staff_id" value="${st.id}" id="primary_manager_${st.id}" checked>
                                        <span class="fw-semibold text-dark"><i class="bi bi-star-fill text-warning me-1"></i>Primary Manager</span>
                                    </label>
                                    <span class="badge bg-primary text-white" style="font-size: 9px;">New</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            if (container) {
                container.insertAdjacentHTML('afterbegin', newCardHtml);
            }

            // Close modal
            const modalEl = document.getElementById('inlineCreateStaffModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modalInstance.hide();
            document.getElementById('quickStaffForm').reset();

            updateStaffCount();
            alert('✅ Staff member ' + st.full_name + ' created and assigned to this Concern!');
        } else {
            alertBox.textContent = data.message || 'Error creating staff member.';
            alertBox.classList.remove('d-none');
        }
    } catch (err) {
        alertBox.textContent = 'Network or server error: ' + err.message;
        alertBox.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Create & Add to Concern';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function handleStaffCardClick(event, stId) {
    if (event.target.tagName === 'INPUT' || event.target.tagName === 'LABEL') {
        return;
    }
    const chk = document.getElementById('chk_staff_' + stId);
    if (chk) {
        chk.checked = !chk.checked;
        syncStaffSelection(stId);
    }
}

function syncStaffSelection(stId) {
    const chk = document.getElementById('chk_staff_' + stId);
    const card = document.getElementById('staffCard_' + stId);
    const radio = document.getElementById('primary_manager_' + stId);

    if (chk && card) {
        if (chk.checked) {
            card.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
            card.classList.remove('bg-light', 'bg-opacity-25');
            const curPrimary = document.querySelector('.primary-manager-radio:checked');
            if (!curPrimary && radio) {
                radio.checked = true;
            }
        } else {
            card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
            card.classList.add('bg-light', 'bg-opacity-25');
            if (radio && radio.checked) {
                radio.checked = false;
                const firstChecked = document.querySelector('.staff-checkbox:checked');
                if (firstChecked) {
                    const nextRadio = document.getElementById('primary_manager_' + firstChecked.value);
                    if (nextRadio) nextRadio.checked = true;
                }
            }
        }
    }
    updateStaffCount();
}

function selectAllStaff(state) {
    document.querySelectorAll('.staff-checkbox').forEach(cb => {
        cb.checked = state;
        const stId = cb.value;
        const card = document.getElementById('staffCard_' + stId);
        if (card) {
            if (state) {
                card.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
                card.classList.remove('bg-light', 'bg-opacity-25');
            } else {
                card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
                card.classList.add('bg-light', 'bg-opacity-25');
            }
        }
    });
    if (state) {
        const curChecked = document.querySelector('.primary-manager-radio:checked');
        if (!curChecked) {
            const first = document.querySelector('.primary-manager-radio');
            if (first) first.checked = true;
        }
    } else {
        document.querySelectorAll('.primary-manager-radio').forEach(r => r.checked = false);
    }
    updateStaffCount();
}

function filterStaffCards(q) {
    const query = q.trim().toLowerCase();
    document.querySelectorAll('.staff-card-item').forEach(item => {
        const searchData = item.getAttribute('data-search') || '';
        item.style.display = (!query || searchData.includes(query)) ? '' : 'none';
    });
}

function updateStaffCount() {
    const checked = document.querySelectorAll('.staff-checkbox:checked');
    const badge = document.getElementById('selectedStaffCount');
    if (badge) {
        badge.textContent = checked.length;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateStaffCount();
});
</script>
