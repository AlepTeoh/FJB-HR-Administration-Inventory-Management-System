<?php
// pages/training.php
require_once __DIR__ . '/../includes/config.php';
$pdo  = getDB();
$user = currentUser();

// Staff are forced to 'list' view. Admins default to 'by_dept'
$view       = $_GET['view'] ?? (isAdmin() ? 'by_dept' : 'list');
if (!isAdmin() && $view !== 'list') {
    $view = 'list';
}

$dept_filter= $_GET['dept'] ?? '';
$company_f  = $_GET['company'] ?? '';
$search     = $_GET['q'] ?? '';
$course_id  = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$status_f   = $_GET['status'] ?? '';

$allDepts = $pdo->query("SELECT * FROM departments ORDER BY company, name")->fetchAll();
$allCourses = $pdo->query("SELECT * FROM training_courses ORDER BY code")->fetchAll();

// ===== VIEW: BY DEPARTMENT =====
if ($view === 'by_dept') {
    $conditions = [];
    $params = [];
    if ($dept_filter) { $conditions[] = 'd.id = ?'; $params[] = $dept_filter; }
    if ($company_f) { $conditions[] = 'd.company = ?'; $params[] = $company_f; }
    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    $stmt = $pdo->prepare("
        SELECT d.*, 
               COUNT(DISTINCT s.id) as staff_count,
               COUNT(DISTINCT ta.id) as training_count,
               COUNT(DISTINCT ta.course_id) as unique_courses
        FROM departments d
        LEFT JOIN staff s ON s.department_id = d.id
        LEFT JOIN training_attendances ta ON ta.staff_id = s.id
        $where
        GROUP BY d.id
        ORDER BY d.company, d.name
    ");
    $stmt->execute($params);
    $dept_summaries = $stmt->fetchAll();
}

// ===== VIEW: COURSE ATTENDEES =====
if ($view === 'course' && $course_id) {
    $course = $pdo->prepare("SELECT * FROM training_courses WHERE id = ?");
    $course->execute([$course_id]);
    $course = $course->fetch();
    
    $stmt = $pdo->prepare("
        SELECT ta.*, s.staff_no, s.name, s.position, d.name as dept_name, d.company
        FROM training_attendances ta
        JOIN staff s ON ta.staff_id = s.id
        LEFT JOIN departments d ON s.department_id = d.id
        ORDER BY d.name, s.name
    ");
    $stmt->execute();
    $attendees = $stmt->fetchAll();
}

// ===== VIEW: DEPARTMENT DETAIL =====
if ($view === 'dept_detail' && $dept_filter) {
    $deptInfo = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
    $deptInfo->execute([$dept_filter]);
    $deptInfo = $deptInfo->fetch();
    
    $stmt = $pdo->prepare("
        SELECT s.staff_no, s.name, s.position,
               GROUP_CONCAT(CONCAT(tc.code, '|', tc.id) ORDER BY tc.code SEPARATOR ';;;') as trainings_raw
        FROM staff s
        LEFT JOIN training_attendances ta ON ta.staff_id = s.id
        LEFT JOIN training_courses tc ON ta.course_id = tc.id
        WHERE s.department_id = ?
        GROUP BY s.id
        ORDER BY s.name
    ");
    $stmt->execute([$dept_filter]);
    $dept_staff = $stmt->fetchAll();
    
    $stmt2 = $pdo->prepare("
        SELECT DISTINCT tc.*, COUNT(DISTINCT ta.staff_id) as attendee_count
        FROM training_courses tc
        JOIN training_attendances ta ON ta.course_id = tc.id
        JOIN staff s ON ta.staff_id = s.id
        WHERE s.department_id = ?
        GROUP BY tc.id
        ORDER BY tc.code
    ");
    $stmt2->execute([$dept_filter]);
    $dept_courses = $stmt2->fetchAll();
}

// ===== VIEW: LIST (classic & Staff View) =====
if ($view === 'list') {
    $conditions = [];
    $params = [];
    
    // STAFF ISOLATION: Staff can only see their own records
    if (!isAdmin()) {
        $conditions[] = 'ta.staff_id = (SELECT id FROM staff WHERE staff_no = ?)';
        $params[] = $user['staff_no'];
    } elseif ($dept_filter) {
        $conditions[] = 's.department_id = ?';
        $params[] = $dept_filter;
    }
    
    if ($company_f) { $conditions[] = 's.company = ?'; $params[] = $company_f; }
    if ($search) {
        $conditions[] = '(s.name LIKE ? OR tc.code LIKE ? OR tc.title LIKE ?)';
        $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
    }
    if ($status_f) { $conditions[] = 'ta.status = ?'; $params[] = $status_f; }
    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    $stmt = $pdo->prepare("
        SELECT ta.*, s.staff_no, s.name as emp_name, s.position,
               d.name as dept_name, d.company,
               tc.code as training_code, tc.title as training_title
        FROM training_attendances ta
        JOIN staff s ON ta.staff_id = s.id
        LEFT JOIN departments d ON s.department_id = d.id
        JOIN training_courses tc ON ta.course_id = tc.id
        $where
        ORDER BY d.name, s.name, tc.code
    ");
    $stmt->execute($params);
    $records = $stmt->fetchAll();
}
?>

<div class="page-header">
    <div>
        <h2><?= isAdmin() ? 'Training Records' : 'My Training Records' ?></h2>
        <p class="page-subtitle"><?= isAdmin() ? 'Training attendance by department and course' : 'View your personal training history' ?></p>
    </div>
    
    <?php if (isAdmin()): ?>
    <div style="display:flex; gap:.5rem;">
        <a href="?page=import_training" class="btn btn-outline btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px; vertical-align:middle;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            Import CSV
        </a>
        <button class="btn btn-outline btn-sm" onclick="openModal('addCourseModal')">+ Course</button>
        <button class="btn btn-primary btn-sm" onclick="openModal('addAttendanceModal')">+ Attendance</button>
    </div>
    <?php endif; ?>
</div>

<div class="filter-bar" style="flex-wrap:wrap; gap:.5rem; align-items:center;">
    <?php if (isAdmin()): ?>
    <div class="filter-tabs">
        <a href="?page=training&view=by_dept<?= $dept_filter?"&dept=$dept_filter":'' ?>" class="filter-tab <?= $view==='by_dept'?'active':'' ?>">📊 By Department</a>
        <a href="?page=training&view=list" class="filter-tab <?= $view==='list'?'active':'' ?>">📋 List View</a>
        <a href="?page=training&view=courses" class="filter-tab <?= $view==='courses'?'active':'' ?>">📚 Courses</a>
    </div>
    <?php endif; ?>
    
    <?php if ($view === 'list'): ?>
    <form method="GET" action="" style="display:flex; gap:.5rem; flex-wrap:wrap; margin-left:auto;">
        <input type="hidden" name="page" value="training">
        <input type="hidden" name="view" value="list">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search…" class="filter-search" style="width:180px;">
        <?php if (isAdmin()): ?>
        <select name="company" class="filter-select">
            <option value="">All Companies</option>
            <option value="FJB" <?= $company_f==='FJB'?'selected':'' ?>>FJB</option>
            <option value="FBSB" <?= $company_f==='FBSB'?'selected':'' ?>>FBSB</option>
        </select>
        <select name="dept" class="filter-select">
            <option value="">All Depts</option>
            <?php foreach($allDepts as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $dept_filter==$d['id']?'selected':'' ?>>[<?= $d['company'] ?>] <?= htmlspecialchars(substr($d['name'],0,30)) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select name="status" class="filter-select">
            <option value="">All Status</option>
            <option value="Completed" <?= $status_f==='Completed'?'selected':'' ?>>Completed</option>
            <option value="In Progress" <?= $status_f==='In Progress'?'selected':'' ?>>In Progress</option>
            <option value="Scheduled" <?= $status_f==='Scheduled'?'selected':'' ?>>Scheduled</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="?page=training&view=list" class="btn btn-ghost btn-sm">Clear</a>
    </form>
    <?php endif; ?>
</div>

<?php if ($view === 'by_dept'): ?>
<div class="dept-grid">
<?php foreach ($dept_summaries as $dept): ?>
<div class="dept-training-card">
    <div class="dept-card-header">
        <div>
            <span class="company-badge company-<?= strtolower($dept['company']) ?>"><?= $dept['company'] ?></span>
            <h4><?= htmlspecialchars($dept['name']) ?></h4>
        </div>
        <a href="?page=training&view=dept_detail&dept=<?= $dept['id'] ?>" class="btn btn-sm btn-outline">View Details →</a>
    </div>
    <div class="dept-card-stats">
        <div class="dstat"><span class="dstat-val"><?= $dept['staff_count'] ?></span><span class="dstat-lbl">Staff</span></div>
        <div class="dstat"><span class="dstat-val"><?= $dept['training_count'] ?></span><span class="dstat-lbl">Attendances</span></div>
        <div class="dstat"><span class="dstat-val"><?= $dept['unique_courses'] ?></span><span class="dstat-lbl">Courses</span></div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php elseif ($view === 'dept_detail' && $dept_filter): ?>
<div style="margin-bottom:1rem;"><a href="?page=training&view=by_dept" class="btn btn-ghost btn-sm">← Back to Departments</a></div>
<?php if ($deptInfo): ?>
<h3 style="margin-bottom:1rem;"><?= htmlspecialchars($deptInfo['name']) ?> <span class="company-badge company-<?= strtolower($deptInfo['company']) ?>"><?= $deptInfo['company'] ?></span></h3>
<?php if (!empty($dept_courses)): ?>
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><h3>Courses Attended</h3></div>
    <table class="table">
        <thead><tr><th>Code</th><th>Training Title</th><th>Attendees</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($dept_courses as $c): ?>
        <tr>
            <td><code class="training-code"><?= htmlspecialchars($c['code']) ?></code></td>
            <td><?= htmlspecialchars($c['title']) ?></td>
            <td><span class="badge-count" style="position:static;display:inline-block;"><?= $c['attendee_count'] ?></span></td>
            <td><a href="?page=training&view=course&course_id=<?= $c['id'] ?>" class="btn btn-sm btn-ghost">All Attendees</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<div class="card">
    <div class="card-header"><h3>Staff Training Attendance</h3></div>
    <table class="table">
        <thead><tr><th>Staff No</th><th>Name</th><th>Position</th><th>Training Codes</th></tr></thead>
        <tbody>
        <?php foreach ($dept_staff as $s): ?>
        <tr>
            <td><code style="font-size:.82rem;color:#6366f1;"><?= htmlspecialchars($s['staff_no']) ?></code></td>
            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
            <td style="font-size:.83rem;color:#64748b;"><?= htmlspecialchars($s['position']) ?></td>
            <td>
                <?php if ($s['trainings_raw']): ?>
                <?php foreach (explode(';;;', $s['trainings_raw']) as $t): ?>
                    <?php [$code, $cid] = explode('|', $t); ?>
                    <a href="?page=training&view=course&course_id=<?= $cid ?>" class="training-code-tag"><?= htmlspecialchars($code) ?></a>
                <?php endforeach; ?>
                <?php else: ?><span style="color:#94a3b8; font-size:.82rem;">No training</span><?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php elseif ($view === 'course' && $course_id): ?>
<div style="margin-bottom:1rem;"><a href="?page=training&view=by_dept" class="btn btn-ghost btn-sm">← Back</a></div>
<?php if ($course): ?>
<div class="card" style="margin-bottom:1rem; padding:1.25rem;">
    <div style="display:flex; gap:1rem; align-items:flex-start;">
        <div style="background:#6366f1;color:white;padding:.5rem .9rem;border-radius:8px;font-weight:700;font-size:1.1rem;"><?= htmlspecialchars($course['code']) ?></div>
        <div>
            <h3 style="margin:0 0 .25rem;"><?= htmlspecialchars($course['title']) ?></h3>
            <span style="color:#64748b; font-size:.85rem;"><?= count($attendees) ?> attendee(s)</span>
        </div>
    </div>
</div>
<div class="card">
    <table class="table">
        <thead><tr><th>Staff No</th><th>Name</th><th>Dept</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($attendees as $a): ?>
        <tr>
            <td><code style="font-size:.82rem;color:#6366f1;"><?= htmlspecialchars($a['staff_no']) ?></code></td>
            <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
            <td><span class="dept-badge"><?= htmlspecialchars($a['dept_name']??'—') ?></span></td>
            <td><span class="status-badge status-<?= strtolower(str_replace(' ','-',$a['status'])) ?>"><?= $a['status'] ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php elseif ($view === 'courses'): ?>
<div class="card">
    <div class="card-header">
        <h3>All Training Courses (<?= count($allCourses) ?>)</h3>
        <input type="text" id="courseSearch" placeholder="Search courses…" class="filter-search" oninput="filterCourses(this.value)" style="width:220px;">
    </div>
    <table class="table" id="courseTable">
        <thead><tr><th>Code</th><th>Training Title</th><th>Attendees</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($allCourses as $c): ?>
        <?php 
            $attCount = $pdo->prepare("SELECT COUNT(*) FROM training_attendances WHERE course_id = ?");
            $attCount->execute([$c['id']]); $cnt = $attCount->fetchColumn();
        ?>
        <tr class="course-row">
            <td><code class="training-code"><?= htmlspecialchars($c['code']) ?></code></td>
            <td><?= htmlspecialchars($c['title']) ?></td>
            <td><?= $cnt ?></td>
            <td><a href="?page=training&view=course&course_id=<?= $c['id'] ?>" class="btn btn-sm btn-ghost">Attendees</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php elseif ($view === 'list'): ?>
<div class="card">
    <?php if (empty($records)): ?>
    <div class="empty-state"><p>No training records found.</p></div>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <?php if (isAdmin()): ?><th>Staff No</th><th>Name</th><th>Dept</th><?php endif; ?>
                <th>Code</th><th>Training Title</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($records as $r): ?>
        <tr>
            <?php if (isAdmin()): ?>
            <td><code style="font-size:.82rem;color:#6366f1;"><?= htmlspecialchars($r['staff_no']) ?></code></td>
            <td><?= htmlspecialchars($r['emp_name']) ?></td>
            <td><span class="dept-badge"><?= htmlspecialchars($r['dept_name']??'—') ?></span></td>
            <?php endif; ?>
            <td><code class="training-code"><?= htmlspecialchars($r['training_code']) ?></code></td>
            <td style="max-width:280px; font-size:.85rem;"><?= htmlspecialchars($r['training_title']) ?></td>
            <td><span class="status-badge status-<?= strtolower(str_replace(' ','-',$r['status'])) ?>"><?= $r['status'] ?></span></td>
            <td class="td-actions">
                <?php if (isAdmin()): ?>
                <button class="btn btn-sm btn-outline" onclick="editAttendance(<?= htmlspecialchars(json_encode($r)) ?>)">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="confirmDeleteAtt(<?= $r['id'] ?>)">Delete</button>
                <?php else: ?>
                <button class="btn btn-sm btn-ghost" onclick="openRequestModal(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['training_code'])) ?>')">Request Update</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (isAdmin()): ?>
<div class="modal" id="addCourseModal">
    <div class="modal-box">
        <div class="modal-header"><h3>Add Training Course</h3><button class="modal-close" onclick="closeModal()">×</button></div>
        <form method="POST" action="actions/training.php">
            <input type="hidden" name="action" value="add_course">
            <div class="form-grid">
                <div class="form-group"><label>Course Code *</label><input type="text" name="code" required></div>
                <div class="form-group"><label>Company</label><select name="company"><option value="FJB">FJB</option><option value="FBSB">FBSB</option></select></div>
                <div class="form-group form-full"><label>Training Title *</label><input type="text" name="title" required></div>
                <div class="form-group"><label>Training Date</label><input type="date" name="training_date"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-primary">Save Course</button></div>
        </form>
    </div>
</div>

<div class="modal" id="addAttendanceModal">
    <div class="modal-box">
        <div class="modal-header"><h3 id="attModalTitle">Add Training Attendance</h3><button class="modal-close" onclick="closeModal()">×</button></div>
        <form method="POST" action="actions/training.php">
            <input type="hidden" name="action" id="attAction" value="add_attendance">
            <input type="hidden" name="id" id="attId" value="">
            <div class="form-grid">
                <div class="form-group form-full">
                    <label>Staff No / Name *</label>
                    <input type="text" name="staff_search" id="staffSearchInput" placeholder="Search..." oninput="searchStaff(this.value)" autocomplete="off">
                    <input type="hidden" name="staff_id" id="selectedStaffId">
                    <div id="staffDropdown" style="display:none; border:1px solid #e2e8f0; border-radius:8px; max-height:200px; overflow-y:auto; margin-top:4px; background:white; position:relative; z-index:100;"></div>
                </div>
                <div class="form-group form-full">
                    <label>Training Course *</label>
                    <select name="course_id" id="f_course_id" required>
                        <option value="">Select Course</option>
                        <?php foreach ($allCourses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['code']) ?> — <?= htmlspecialchars(substr($c['title'],0,60)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="f_att_status">
                        <option value="Completed">Completed</option><option value="In Progress">In Progress</option><option value="Scheduled">Scheduled</option>
                    </select>
                </div>
                <div class="form-group form-full"><label>Remarks</label><textarea name="remarks" id="f_att_remarks" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
        </form>
    </div>
</div>

<div class="modal" id="deleteAttModal">
    <div class="modal-box modal-sm">
        <div class="modal-header"><h3>Confirm Delete</h3><button class="modal-close" onclick="closeModal()">×</button></div>
        <p style="padding:1rem 0;">Delete this attendance record?</p>
        <form method="POST" action="actions/training.php">
            <input type="hidden" name="action" value="delete_attendance">
            <input type="hidden" name="id" id="deleteAttId" value="">
            <div class="modal-footer"><button type="submit" class="btn btn-danger">Delete</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="modal" id="requestModal">
    <div class="modal-box modal-sm">
        <div class="modal-header"><h3>Request Update</h3><button class="modal-close" onclick="closeModal()">×</button></div>
        <form method="POST" action="actions/request.php">
            <input type="hidden" name="record_type" value="Training Record">
            <input type="hidden" name="record_id" id="reqRecordId" value="">
            <input type="hidden" name="record_reference" id="reqRecordRef" value="">
            <div class="form-group">
                <label>Your Message *</label>
                <textarea name="message" rows="4" placeholder="Describe what needs to be updated..." required></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-primary">Send</button></div>
        </form>
    </div>
</div>

<script>
function filterCourses(q) {
    const rows = document.querySelectorAll('.course-row');
    q = q.toLowerCase();
    rows.forEach(r => { r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'; });
}

function editAttendance(data) {
    document.getElementById('attModalTitle').textContent = 'Edit Attendance';
    document.getElementById('attAction').value = 'edit_attendance';
    document.getElementById('attId').value = data.id;
    document.getElementById('f_course_id').value = data.course_id;
    document.getElementById('f_att_status').value = data.status;
    document.getElementById('f_att_remarks').value = data.remarks || '';
    openModal('addAttendanceModal');
}

function confirmDeleteAtt(id) { document.getElementById('deleteAttId').value = id; openModal('deleteAttModal'); }

function openRequestModal(id, ref) {
    document.getElementById('reqRecordId').value = id;
    document.getElementById('reqRecordRef').value = ref;
    openModal('requestModal');
}

let staffTimeout;
function searchStaff(q) {
    clearTimeout(staffTimeout);
    const dd = document.getElementById('staffDropdown');
    if (q.length < 2) { dd.style.display = 'none'; return; }
    staffTimeout = setTimeout(() => {
        fetch('actions/training.php?search_staff=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data.length) { dd.style.display = 'none'; return; }
                dd.innerHTML = data.map(s => 
                    `<div onclick="selectStaff(${s.id},'${s.staff_no} - ${s.name.replace(/'/g,"\\'")}','${s.name.replace(/'/g,"\\'")} (${s.staff_no})')" 
                          style="padding:.5rem .75rem; cursor:pointer; font-size:.9rem; border-bottom:1px solid #f1f5f9;"
                          onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                        <strong>${s.staff_no}</strong> — ${s.name} <span style="color:#94a3b8;font-size:.8rem;">${s.dept_name||''}</span>
                    </div>`
                ).join('');
                dd.style.display = 'block';
            });
    }, 300);
}

function selectStaff(id, displayVal, label) {
    document.getElementById('selectedStaffId').value = id;
    document.getElementById('staffSearchInput').value = displayVal;
    document.getElementById('staffDropdown').style.display = 'none';
}
</script>