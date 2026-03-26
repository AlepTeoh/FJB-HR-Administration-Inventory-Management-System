<?php
// actions/staffid.php - Both admins can update staff_no for staff users
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireAdmin(); // allows admin_it and admin_hr

$pdo    = getDB();
$id     = (int)($_POST['id'] ?? 0);
$staffno = trim($_POST['staff_no'] ?? '');

if (!$id || $staffno === '') {
    header('Location: ../dashboard.php?page=staffid&toast=missing_fields&toast_type=error');
    exit;
}

// Ensure staff_no is unique (excluding this user)
$check = $pdo->prepare("SELECT id FROM users WHERE staff_no = ? AND id != ?");
$check->execute([$staffno, $id]);
if ($check->fetch()) {
    header('Location: ../dashboard.php?page=staffid&toast=duplicate_id&toast_type=error');
    exit;
}

// Only allow updating staff role users
$stmt = $pdo->prepare("UPDATE users SET staff_no = ? WHERE id = ? AND role = 'staff'");
$stmt->execute([$staffno, $id]);

header('Location: ../dashboard.php?page=staffid&toast=saved&toast_type=success');
exit;
