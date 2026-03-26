<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireLogin();
if (!isAdmin()) { header('Location: ../dashboard.php'); exit; }
$pdo = getDB();
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $stmt = $pdo->prepare("INSERT INTO staff (staff_no,name,position,department_id,company,email) VALUES (?,?,?,?,?,?)");
    $stmt->execute([trim($_POST['staff_no']),trim($_POST['name']),trim($_POST['position']),($_POST['department_id']?:(null)),trim($_POST['company']),trim($_POST['email']??'')]);
    redirect('saved');
}
if ($action === 'edit') {
    $stmt = $pdo->prepare("UPDATE staff SET staff_no=?,name=?,position=?,department_id=?,company=?,email=? WHERE id=?");
    $stmt->execute([trim($_POST['staff_no']),trim($_POST['name']),trim($_POST['position']),($_POST['department_id']?:null),trim($_POST['company']),trim($_POST['email']??''),(int)$_POST['id']]);
    redirect('saved');
}
if ($action === 'delete' && isAdminIT()) {
    $pdo->prepare("DELETE FROM staff WHERE id=?")->execute([(int)$_POST['id']]);
    redirect('deleted');
}
function redirect($t) { header("Location: ../dashboard.php?page=staff&toast=$t&toast_type=success"); exit; }
