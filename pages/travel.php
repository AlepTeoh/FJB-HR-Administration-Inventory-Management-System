<?php
// pages/travel.php – Business Travel Management (Admin: HR & IT)
require_once __DIR__ . '/../includes/config.php';
$pdo  = getDB();
$user = currentUser();

// ── Filters ────────────────────────────────────────────────────────────────
$search      = trim($_GET['q'] ?? '');
$dept_filter = $_GET['dept'] ?? '';
$month_filter= $_GET['month'] ?? '';   // YYYY-MM
$staff_filter= isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : 0;

// Departments list for filter dropdown
$allDepts = $pdo->query("SELECT id, name, company FROM departments ORDER BY company, name")->fetchAll();

// ── Build query ────────────────────────────────────────────────────────────
$conditions = [];
$params     = [];

if ($search) {
    $conditions[] = "(s.name LIKE ? OR s.staff_no LIKE ? OR bt.destination LIKE ? OR bt.purpose LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($dept_filter) {
    $conditions[] = "s.department_id = ?";
    $params[] = $dept_filter;
}
if ($month_filter) {
    $conditions[] = "DATE_FORMAT(bt.departure_date,'%Y-%m') = ?";
    $params[] = $month_filter;
}
if ($staff_filter) {
    $conditions[] = "bt.staff_id = ?";
    $params[] = $staff_filter;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $pdo->prepare("
    SELECT bt.*,
           s.name      AS staff_name,
           s.staff_no,
           s.position,
           d.name      AS dept_name,
           d.company,
           u.name      AS created_by_name
      FROM business_travel bt
      JOIN staff s ON bt.staff_id = s.id
      LEFT JOIN departments d ON s.department_id = d.id
      LEFT JOIN users u ON bt.created_by = u.id
     $where
     ORDER BY bt.departure_date DESC, s.name
");
$stmt->execute($params);
$travels = $stmt->fetchAll();

// ── Stats ──────────────────────────────────────────────────────────────────
$totalTrips     = count($travels);
$uniqueStaff    = count(array_unique(array_column($travels, 'staff_id')));
$today          = date('Y-m-d');
$activeNow      = array_filter($travels, fn($r) => $r['departure_date'] <= $today && $r['return_date'] >= $today);
$upcomingTrips  = array_filter($travels, fn($r) => $r['departure_date'] > $today);
?>

<!-- ── Stats row ─────────────────────────────────────────────────────────── -->
<div class="stats-row" style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;">

    <div class="stat-card">
        <div class="stat-icon" style="background:#eff6ff;color:#2563eb;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.6 12 19.79 19.79 0 0 1 1.58 3.47 2 2 0 0 1 3.55 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.27a16 16 0 0 0 6.29 6.29l1.63-1.83a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 14.92z"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= $totalTrips ?></span>
            <span class="stat-label">Total Trips</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4;color:#16a34a;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= $uniqueStaff ?></span>
            <span class="stat-label">Staff Travelling</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= count($activeNow) ?></span>
            <span class="stat-label">Currently Away</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#f5f3ff;color:#7c3aed;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= count($upcomingTrips) ?></span>
            <span class="stat-label">Upcoming Trips</span>
        </div>
    </div>

</div>

<!-- ── Main card ─────────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.4rem;vertical-align:-.15em;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            Business Travel Records
        </h3>
        <button class="btn btn-primary btn-sm" onclick="openAddModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Travel
        </button>
    </div>

    <!-- Filters -->
    <div style="padding:.75rem 1.25rem;border-bottom:1px solid var(--border);display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;">
        <form method="GET" action="dashboard.php" style="display:contents;">
            <input type="hidden" name="page" value="travel">
            <div class="search-wrap" style="position:relative;flex:1;min-width:200px;">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                       placeholder="Search staff, destination…"
                       style="padding:.5rem .75rem .5rem 2rem;border:1.5px solid var(--border);border-radius:8px;font-size:.85rem;width:100%;">
            </div>
            <select name="dept" style="padding:.5rem .75rem;border:1.5px solid var(--border);border-radius:8px;font-size:.85rem;background:white;">
                <option value="">All Departments</option>
                <?php foreach ($allDepts as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $dept_filter == $d['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['name']) ?> (<?= $d['company'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <input type="month" name="month" value="<?= htmlspecialchars($month_filter) ?>"
                   style="padding:.5rem .75rem;border:1.5px solid var(--border);border-radius:8px;font-size:.85rem;background:white;">
            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
            <?php if ($search || $dept_filter || $month_filter): ?>
            <a href="dashboard.php?page=travel" class="btn btn-ghost btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <?php if (empty($travels)): ?>
    <div style="text-align:center;padding:3rem 1rem;color:var(--muted);">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:.75rem;opacity:.4;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.6 12 19.79 19.79 0 0 1 1.58 3.47 2 2 0 0 1 3.55 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.27a16 16 0 0 0 6.29 6.29l1.63-1.83a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 14.92z"/></svg>
        <p style="font-weight:600;margin-bottom:.25rem;">No travel records found</p>
        <p style="font-size:.85rem;">Use the <em>Add Travel</em> button to record a business trip.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Staff</th>
                    <th>Department</th>
                    <th>Destination</th>
                    <th>Purpose</th>
                    <th>Departure</th>
                    <th>Return</th>
                    <th>Duration</th>
                    <th>Transport</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($travels as $t):
                $status = 'upcoming';
                $statusLabel = 'Upcoming';
                $statusStyle = 'background:#f5f3ff;color:#7c3aed;';
                if ($t['departure_date'] <= $today && $t['return_date'] >= $today) {
                    $status = 'active'; $statusLabel = 'Away'; $statusStyle = 'background:#fef3c7;color:#d97706;';
                } elseif ($t['return_date'] < $today) {
                    $status = 'completed'; $statusLabel = 'Completed'; $statusStyle = 'background:#f0fdf4;color:#16a34a;';
                }
            ?>
            <tr>
                <td>
                    <div style="font-weight:600;line-height:1.3;"><?= htmlspecialchars($t['staff_name']) ?></div>
                    <div style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars($t['staff_no']) ?></div>
                </td>
                <td>
                    <span class="dept-badge"><?= htmlspecialchars($t['dept_name'] ?? '—') ?></span>
                    <?php if ($t['company']): ?>
                    <div style="font-size:.75rem;color:var(--muted);margin-top:.2rem;"><?= htmlspecialchars($t['company']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="font-weight:500;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-.1em;margin-right:.25rem;color:var(--muted);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?= htmlspecialchars($t['destination']) ?>
                    </div>
                </td>
                <td style="max-width:160px;">
                    <span style="font-size:.85rem;color:var(--muted);" title="<?= htmlspecialchars($t['purpose'] ?? '') ?>">
                        <?= $t['purpose'] ? htmlspecialchars(mb_strimwidth($t['purpose'], 0, 35, '…')) : '—' ?>
                    </span>
                </td>
                <td style="white-space:nowrap;"><?= date('d M Y', strtotime($t['departure_date'])) ?></td>
                <td style="white-space:nowrap;"><?= date('d M Y', strtotime($t['return_date'])) ?></td>
                <td style="text-align:center;">
                    <span style="font-weight:600;color:var(--navy);"><?= $t['duration_days'] ?></span>
                    <span style="font-size:.78rem;color:var(--muted);"> day<?= $t['duration_days'] != 1 ? 's' : '' ?></span>
                </td>
                <td style="font-size:.85rem;color:var(--muted);"><?= htmlspecialchars($t['transport'] ?: '—') ?></td>
                <td>
                    <span class="status-badge" style="<?= $statusStyle ?>font-size:.75rem;padding:.2rem .55rem;border-radius:6px;font-weight:600;">
                        <?= $statusLabel ?>
                    </span>
                </td>
                <td style="text-align:right;white-space:nowrap;">
                    <button class="btn btn-ghost btn-xs" onclick="editTravel(<?= $t['id'] ?>)" title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="btn btn-danger btn-xs" onclick="deleteTravel(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['staff_name'])) ?>')" title="Delete">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="padding:.75rem 1.25rem;color:var(--muted);font-size:.82rem;border-top:1px solid var(--border);">
        Showing <?= count($travels) ?> record<?= count($travels) != 1 ? 's' : '' ?>
    </div>
    <?php endif; ?>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     ADD / EDIT MODAL
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal" id="travelModal">
    <div class="modal-box" style="max-width:560px;">
        <div class="modal-header">
            <h3 id="travelModalTitle">Add Business Travel</h3>
            <button class="modal-close" onclick="closeModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form method="POST" action="actions/travel.php" id="travelForm">
            <input type="hidden" name="action" value="save_travel">
            <input type="hidden" name="id" id="travel_id" value="">
            <input type="hidden" name="staff_id" id="travel_staff_id" value="">

            <div class="form-grid" style="padding:1.25rem 1.5rem;">

                <!-- Staff search -->
                <div class="form-group form-full" id="staffSearchGroup">
                    <label>Staff Member <span style="color:var(--danger)">*</span></label>
                    <div style="position:relative;">
                        <input type="text" id="staffSearchInput" placeholder="Search by name or staff no…"
                               autocomplete="off"
                               style="width:100%;padding:.65rem 1rem;border:1.5px solid var(--border);border-radius:var(--radius);font-size:.875rem;">
                        <div id="staffDropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:white;border:1px solid var(--border);border-radius:8px;box-shadow:var(--shadow);z-index:200;max-height:200px;overflow-y:auto;"></div>
                    </div>
                    <!-- Selected staff display -->
                    <div id="selectedStaffDisplay" style="display:none;margin-top:.5rem;padding:.5rem .75rem;background:var(--sky-light);border-radius:8px;font-size:.85rem;display:flex;align-items:center;justify-content:space-between;">
                        <span id="selectedStaffName"></span>
                        <button type="button" onclick="clearStaffSelection()" style="background:none;border:none;cursor:pointer;color:var(--muted);font-size:1rem;line-height:1;">×</button>
                    </div>
                </div>

                <!-- Destination -->
                <div class="form-group form-full">
                    <label>Destination <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="destination" id="travel_destination" placeholder="e.g. Kuala Lumpur, Malaysia" required
                           style="width:100%;padding:.65rem 1rem;border:1.5px solid var(--border);border-radius:var(--radius);font-size:.875rem;">
                </div>

                <!-- Purpose -->
                <div class="form-group form-full">
                    <label>Purpose / Event</label>
                    <input type="text" name="purpose" id="travel_purpose" placeholder="e.g. Client meeting, Conference"
                           style="width:100%;padding:.65rem 1rem;border:1.5px solid var(--border);border-radius:var(--radius);font-size:.875rem;">
                </div>

                <!-- Dates -->
                <div class="form-group">
                    <label>Departure Date <span style="color:var(--danger)">*</span></label>
                    <input type="date" name="departure_date" id="travel_departure" required
                           style="width:100%;padding:.65rem 1rem;border:1.5px solid var(--border);border-radius:var(--radius);font-size:.875rem;">
                </div>
                <div class="form-group">
                    <label>Return Date <span style="color:var(--danger)">*</span></label>
                    <input type="date" name="return_date" id="travel_return" required
                           style="width:100%;padding:.65rem 1rem;border:1.5px solid var(--border);border-radius:var(--radius);font-size:.875rem;">
                </div>

                <!-- Duration preview -->
                <div class="form-full" id="durationPreview" style="display:none;padding:.5rem .75rem;background:#f8fafc;border-radius:8px;font-size:.85rem;color:var(--muted);text-align:center;"></div>

                <!-- Transport -->
                <div class="form-group form-full">
                    <label>Mode of Transport</label>
                    <select name="transport" id="travel_transport"
                            style="width:100%;padding:.65rem 1rem;border:1.5px solid var(--border);border-radius:var(--radius);font-size:.875rem;background:white;">
                        <option value="">— Select —</option>
                        <option value="Flight">✈ Flight</option>
                        <option value="Train">🚆 Train</option>
                        <option value="Bus">🚌 Bus</option>
                        <option value="Car">🚗 Car (Company)</option>
                        <option value="Personal Car">🚗 Car (Personal)</option>
                        <option value="Grab/Taxi">🚕 Grab / Taxi</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <!-- Notes -->
                <div class="form-group form-full">
                    <label>Notes</label>
                    <textarea name="notes" id="travel_notes" rows="2" placeholder="Any additional info…"
                              style="width:100%;padding:.65rem 1rem;border:1.5px solid var(--border);border-radius:var(--radius);font-size:.875rem;resize:vertical;"></textarea>
                </div>

            </div><!-- /.form-grid -->

            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm" id="travelSubmitBtn">Save Travel Record</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     DELETE CONFIRM MODAL
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal" id="deleteModal">
    <div class="modal-box modal-sm">
        <div class="modal-header">
            <h3>Delete Travel Record</h3>
            <button class="modal-close" onclick="closeDeleteModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div style="padding:1.25rem 1.5rem;">
            <p id="deleteConfirmText" style="color:var(--muted);font-size:.9rem;"></p>
        </div>
        <form method="POST" action="actions/travel.php">
            <input type="hidden" name="action" value="delete_travel">
            <input type="hidden" name="id" id="delete_travel_id">
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     INLINE SCRIPTS
══════════════════════════════════════════════════════════════════════════ -->
<script>
/* ── Modal helpers ───────────────────────────────────────────────────── */
function openAddModal() {
    document.getElementById('travelModalTitle').textContent = 'Add Business Travel';
    document.getElementById('travelForm').reset();
    document.getElementById('travel_id').value = '';
    document.getElementById('travel_staff_id').value = '';
    document.getElementById('travelSubmitBtn').textContent = 'Save Travel Record';
    clearStaffSelection();
    hideDurationPreview();
    openModal('travelModal');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    document.getElementById('modalOverlay').classList.remove('active');
}

function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.getElementById('modalOverlay').classList.add('active');
}

/* Override global closeModal to also handle delete modal */
const _origClose = window.closeModal;
window.closeModal = function() {
    document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
    document.getElementById('modalOverlay').classList.remove('active');
};

/* ── Edit travel ─────────────────────────────────────────────────────── */
async function editTravel(id) {
    const res  = await fetch(`actions/travel.php?get_record=${id}`);
    const data = await res.json();
    if (!data || !data.id) return alert('Could not load record.');

    document.getElementById('travelModalTitle').textContent = 'Edit Travel Record';
    document.getElementById('travel_id').value         = data.id;
    document.getElementById('travel_staff_id').value   = data.staff_id;
    document.getElementById('travel_destination').value= data.destination;
    document.getElementById('travel_purpose').value    = data.purpose ?? '';
    document.getElementById('travel_departure').value  = data.departure_date;
    document.getElementById('travel_return').value     = data.return_date;
    document.getElementById('travel_transport').value  = data.transport ?? '';
    document.getElementById('travel_notes').value      = data.notes ?? '';
    document.getElementById('travelSubmitBtn').textContent = 'Update Record';

    // Show selected staff
    showSelectedStaff(data.staff_id, `${data.staff_name} (${data.staff_no})`);
    updateDurationPreview();
    openModal('travelModal');
}

/* ── Delete travel ───────────────────────────────────────────────────── */
function deleteTravel(id, name) {
    document.getElementById('delete_travel_id').value = id;
    document.getElementById('deleteConfirmText').textContent =
        `Are you sure you want to delete the travel record for ${name}? This cannot be undone.`;
    openModal('deleteModal');
}

/* ── Staff search autocomplete ───────────────────────────────────────── */
let staffSearchTimeout;
const staffInput    = document.getElementById('staffSearchInput');
const staffDropdown = document.getElementById('staffDropdown');

staffInput.addEventListener('input', function() {
    clearTimeout(staffSearchTimeout);
    const q = this.value.trim();
    if (q.length < 2) { staffDropdown.style.display = 'none'; return; }
    staffSearchTimeout = setTimeout(() => fetchStaff(q), 280);
});

staffInput.addEventListener('focus', function() {
    if (this.value.trim().length >= 2) fetchStaff(this.value.trim());
});

document.addEventListener('click', function(e) {
    if (!staffInput.contains(e.target) && !staffDropdown.contains(e.target)) {
        staffDropdown.style.display = 'none';
    }
});

async function fetchStaff(q) {
    const res  = await fetch(`actions/travel.php?search_staff=${encodeURIComponent(q)}`);
    const list = await res.json();
    staffDropdown.innerHTML = '';
    if (!list.length) {
        staffDropdown.innerHTML = '<div style="padding:.65rem 1rem;color:var(--muted);font-size:.85rem;">No staff found</div>';
    } else {
        list.forEach(s => {
            const div = document.createElement('div');
            div.style.cssText = 'padding:.6rem 1rem;cursor:pointer;font-size:.875rem;border-bottom:1px solid #f1f5f9;';
            div.innerHTML = `<strong>${s.name}</strong> <span style="color:var(--muted);font-size:.8rem;">${s.staff_no} · ${s.dept_name ?? ''}</span>`;
            div.addEventListener('mouseenter', () => div.style.background = 'var(--sky-light)');
            div.addEventListener('mouseleave', () => div.style.background = '');
            div.addEventListener('click', () => {
                showSelectedStaff(s.id, `${s.name} (${s.staff_no})`);
                staffDropdown.style.display = 'none';
            });
            staffDropdown.appendChild(div);
        });
    }
    staffDropdown.style.display = 'block';
}

function showSelectedStaff(id, label) {
    document.getElementById('travel_staff_id').value = id;
    document.getElementById('selectedStaffName').textContent = label;
    const disp = document.getElementById('selectedStaffDisplay');
    disp.style.display = 'flex';
    staffInput.style.display = 'none';
    staffDropdown.style.display = 'none';
}

function clearStaffSelection() {
    document.getElementById('travel_staff_id').value = '';
    document.getElementById('selectedStaffDisplay').style.display = 'none';
    staffInput.style.display = '';
    staffInput.value = '';
}

/* ── Duration preview ────────────────────────────────────────────────── */
document.getElementById('travel_departure').addEventListener('change', updateDurationPreview);
document.getElementById('travel_return').addEventListener('change', updateDurationPreview);

function updateDurationPreview() {
    const dep = document.getElementById('travel_departure').value;
    const ret = document.getElementById('travel_return').value;
    const el  = document.getElementById('durationPreview');
    if (dep && ret && ret >= dep) {
        const d1 = new Date(dep), d2 = new Date(ret);
        const days = Math.round((d2 - d1) / 86400000) + 1;
        el.textContent = `🗓 Duration: ${days} day${days !== 1 ? 's' : ''}`;
        el.style.display = 'block';
    } else {
        hideDurationPreview();
    }
}

function hideDurationPreview() {
    document.getElementById('durationPreview').style.display = 'none';
}

/* ── Form validation ─────────────────────────────────────────────────── */
document.getElementById('travelForm').addEventListener('submit', function(e) {
    const staffId = document.getElementById('travel_staff_id').value;
    if (!staffId) {
        e.preventDefault();
        staffInput.style.borderColor = 'var(--danger)';
        staffInput.focus();
        return;
    }
    const dep = document.getElementById('travel_departure').value;
    const ret = document.getElementById('travel_return').value;
    if (ret < dep) {
        e.preventDefault();
        alert('Return date cannot be before departure date.');
    }
});
</script>
