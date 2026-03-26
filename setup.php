<?php
// setup.php - Run this ONCE to create/fix password hashes
// Access: http://yourserver/hr_system/setup.php?key=setup2024

if (($_GET['key'] ?? '') !== 'setup2024') {
    die('Access denied. Add ?key=setup2024 to URL');
}

require_once 'includes/config.php';
$pdo = getDB();

$users = [
    ['admin@company.com', 'password', 'admin_it'],
    ['hr@company.com', 'hr123', 'admin_hr'],
    ['staff@company.com', 'staff123', 'staff'],
];

foreach ($users as [$email, $pw, $role]) {
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password=? WHERE email=?");
    $stmt->execute([$hash, $email]);
    echo "Updated $email ($role) with hash for '$pw'<br>";
}

echo "<br><strong style='color:green'>✅ All passwords updated! Delete or rename setup.php now.</strong>";
echo "<br><a href='index.php'>Go to Login</a>";
