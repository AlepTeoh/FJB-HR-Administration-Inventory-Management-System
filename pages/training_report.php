<?php
// pages/training_report.php - Training Attendance Report with Filters
require_once __DIR__ . '/../includes/config.php';
$pdo = getDB();

// 1. Handle Filters
$dept_id = isset($_GET['dept']) ? $_GET['dept'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : date('Y'); // Default to current year

// 2. Fetch Data for Select Inputs
$departments = $pdo->query("SELECT id, name, company FROM departments ORDER BY company, name")->fetchAll();

// 3. Build Query Conditions
$conditions = ["YEAR(tc.training_date) = ?"];
$params = [$year];

if ($dept_id) {
    $conditions[] = "s.department_id = ?";
    $params[] = $dept_id;
}

if ($month) {
    $conditions[] = "MONTH(tc.training_date) = ?";
    $params[] = $month;
}

$where_sql = "WHERE " . implode(" AND ", $conditions);

// 4. Fetch Report Data
$sql = "SELECT 
            ta.*, 
            s.staff_no, 
            s.name as staff_name, 
            d.name as dept_name, 
            d.company,
            tc.code as course_code, 
            tc.title as course_title, 
            tc.training_date
        FROM training_attendances ta
        JOIN staff s ON ta.staff_id = s.id
        JOIN training_courses tc ON ta.course_id = tc.id
        LEFT JOIN departments d ON s.department_id = d.id
        $where_sql
        ORDER BY tc.training_date DESC, d.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// 5. Calculate Summary Stats for the filtered view
$total_attendees = count($reports);
$unique_courses = count(array_unique(array_column($reports, 'course_id')));
?>

<div class="page-header">
    <div>
        <h2>Training Report</h2>
        <p class="page-subtitle">Analysis of staff training attendance and participation</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-outline btn-sm" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px;"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
            Print Report
        </button>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <form method="GET" class="filter-bar" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end;">
            <input type="hidden" name="page" value="training_report">
            
            <div class="form-group" style="margin-bottom:0;">
                <label style="font-size: 0.75rem; color: var(--slate-500);">Department</label>
                <select name="dept" class="filter-select" style="min-width: 200px;">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $dept_id == $d['id'] ? 'selected' : '' ?>>
                            [<?= $d['company'] ?>] <?= htmlspecialchars($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label style="font-size: 0.75rem; color: var(--slate-500);">Month</label>
                <select name="month" class="filter-select">
                    <option value="">All Months</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label style="font-size: 0.75rem; color: var(--slate-500);">Year</label>
                <select name="year" class="filter-select">
                    <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-sm" style="height: 38px;">Apply Filters</button>
            <a href="?page=training_report" class="btn btn-ghost btn-sm" style="height: 38px; line-height: 38px;">Reset</a>
        </form>
    </div>
</div>

<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="stat-card card">
        <span class="stat-label">Total Attendances</span>
        <span class="stat-value"><?= $total_attendees ?></span>
    </div>
    <div class="stat-card card">
        <span class="stat-label">Unique Courses</span>
        <span class="stat-value"><?= $unique_courses ?></span>
    </div>
    <div class="stat-card card">
        <span class="stat-label">Period</span>
        <span class="stat-value" style="font-size: 1.1rem;">
            <?= $month ? date('F', mktime(0, 0, 0, $month, 1)) : 'Full Year' ?> <?= $year ?>
        </span>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Training Date</th>
                    <th>Course</th>
                    <th>Staff Info</th>
                    <th>Department</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="5" class="empty-state">No training records found for the selected filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $r): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: var(--slate-700);">
                                    <?= $r['training_date'] ? date('d M Y', strtotime($r['training_date'])) : '<span style="color:var(--slate-400)">N/A</span>' ?>
                                </div>
                            </td>
                            <td>
                                <div style="max-width: 300px;">
                                    <code class="training-code" style="font-size: 0.7rem;"><?= htmlspecialchars($r['course_code']) ?></code>
                                    <div style="font-size: 0.85rem; font-weight: 500; margin-top: 2px;"><?= htmlspecialchars($r['course_title']) ?></div>
                                </div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($r['staff_name']) ?></strong>
                                <div style="font-size: 0.75rem; color: var(--slate-500);"><?= htmlspecialchars($r['staff_no']) ?></div>
                            </td>
                            <td>
                                <span class="dept-badge"><?= htmlspecialchars($r['dept_name']) ?></span>
                                <div style="font-size: 0.7rem; color: var(--slate-400); margin-top: 2px;"><?= $r['company'] ?></div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $r['status'])) ?>">
                                    <?= $r['status'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@media print {
    .filter-bar, .header-actions, .sidebar, .navbar { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
    .stat-card { border: 1px solid #eee !important; }
}
</style>