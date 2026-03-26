<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireAdminIT();
$pdo = getDB();
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $pw = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role,staff_no,department_id,position,company) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([trim($_POST['name']),trim($_POST['email']),$pw,$_POST['role'],trim($_POST['staff_no']??''),($_POST['department_id']?:null),trim($_POST['position']??''),'FJB']);
    redirect('saved');
}
if ($action === 'edit') {
    if (!empty($_POST['password'])) {
        $pw = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET name=?,email=?,password=?,role=?,staff_no=?,department_id=?,position=? WHERE id=?");
        $stmt->execute([trim($_POST['name']),trim($_POST['email']),$pw,$_POST['role'],trim($_POST['staff_no']??''),($_POST['department_id']?:null),trim($_POST['position']??''),(int)$_POST['id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name=?,email=?,role=?,staff_no=?,department_id=?,position=? WHERE id=?");
        $stmt->execute([trim($_POST['name']),trim($_POST['email']),$_POST['role'],trim($_POST['staff_no']??''),($_POST['department_id']?:null),trim($_POST['position']??''),(int)$_POST['id']]);
    }
    redirect('saved');
}
if ($action === 'delete') {
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([(int)$_POST['id']]);
    redirect('deleted');
}
function redirect($t) { header("Location: ../dashboard.php?page=users&toast=$t&toast_type=success"); exit; }
