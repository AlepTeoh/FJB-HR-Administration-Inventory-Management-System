<?php
// includes/auth.php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

// Role checks
function getRole() {
    return $_SESSION['role'] ?? '';
}

function isAdminIT() {
    return getRole() === 'admin_it';
}

function isAdminHR() {
    return getRole() === 'admin_hr';
}

function isAdmin() {
    // Both admin_it and admin_hr have admin-level access
    return in_array(getRole(), ['admin_it', 'admin_hr']);
}

function isStaff() {
    return getRole() === 'staff';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php?error=access_denied');
        exit;
    }
}

function requireAdminIT() {
    requireLogin();
    if (!isAdminIT()) {
        header('Location: dashboard.php?error=access_denied');
        exit;
    }
}

function currentUser() {
    return [
        'id'            => $_SESSION['user_id'] ?? null,
        'name'          => $_SESSION['name'] ?? '',
        'email'         => $_SESSION['email'] ?? '',
        'role'          => $_SESSION['role'] ?? '',
        'department_id' => $_SESSION['department_id'] ?? null,
        'department'    => $_SESSION['department'] ?? '',
        'staff_no'      => $_SESSION['staff_no'] ?? '',
        'position'      => $_SESSION['position'] ?? '',
        'company'       => $_SESSION['company'] ?? 'FJB',
    ];
}

function getRoleLabel($role = null) {
    $r = $role ?? getRole();
    return match($r) {
        'admin_it' => 'Admin (IT)',
        'admin_hr' => 'Admin (HR)',
        'staff'    => 'Staff',
        default    => ucfirst($r),
    };
}

function login($staff_id, $password) {
    require_once __DIR__ . '/config.php';
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT u.*, d.name as dept_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.staff_no = ? AND u.is_active = 1 LIMIT 1");
    $stmt->execute([$staff_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['name']         = $user['name'];
        $_SESSION['email']        = $user['email'];
        $_SESSION['role']         = $user['role'];
        $_SESSION['department_id']= $user['department_id'];
        $_SESSION['department']   = $user['dept_name'] ?? '';
        $_SESSION['staff_no']     = $user['staff_no'] ?? '';
        $_SESSION['position']     = $user['position'] ?? '';
        $_SESSION['company']      = $user['company'] ?? 'FJB';
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: index.php');
    exit;
}

function isModuleEnabled($module) {
    require_once __DIR__ . '/config.php';
    $pdo = getDB();
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute(['module_' . $module]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (bool)$val : true;
    } catch (Exception $e) {
        return true;
    }
}

function getPendingRequestCount() {
    if (!isAdmin()) return 0;
    require_once __DIR__ . '/config.php';
    $pdo = getDB();
    $stmt = $pdo->query("SELECT COUNT(*) FROM update_requests WHERE status = 'Pending'");
    return (int)$stmt->fetchColumn();
}
