<?php
// pages/users.php - Admin IT only: manage user accounts
require_once __DIR__ . '/../includes/config.php';
$pdo = getDB();
$departments = $pdo->query("SELECT * FROM departments ORDER BY company,name")->fetchAll();
$users = $pdo->query("SELECT u.*, d.name as dept_name FROM users u LEFT JOIN departments d ON u.department_id=d.id ORDER BY u.role, u.name")->fetchAll();
?>
<div class="page-header">
    <div><h2>User Accounts</h2><p class="page-subtitle"><?= count($users) ?> user(s) — Manage system access</p></div>
    <button class="btn btn-primary" onclick="openModal('addUserModal')">+ Add User</button>
</div>
<div class="card">
    <table class="table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Staff No</th><th>Department</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
            <td style="font-size:.85rem;"><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="role-badge <?= str_replace('_','-',$u['role']) ?>"><?= getRoleLabel($u['role']) ?></span></td>
            <td><code style="font-size:.82rem;color:#6366f1;"><?= htmlspecialchars($u['staff_no']??'—') ?></code></td>
            <td><?= htmlspecialchars($u['dept_name']??'—') ?></td>
            <td><?= $u['is_active']?'<span class="status-badge status-completed">Active</span>':'<span class="status-badge status-scheduled">Inactive</span>' ?></td>
            <td class="td-actions">
                <button class="btn btn-sm btn-outline" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">Edit</button>
                <?php if ($u['id'] != currentUser()['id']): ?>
                <button class="btn btn-sm btn-danger" onclick="confirmDeleteUser(<?= $u['id'] ?>)">Delete</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit User Modal -->
<div class="modal" id="addUserModal">
    <div class="modal-box">
        <div class="modal-header"><h3 id="userModalTitle">Add User</h3><button class="modal-close" onclick="closeModal()">×</button></div>
        <form method="POST" action="actions/users.php">
            <input type="hidden" name="action" id="userAction" value="add">
            <input type="hidden" name="id" id="userId" value="">
            <div class="form-grid">
                <div class="form-group"><label>Full Name *</label><input type="text" name="name" id="f_uname" required></div>
                <div class="form-group"><label>Email *</label><input type="email" name="email" id="f_uemail" required></div>
                <div class="form-group"><label>Role *</label>
                    <select name="role" id="f_urole" required>
                        <option value="staff">Staff</option>
                        <option value="admin_hr">Admin (HR)</option>
                        <option value="admin_it">Admin (IT)</option>
                    </select>
                </div>
                <div class="form-group"><label>Staff No</label><input type="text" name="staff_no" id="f_ustaffno"></div>
                <div class="form-group"><label>Department</label>
                    <select name="department_id" id="f_udept">
                        <option value="">— None —</option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>">[<?= $d['company'] ?>] <?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Position</label><input type="text" name="position" id="f_upos"></div>
                <div class="form-group form-full"><label>Password <span id="pwLabel">(required for new user)</span></label><input type="password" name="password" id="f_upw" placeholder="Leave blank to keep unchanged"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save User</button>
            </div>
        </form>
    </div>
</div>
<div class="modal" id="deleteUserModal">
    <div class="modal-box modal-sm">
        <div class="modal-header"><h3>Delete User</h3><button class="modal-close" onclick="closeModal()">×</button></div>
        <p style="padding:1rem 0;">Delete this user account? This cannot be undone.</p>
        <form method="POST" action="actions/users.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteUserId" value="">
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>
<script>
function editUser(u) {
    document.getElementById('userModalTitle').textContent='Edit User';
    document.getElementById('userAction').value='edit';
    document.getElementById('userId').value=u.id;
    document.getElementById('f_uname').value=u.name;
    document.getElementById('f_uemail').value=u.email;
    document.getElementById('f_urole').value=u.role;
    document.getElementById('f_ustaffno').value=u.staff_no||'';
    document.getElementById('f_udept').value=u.department_id||'';
    document.getElementById('f_upos').value=u.position||'';
    document.getElementById('f_upw').required=false;
    document.getElementById('pwLabel').textContent='(leave blank to keep current)';
    openModal('addUserModal');
}
function confirmDeleteUser(id) {
    document.getElementById('deleteUserId').value=id;
    openModal('deleteUserModal');
}
</script>
