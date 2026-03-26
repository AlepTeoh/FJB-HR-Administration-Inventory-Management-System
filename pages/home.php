<?php
// pages/home.php
require_once __DIR__ . '/../includes/config.php';
$pdo  = getDB();
$user = currentUser();

// Logic: Admins see global stats, Staff see only their own data
if (isAdmin()) {
    // ADMIN STATS
    $totalStaff     = $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
    $totalTraining  = $pdo->query("SELECT COUNT(*) FROM training_attendances")->fetchColumn();
    $totalCourses   = $pdo->query("SELECT COUNT(*) FROM training_courses")->fetchColumn();
    $totalBookings  = $pdo->query("SELECT COUNT(*) FROM room_bookings WHERE booking_date >= CURDATE()")->fetchColumn();
    $pendingReqs    = $pdo->query("SELECT COUNT(*) FROM update_requests WHERE status='Pending'")->fetchColumn();

    // ADMIN: Recent training (Global)
    $recentTraining = $pdo->query("
        SELECT ta.status, ta.created_at, s.name as emp_name, s.staff_no,
               tc.code as training_code, tc.title as training_title, d.name as dept_name
        FROM training_attendances ta
        JOIN staff s ON ta.staff_id = s.id
        JOIN training_courses tc ON ta.course_id = tc.id
        LEFT JOIN departments d ON s.department_id = d.id
        ORDER BY ta.created_at DESC LIMIT 6
    ")->fetchAll();

    // ADMIN: Top departments
    $topDepts = $pdo->query("
        SELECT d.name, d.company, COUNT(ta.id) as cnt
        FROM departments d
        JOIN staff s ON s.department_id = d.id
        JOIN training_attendances ta ON ta.staff_id = s.id
        GROUP BY d.id ORDER BY cnt DESC LIMIT 5
    ")->fetchAll();

} else {
    // STAFF STATS (Personal Only)
    $stmtId = $pdo->prepare("SELECT id FROM staff WHERE staff_no = ?");
    $stmtId->execute([$user['staff_no']]);
    $myStaffId = $stmtId->fetchColumn();

    $stmtMyStats = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM training_attendances WHERE staff_id = ?) as my_total_training,
            (SELECT COUNT(*) FROM room_bookings WHERE booked_by_id = ? AND booking_date >= CURDATE()) as my_upcoming_bookings,
            (SELECT COUNT(*) FROM family_members WHERE staff_id = ?) as my_family_count
    ");
    $stmtMyStats->execute([$myStaffId, $user['id'], $myStaffId]);
    $myStats = $stmtMyStats->fetch();

    // STAFF: My Recent training only
    $stmtRecent = $pdo->prepare("
        SELECT ta.status, ta.created_at, tc.code as training_code, tc.title as training_title
        FROM training_attendances ta
        JOIN training_courses tc ON ta.course_id = tc.id
        WHERE ta.staff_id = ?
        ORDER BY ta.created_at DESC LIMIT 6
    ");
    $stmtRecent->execute([$myStaffId]);
    $recentTraining = $stmtRecent->fetchAll();
}

// Today's bookings
$todayBookings = $pdo->query("
    SELECT rb.*, mr.name as room_name, mr.color_class
    FROM room_bookings rb
    JOIN meeting_rooms mr ON rb.room_id = mr.id
    WHERE rb.booking_date = CURDATE()
    ORDER BY rb.start_time
")->fetchAll();
?>

<div class="page-header">
    <h2>Welcome back, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?> 👋</h2>
    <p class="page-subtitle"><?= date('l, d F Y') ?> &nbsp;·&nbsp; <?= getRoleLabel() ?></p>
</div>

<div class="stats-grid">
    <?php if (isAdmin()): ?>
        <div class="stat-card stat-blue">
            <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
            <div class="stat-info"><span class="stat-value"><?= $totalStaff ?></span><span class="stat-label">Total Staff</span></div>
        </div>
        <div class="stat-card stat-green">
            <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg></div>
            <div class="stat-info"><span class="stat-value"><?= $totalTraining ?></span><span class="stat-label">Training Records</span></div>
        </div>
        <div class="stat-card stat-amber">
            <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div>
            <div class="stat-info"><span class="stat-value"><?= $totalBookings ?></span><span class="stat-label">Upcoming Bookings</span></div>
        </div>
        <div class="stat-card stat-red">
            <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg></div>
            <div class="stat-info"><span class="stat-value"><?= $pendingReqs ?></span><span class="stat-label">Pending Requests</span></div>
        </div>
    <?php else: ?>
        <div class="stat-card stat-green">
            <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div>
            <div class="stat-info"><span class="stat-value"><?= $myStats['my_total_training'] ?></span><span class="stat-label">My Training Courses</span></div>
        </div>
        <div class="stat-card stat-amber">
            <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div>
            <div class="stat-info"><span class="stat-value"><?= $myStats['my_upcoming_bookings'] ?></span><span class="stat-label">My Upcoming Bookings</span></div>
        </div>
        <div class="stat-card stat-purple">
            <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg></div>
            <div class="stat-info"><span class="stat-value"><?= $myStats['my_family_count'] ?></span><span class="stat-label">Family Records</span></div>
        </div>
    <?php endif; ?>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h3><?= isAdmin() ? 'Recent Training Attendances' : 'My Recent Trainings' ?></h3>
            <a href="?page=training" class="btn btn-sm btn-ghost">View All →</a>
        </div>
        <?php if (empty($recentTraining)): ?>
            <div class="empty-state" style="padding:2rem;">No training records yet.</div>
        <?php else: ?>
            <table class="table">
                <thead><tr><?= isAdmin() ? '<th>Staff</th>' : '' ?><th>Code</th><th>Training</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recentTraining as $t): ?>
                <tr>
                    <?php if (isAdmin()): ?>
                    <td>
                        <div style="font-weight:500;"><?= htmlspecialchars($t['emp_name']) ?></div>
                        <div style="font-size:.78rem;color:#94a3b8;"><?= htmlspecialchars($t['dept_name'] ?? '') ?></div>
                    </td>
                    <?php endif; ?>
                    <td><code class="training-code"><?= htmlspecialchars($t['training_code']) ?></code></td>
                    <td style="font-size:.83rem;max-width:250px;"><?= htmlspecialchars(substr($t['training_title'], 0, 55)) ?>…</td>
                    <td><span class="status-badge status-<?= strtolower(str_replace(' ', '-', $t['status'])) ?>"><?= $t['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Today's Room Bookings</h3>
            <a href="?page=rooms" class="btn btn-sm btn-ghost">View All →</a>
        </div>
        <?php if (empty($todayBookings)): ?>
            <div class="empty-state" style="padding:2rem;">No bookings for today.</div>
        <?php else: ?>
            <div class="booking-list">
                <?php foreach ($todayBookings as $b): ?>
                <div class="booking-item <?= $b['color_class'] ?>">
                    <div class="booking-time"><?= date('H:i', strtotime($b['start_time'])) ?> – <?= date('H:i', strtotime($b['end_time'])) ?></div>
                    <div class="booking-details">
                        <strong><?= htmlspecialchars($b['room_name']) ?></strong>
                        <span><?= htmlspecialchars($b['purpose']) ?></span>
                        <small>by <?= htmlspecialchars($b['booked_by_name']) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<style>
.stat-card.stat-purple { border-left: 4px solid #8b5cf6; }
.stat-card.stat-purple .stat-icon { background: #ede9fe; color: #7c3aed; }
</style>