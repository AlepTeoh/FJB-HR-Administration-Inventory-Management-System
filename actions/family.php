<?php
// actions/family.php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireLogin();
requireAdmin();

$pdo    = getDB();
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $stmt = $pdo->prepare("
        INSERT INTO family_members (employee_name, department, family_member_name, relationship, date_of_birth, emergency_contact, phone_number, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        trim($_POST['employee_name']),
        $_POST['department'],
        trim($_POST['family_member_name']),
        $_POST['relationship'],
        $_POST['date_of_birth'] ?: null,
        $_POST['emergency_contact'],
        trim($_POST['phone_number'] ?? '') ?: null,
        currentUser()['id'],
    ]);
    redirect('saved');
}

if ($action === 'edit') {
    $stmt = $pdo->prepare("
        UPDATE family_members SET
            employee_name = ?, department = ?, family_member_name = ?,
            relationship = ?, date_of_birth = ?, emergency_contact = ?, phone_number = ?
        WHERE id = ?
    ");
    $stmt->execute([
        trim($_POST['employee_name']),
        $_POST['department'],
        trim($_POST['family_member_name']),
        $_POST['relationship'],
        $_POST['date_of_birth'] ?: null,
        $_POST['emergency_contact'],
        trim($_POST['phone_number'] ?? '') ?: null,
        (int)$_POST['id'],
    ]);
    redirect('saved');
}

if ($action === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM family_members WHERE id = ?");
    $stmt->execute([(int)$_POST['id']]);
    redirect('deleted');
}

function redirect($toast) {
    header("Location: ../dashboard.php?page=family&toast=$toast&toast_type=success");
    exit;
}
