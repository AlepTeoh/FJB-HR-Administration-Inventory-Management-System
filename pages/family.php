<?php
// pages/family.php
require_once __DIR__ . '/../includes/config.php';
$pdo  = getDB();
$user = currentUser();
$search = $_GET['search'] ?? '';

$where = '';
$params = [];
if (!isAdmin()) {
    $where = "WHERE f.department = ?";
    $params[] = $user['department'];
} elseif ($search) {
    $where = "WHERE (f.employee_name LIKE ? OR f.family_member_name LIKE ? OR f.department LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$stmt = $pdo->prepare("SELECT * FROM family_members f $where ORDER BY f.employee_name, f.family_member_name");
$stmt->execute($params);
$records = $stmt->fetchAll();

$departments = ['HR','IT','Operations','Marketing','Account','Admin','FMU','Safety','Shipping','Procurement','Engineering','Stock','TQM/LAB','FELSCO'];
$relationships = ['Spouse','Child','Parent','Sibling'];
?>

<div class="page-header">
    <div>
        <h2>Family Member Information</h2>
        <p class="page-subtitle">
            <?= isAdmin() ? 'Manage staff family member records' : 'View your family records' ?>
        </p>
    </div>
    <?php if (isAdmin()): ?>
    <button class="btn btn-primary" onclick="resetFamilyModal(); openModal('addFamilyModal')">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Add Record
    </button>
    <?php endif; ?>
</div>

<?php if (isAdmin()): ?>
<form method="GET" class="search-bar">
    <input type="hidden" name="page" value="family">
    <div class="search-input-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="text" name="search" placeholder="Search by employee, family member, or department..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <button type="submit" class="btn btn-outline">Search</button>
    <?php if ($search): ?><a href="?page=family" class="btn btn-ghost">Clear</a><?php endif; ?>
</form>
<?php endif; ?>

<div class="card">
    <?php if (empty($records)): ?>
    <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
        <p>No family records found.</p>
    </div>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Employee</th>
                <th>Dept</th>
                <th>Family Member</th>
                <th>Relationship</th>
                <th>Date of Birth</th>
                <th>Emergency Contact</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($records as $i => $r): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($r['employee_name']) ?></td>
            <td><span class="dept-badge"><?= htmlspecialchars($r['department']) ?></span></td>
            <td><?= htmlspecialchars($r['family_member_name']) ?></td>
            <td><span class="rel-badge rel-<?= strtolower($r['relationship']) ?>"><?= $r['relationship'] ?></span></td>
            <td><?= $r['date_of_birth'] ? date('d M Y', strtotime($r['date_of_birth'])) : '—' ?></td>
            <td>
                <?php if ($r['emergency_contact'] === 'Yes'): ?>
                <span class="badge-yes">✓ Yes</span>
                <?php else: ?>
                <span class="badge-no">No</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($r['phone_number'] ?? '—') ?></td>
            <td class="td-actions">
                <?php if (isAdmin()): ?>
                <button class="btn btn-sm btn-outline" onclick="editFamily(<?= htmlspecialchars(json_encode($r)) ?>)">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="confirmFamilyDelete(<?= $r['id'] ?>)">Delete</button>
                <?php else: ?>
                <button class="btn btn-sm btn-ghost" onclick="openFamilyRequest(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['family_member_name'])) ?>')">Request Update</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php if (isAdmin()): ?>
<!-- Add/Edit Modal -->
<div class="modal" id="addFamilyModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="familyModalTitle">Add Family Record</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form method="POST" action="actions/family.php">
            <input type="hidden" name="action" id="familyAction" value="add">
            <input type="hidden" name="id" id="familyId" value="">
            <div class="form-grid">
                <div class="form-group">
                    <label>Employee Name *</label>
                    <input type="text" name="employee_name" id="ff_emp" required>
                </div>
                <div class="form-group">
                    <label>Department *</label>
                    <select name="department" id="ff_dept" required>
                        <option value="">Select</option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Family Member Name *</label>
                    <input type="text" name="family_member_name" id="ff_fname" required>
                </div>
                <div class="form-group">
                    <label>Relationship *</label>
                    <select name="relationship" id="ff_rel" required>
                        <option value="">Select</option>
                        <?php foreach ($relationships as $r): ?>
                        <option value="<?= $r ?>"><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" id="ff_dob">
                </div>
                <div class="form-group">
                    <label>Emergency Contact</label>
                    <select name="emergency_contact" id="ff_ec">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>
                <div class="form-group form-full">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" id="ff_phone" placeholder="e.g. 012-3456789">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Record</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete modal -->
<div class="modal" id="deleteFamilyModal">
    <div class="modal-box modal-sm">
        <div class="modal-header">
            <h3>Confirm Delete</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <p style="padding:1rem 0;">Are you sure you want to delete this family record?</p>
        <form method="POST" action="actions/family.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="familyDeleteId" value="">
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Staff request modal -->
<div class="modal" id="familyRequestModal">
    <div class="modal-box modal-sm">
        <div class="modal-header">
            <h3>Request Update</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form method="POST" action="actions/request.php">
            <input type="hidden" name="record_type" value="Family Information">
            <input type="hidden" name="record_id" id="freqId" value="">
            <input type="hidden" name="record_reference" id="freqRef" value="">
            <div class="form-group">
                <label>Your Message *</label>
                <textarea name="message" rows="4" placeholder="Describe what needs to be updated..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Send Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function editFamily(data) {
    document.getElementById('familyModalTitle').textContent = 'Edit Family Record';
    document.getElementById('familyAction').value = 'edit';
    document.getElementById('familyId').value = data.id;
    document.getElementById('ff_emp').value = data.employee_name;
    document.getElementById('ff_dept').value = data.department;
    document.getElementById('ff_fname').value = data.family_member_name;
    document.getElementById('ff_rel').value = data.relationship;
    document.getElementById('ff_dob').value = data.date_of_birth || '';
    document.getElementById('ff_ec').value = data.emergency_contact;
    document.getElementById('ff_phone').value = data.phone_number || '';
    openModal('addFamilyModal');
}
function resetFamilyModal() {
    document.getElementById('familyModalTitle').textContent = 'Add Family Record';
    document.getElementById('familyAction').value = 'add';
    document.getElementById('familyId').value = '';
    document.querySelector('#addFamilyModal form').reset();
}
function confirmFamilyDelete(id) {
    document.getElementById('familyDeleteId').value = id;
    openModal('deleteFamilyModal');
}
function openFamilyRequest(id, ref) {
    document.getElementById('freqId').value = id;
    document.getElementById('freqRef').value = ref;
    openModal('familyRequestModal');
}
</script>
