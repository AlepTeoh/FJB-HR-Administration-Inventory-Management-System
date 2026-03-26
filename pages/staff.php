<?php
// pages/staff.php - Staff Registry (Admin HR + Admin IT)
require_once __DIR__ . '/../includes/config.php';
$pdo  = getDB();
$user = currentUser();

// Filters
$dept_filter    = $_GET['dept'] ?? '';
$company_filter = $_GET['company'] ?? '';
$search         = $_GET['q'] ?? '';

// Get departments for filter
$departments = $pdo->query("SELECT * FROM departments ORDER BY company, name")->fetchAll();

// Build query
$conditions = [];
$params = [];
if ($dept_filter) {
    $conditions[] = 's.department_id = ?';
    $params[] = $dept_filter;
}
if ($company_filter) {
    $conditions[] = 's.company = ?';
    $params[] = $company_filter;
}
if ($search) {
    $conditions[] = '(s.name LIKE ? OR s.staff_no LIKE ? OR s.position LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$stmt = $pdo->prepare("
    SELECT s.*, d.name as dept_name 
    FROM staff s 
    LEFT JOIN departments d ON s.department_id = d.id
    $where
    ORDER BY d.name, s.name
");
$stmt->execute($params);
$staff_list = $stmt->fetchAll();

// Count stats
$totalStaff = $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
$totalDepts = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$fjbCount   = $pdo->query("SELECT COUNT(*) FROM staff WHERE company='FJB'")->fetchColumn();
$fbsbCount  = $pdo->query("SELECT COUNT(*) FROM staff WHERE company='FBSB'")->fetchColumn();
?>

<div class="page-header">
    <div>
        <h2>Staff Registry</h2>
        <p class="page-subtitle">
            <?= $totalStaff ?> staff · <?= $totalDepts ?> departments · FJB: <?= $fjbCount ?> · FBSB: <?= $fbsbCount ?>
        </p>
    </div>
    <?php if (isAdmin()): ?>
    <button class="btn btn-primary" onclick="openModal('addStaffModal')">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Add Staff
    </button>
    <?php endif; ?>
</div>

<!-- Filter Bar -->
<div class="filter-bar" style="flex-wrap:wrap; gap:.5rem;">
    <form method="GET" action="" id="staffFilterForm" style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; width:100%;">
        <input type="hidden" name="page" value="staff">
        <input type="text" name="q" id="staffSearchInput" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, staff no, position…" class="filter-search" style="flex:1; min-width:200px;" autocomplete="off">
        <select name="company" id="staffCompanyFilter" class="filter-select">
            <option value="">All Companies</option>
            <option value="FJB" <?= $company_filter==='FJB'?'selected':'' ?>>FGV Johor Bulkers (FJB)</option>
            <option value="FBSB" <?= $company_filter==='FBSB'?'selected':'' ?>>FGV Bulkers Sdn Bhd (FBSB)</option>
        </select>
        <select name="dept" id="staffDeptFilter" class="filter-select">
            <option value="">All Departments</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $dept_filter==$d['id']?'selected':'' ?>>
                [<?= $d['company'] ?>] <?= htmlspecialchars($d['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <a href="?page=staff" class="btn btn-ghost btn-sm">Clear</a>
    </form>
</div>

<div class="card">
    <?php if (empty($staff_list)): ?>
    <div class="empty-state" id="staffEmptyState">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
        <p>No staff records found.</p>
    </div>
    <?php else: ?>
    <div style="padding:.75rem 1rem; color:#64748b; font-size:.85rem;" id="staffCount">Showing <?= count($staff_list) ?> record(s)</div>
    <div id="staffNoResults" style="display:none; padding:2.5rem 1rem; text-align:center; color:#64748b;">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:.75rem; opacity:.4;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <p style="font-weight:600; margin-bottom:.25rem;">No matching staff found</p>
        <p style="font-size:.85rem;">Try a different name, staff number, or position.</p>
    </div>
    <table class="table" id="staffTable">
        <thead>
            <tr>
                <th>Staff No</th>
                <th>Name</th>
                <th>Position</th>
                <th>Department</th>
                <th>Company</th>
                <?php if (isAdmin()): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody id="staffTableBody">
        <?php foreach ($staff_list as $s): ?>
        <tr
            data-name="<?= strtolower(htmlspecialchars($s['name'])) ?>"
            data-staffno="<?= strtolower(htmlspecialchars($s['staff_no'])) ?>"
            data-position="<?= strtolower(htmlspecialchars($s['position'] ?? '')) ?>"
            data-dept="<?= $s['department_id'] ?? '' ?>"
            data-company="<?= strtolower($s['company']) ?>"
        >
            <td><code style="font-size:.82rem;color:#6366f1;"><?= htmlspecialchars($s['staff_no']) ?></code></td>
            <td><strong class="staff-name-cell"><?= htmlspecialchars($s['name']) ?></strong></td>
            <td style="font-size:.85rem; color:#475569;"><?= htmlspecialchars($s['position']) ?></td>
            <td><span class="dept-badge"><?= htmlspecialchars($s['dept_name'] ?? '—') ?></span></td>
            <td><span class="company-badge company-<?= strtolower($s['company']) ?>"><?= $s['company'] ?></span></td>
            <?php if (isAdmin()): ?>
            <td class="td-actions">
                <button class="btn btn-sm btn-outline" onclick="editStaff(<?= htmlspecialchars(json_encode($s)) ?>)">Edit</button>
                <?php if (isAdminIT()): ?>
                <button class="btn btn-sm btn-danger" onclick="confirmDeleteStaff(<?= $s['id'] ?>)">Delete</button>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
(function () {
    const searchInput   = document.getElementById('staffSearchInput');
    const companyFilter = document.getElementById('staffCompanyFilter');
    const deptFilter    = document.getElementById('staffDeptFilter');
    const tbody         = document.getElementById('staffTableBody');
    const countEl       = document.getElementById('staffCount');
    const noResults     = document.getElementById('staffNoResults');

    if (!searchInput || !tbody) return;

    function applyFilters() {
        const q       = searchInput.value.toLowerCase().trim();
        const company = companyFilter.value.toLowerCase();
        const dept    = deptFilter.value;
        const rows    = tbody.querySelectorAll('tr');
        let visible   = 0;

        rows.forEach(row => {
            const matchQ = !q ||
                row.dataset.name.includes(q) ||
                row.dataset.staffno.includes(q) ||
                row.dataset.position.includes(q);
            const matchCompany = !company || row.dataset.company === company;
            const matchDept    = !dept    || row.dataset.dept === dept;

            if (matchQ && matchCompany && matchDept) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        if (countEl) countEl.textContent = `Showing ${visible} record(s)`;
        if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
    }

    // Live search as user types
    let debounceTimer;
    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(applyFilters, 150);
    });

    // Dropdowns filter instantly
    companyFilter.addEventListener('change', applyFilters);
    deptFilter.addEventListener('change', applyFilters);

    // Run on load to respect any pre-filled filters from URL
    applyFilters();
})();
</script>

<?php if (isAdmin()): ?>
<!-- Add/Edit Staff Modal -->
<div class="modal" id="addStaffModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="staffModalTitle">Add Staff</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form method="POST" action="actions/staff.php">
            <input type="hidden" name="action" id="staffAction" value="add">
            <input type="hidden" name="id" id="staffId" value="">
            <div class="form-grid">
                <div class="form-group">
                    <label>Staff No *</label>
                    <input type="text" name="staff_no" id="f_sno" required>
                </div>
                <div class="form-group">
                    <label>Company *</label>
                    <select name="company" id="f_company" required>
                        <option value="FJB">FGV Johor Bulkers (FJB)</option>
                        <option value="FBSB">FGV Bulkers Sdn Bhd (FBSB)</option>
                    </select>
                </div>
                <div class="form-group form-full">
                    <label>Full Name *</label>
                    <input type="text" name="name" id="f_sname" required>
                </div>
                <div class="form-group form-full">
                    <label>Position</label>
                    <input type="text" name="position" id="f_spos">
                </div>
                <div class="form-group form-full">
                    <label>Department</label>
                    <select name="department_id" id="f_sdept">
                        <option value="">— None —</option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>">[<?= $d['company'] ?>] <?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group form-full">
                    <label>Email</label>
                    <input type="email" name="email" id="f_semail">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete confirm -->
<div class="modal" id="deleteStaffModal">
    <div class="modal-box modal-sm">
        <div class="modal-header"><h3>Confirm Delete</h3><button class="modal-close" onclick="closeModal()">×</button></div>
        <p style="padding:1rem 0;">Delete this staff record? This cannot be undone.</p>
        <form method="POST" action="actions/staff.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteStaffId" value="">
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>
<script>
function editStaff(data) {
    document.getElementById('staffModalTitle').textContent = 'Edit Staff';
    document.getElementById('staffAction').value = 'edit';
    document.getElementById('staffId').value = data.id;
    document.getElementById('f_sno').value = data.staff_no;
    document.getElementById('f_company').value = data.company;
    document.getElementById('f_sname').value = data.name;
    document.getElementById('f_spos').value = data.position || '';
    document.getElementById('f_sdept').value = data.department_id || '';
    document.getElementById('f_semail').value = data.email || '';
    openModal('addStaffModal');
}
function confirmDeleteStaff(id) {
    document.getElementById('deleteStaffId').value = id;
    openModal('deleteStaffModal');
}
</script>
<?php endif; ?>
