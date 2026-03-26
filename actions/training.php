<?php
// actions/training.php
require_once '../includes/auth.php';
require_once '../includes/config.php';

// AJAX staff search
if (isset($_GET['search_staff'])) {
    requireLogin();
    $pdo = getDB();
    $q = '%' . trim($_GET['search_staff']) . '%';
    $stmt = $pdo->prepare("SELECT s.id, s.staff_no, s.name, d.name as dept_name FROM staff s LEFT JOIN departments d ON s.department_id = d.id WHERE s.name LIKE ? OR s.staff_no LIKE ? ORDER BY s.name LIMIT 20");
    $stmt->execute([$q, $q]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll());
    exit;
}

requireLogin();
$pdo = getDB();
$action = $_POST['action'] ?? '';

if ($action === 'add_course' && isAdmin()) {
    $stmt = $pdo->prepare("INSERT INTO training_courses (code, title, company, training_date) VALUES (?,?,?,?)");
    $stmt->execute([strtoupper(trim($_POST['code'])), trim($_POST['title']), $_POST['company'], $_POST['training_date']?:null]);
    redirect('saved');
}

if ($action === 'add_attendance' && isAdmin()) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO training_attendances (staff_id, course_id, status, remarks, created_by) VALUES (?,?,?,?,?)");
    $stmt->execute([(int)$_POST['staff_id'], (int)$_POST['course_id'], $_POST['status'], trim($_POST['remarks']??''), currentUser()['id']]);
    redirect('saved');
}

if ($action === 'edit_attendance' && isAdmin()) {
    $stmt = $pdo->prepare("UPDATE training_attendances SET status=?, remarks=? WHERE id=?");
    $stmt->execute([$_POST['status'], trim($_POST['remarks']??''), (int)$_POST['id']]);
    redirect('saved');
}

if ($action === 'delete_attendance' && isAdmin()) {
    $stmt = $pdo->prepare("DELETE FROM training_attendances WHERE id=?");
    $stmt->execute([(int)$_POST['id']]);
    redirect('deleted');
}

function redirect($toast, $type='success') {
    header("Location: ../dashboard.php?page=training&toast=$toast&toast_type=$type");
    exit;
}
