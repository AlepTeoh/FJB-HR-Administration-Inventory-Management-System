<?php
// actions/travel.php
require_once '../includes/auth.php';
require_once '../includes/config.php';

requireAdmin(); // Only HR & IT admins allowed
$pdo = getDB();
$action = $_POST['action'] ?? '';

// ── Add / Edit travel record ──────────────────────────────────────────────
if ($action === 'save_travel') {
    $id            = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $staff_id      = (int)($_POST['staff_id'] ?? 0);
    $destination   = trim($_POST['destination'] ?? '');
    $purpose       = trim($_POST['purpose'] ?? '');
    $departure     = $_POST['departure_date'] ?? '';
    $return_date   = $_POST['return_date'] ?? '';
    $transport     = trim($_POST['transport'] ?? '');
    $notes         = trim($_POST['notes'] ?? '');

    // Basic validation
    if (!$staff_id || !$destination || !$departure || !$return_date) {
        redirect('error', 'error');
    }
    if ($return_date < $departure) {
        redirect('error', 'error');
    }

    if ($id > 0) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE business_travel
               SET staff_id=?, destination=?, purpose=?, departure_date=?,
                   return_date=?, transport=?, notes=?
             WHERE id=?
        ");
        $stmt->execute([$staff_id, $destination, $purpose, $departure,
                        $return_date, $transport, $notes, $id]);
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO business_travel
                (staff_id, destination, purpose, departure_date, return_date, transport, notes, created_by)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([$staff_id, $destination, $purpose, $departure,
                        $return_date, $transport, $notes, currentUser()['id']]);
    }

    redirect('saved');
}

// ── Delete travel record ──────────────────────────────────────────────────
if ($action === 'delete_travel') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM business_travel WHERE id=?");
        $stmt->execute([$id]);
    }
    redirect('deleted');
}

// ── AJAX: staff search ────────────────────────────────────────────────────
if (isset($_GET['search_staff'])) {
    requireLogin();
    $q = '%' . trim($_GET['search_staff']) . '%';
    $stmt = $pdo->prepare("
        SELECT s.id, s.staff_no, s.name, d.name AS dept_name
          FROM staff s
          LEFT JOIN departments d ON s.department_id = d.id
         WHERE (s.name LIKE ? OR s.staff_no LIKE ?) AND s.is_active = 1
         ORDER BY s.name LIMIT 20
    ");
    $stmt->execute([$q, $q]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll());
    exit;
}

// ── AJAX: fetch single record for edit modal ──────────────────────────────
if (isset($_GET['get_record'])) {
    requireAdmin();
    $id = (int)$_GET['get_record'];
    $stmt = $pdo->prepare("
        SELECT bt.*, s.name AS staff_name, s.staff_no
          FROM business_travel bt
          JOIN staff s ON bt.staff_id = s.id
         WHERE bt.id = ?
    ");
    $stmt->execute([$id]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetch() ?: []);
    exit;
}

function redirect($toast, $type = 'success') {
    header("Location: ../dashboard.php?page=travel&toast=$toast&toast_type=$type");
    exit;
}
