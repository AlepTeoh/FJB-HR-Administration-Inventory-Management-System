<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireAdminIT();
$pdo = getDB();
$modules = ['training','family','rooms','requests','staff'];
foreach ($modules as $m) {
    $val = isset($_POST['module_'.$m]) ? '1' : '0';
    $pdo->prepare("INSERT INTO system_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")->execute(['module_'.$m,$val,$val]);
}
header("Location: ../dashboard.php?page=settings&toast=settings&toast_type=success");
exit;
