<?php
// actions/request.php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireLogin();

$pdo    = getDB();
$action = $_POST['action'] ?? 'submit';
$user   = currentUser();

if ($action === 'submit') {
    // Staff submitting a request
    $stmt = $pdo->prepare("
        INSERT INTO update_requests (requester_id, requester_name, record_type, record_id, record_reference, message)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user['id'],
        $user['name'],
        $_POST['record_type'],
        (int)$_POST['record_id'],
        trim($_POST['record_reference']),
        trim($_POST['message']),
    ]);
    $page = $_POST['record_type'] === 'Training Record' ? 'training' : 'family';
    header("Location: ../dashboard.php?page=$page&toast=request&toast_type=success");
    exit;
}

if ($action === 'resolve' && isAdmin()) {
    $stmt = $pdo->prepare("UPDATE update_requests SET status = 'Resolved' WHERE id = ?");
    $stmt->execute([(int)$_POST['id']]);
    header("Location: ../dashboard.php?page=requests&filter=Pending&toast=resolved&toast_type=success");
    exit;
}

if ($action === 'dismiss' && isAdmin()) {
    $stmt = $pdo->prepare("UPDATE update_requests SET status = 'Dismissed' WHERE id = ?");
    $stmt->execute([(int)$_POST['id']]);
    header("Location: ../dashboard.php?page=requests&filter=Pending&toast=dismissed&toast_type=success");
    exit;
}

header("Location: ../dashboard.php");
exit;
