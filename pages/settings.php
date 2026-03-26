<?php
// pages/settings.php - Admin IT only
require_once __DIR__ . '/../includes/config.php';
$pdo = getDB();

$modules = ['training'=>'Training Records','family'=>'Family Information','rooms'=>'Meeting Rooms','requests'=>'Update Requests','staff'=>'Staff Registry'];
$settings = [];
foreach ($modules as $key => $label) {
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key=?");
    $stmt->execute(['module_'.$key]);
    $settings[$key] = $stmt->fetchColumn() ?? '1';
}
?>
<div class="page-header">
    <div><h2>System Settings</h2><p class="page-subtitle">Control which modules are accessible to Admin HR and Staff roles</p></div>
</div>
<div class="card" style="max-width:600px;">
    <div class="card-header"><h3>Module Access Control</h3></div>
    <form method="POST" action="actions/settings.php">
        <div style="padding:0 1rem;">
        <?php foreach ($modules as $key => $label): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 0;border-bottom:1px solid #f1f5f9;">
            <div>
                <strong><?= $label ?></strong>
                <p style="color:#64748b;font-size:.85rem;margin:.2rem 0 0;">Module key: <code>module_<?= $key ?></code></p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="module_<?= $key ?>" value="1" <?= $settings[$key]?'checked':'' ?>>
                <span class="toggle-slider"></span>
            </label>
        </div>
        <?php endforeach; ?>
        </div>
        <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>

<div class="card" style="max-width:600px; margin-top:1.5rem;">
    <div class="card-header"><h3>Role Permissions Overview</h3></div>
    <div style="padding:1rem;">
        <table class="table">
            <thead><tr><th>Module</th><th>Admin IT</th><th>Admin HR</th><th>Staff</th></tr></thead>
            <tbody>
            <tr><td>Staff Registry</td><td>✅ Full CRUD</td><td>✅ Add/Edit</td><td>❌</td></tr>
            <tr><td>Training Records</td><td>✅ Full CRUD</td><td>✅ Add/Edit</td><td>👁️ View Only</td></tr>
            <tr><td>Family Information</td><td>✅ Full CRUD</td><td>✅ Add/Edit</td><td>👁️ View Only</td></tr>
            <tr><td>Meeting Rooms</td><td>✅ Full CRUD</td><td>✅ Book</td><td>✅ Book</td></tr>
            <tr><td>Update Requests</td><td>✅ Manage</td><td>✅ Manage</td><td>📝 Submit</td></tr>
            <tr><td>User Accounts</td><td>✅ Full CRUD</td><td>❌</td><td>❌</td></tr>
            <tr><td>System Settings</td><td>✅ Full</td><td>❌</td><td>❌</td></tr>
            </tbody>
        </table>
    </div>
</div>
