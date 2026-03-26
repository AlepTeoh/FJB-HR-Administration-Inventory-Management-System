<?php
// pages/staffid.php - Both admin_it and admin_hr: manage staff IDs for staff users
require_once __DIR__ . '/../includes/config.php';
$pdo = getDB();
$users = $pdo->query("SELECT u.*, d.name as dept_name FROM users u LEFT JOIN departments d ON u.department_id=d.id WHERE u.role='staff' ORDER BY u.name")->fetchAll();
?>
<div class="page-header">
    <div>
        <h2>Staff ID Management</h2>
        <p class="page-subtitle"><?= count($users) ?> staff account(s) — Assign or update Staff IDs</p>
    </div>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Position</th>
                <th>Department</th>
                <th>Staff ID</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
            <td style="font-size:.85rem;"><?= htmlspecialchars($u['position']??'—') ?></td>
            <td><?= htmlspecialchars($u['dept_name']??'—') ?></td>
            <td>
                <?php if ($u['staff_no']): ?>
                    <code style="font-size:.82rem;color:#6366f1;"><?= htmlspecialchars($u['staff_no']) ?></code>
                <?php else: ?>
                    <span style="color:#f59e0b;font-size:.82rem;">⚠ Not assigned</span>
                <?php endif; ?>
            </td>
            <td><?= $u['is_active']
                ? '<span class="status-badge status-completed">Active</span>'
                : '<span class="status-badge status-scheduled">Inactive</span>' ?>
            </td>
            <td class="td-actions">
                <button class="btn btn-sm btn-outline" onclick="editStaffId(<?= htmlspecialchars(json_encode([
                    'id'       => $u['id'],
                    'name'     => $u['name'],
                    'staff_no' => $u['staff_no'] ?? '',
                ])) ?>)">
                    <?= $u['staff_no'] ? 'Change ID' : 'Assign ID' ?>
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Edit Staff ID Modal -->
<div class="modal" id="staffIdModal">
    <div class="modal-box modal-sm">
        <div class="modal-header">
            <h3 id="staffIdModalTitle">Assign Staff ID</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form method="POST" action="actions/staffid.php">
            <input type="hidden" name="id" id="sid_uid" value="">
            <div style="padding:.5rem 0 1rem;">
                <p style="margin-bottom:1rem;color:var(--text-muted);font-size:.9rem;">
                    Updating Staff ID for: <strong id="sid_name"></strong>
                </p>
                <div class="form-group">
                    <label>Staff ID *</label>
                    <input type="text" name="staff_no" id="sid_staffno" required
                           placeholder="e.g. 0000042"
                           style="font-family:monospace;letter-spacing:.05em;">
                    <small style="color:var(--text-muted);display:block;margin-top:.4rem;">
                        This ID will be used by the staff member to log in.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Staff ID</button>
            </div>
        </form>
    </div>
</div>

<script>
function editStaffId(u) {
    document.getElementById('staffIdModalTitle').textContent = u.staff_no ? 'Change Staff ID' : 'Assign Staff ID';
    document.getElementById('sid_uid').value    = u.id;
    document.getElementById('sid_name').textContent = u.name;
    document.getElementById('sid_staffno').value = u.staff_no || '';
    openModal('staffIdModal');
}
</script>
