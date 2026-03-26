<?php
require_once 'includes/auth.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }
$error = '';
$success = '';
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = 'Password reset successfully. Please sign in with your new password.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = trim($_POST['staff_id']??'');
    $password = trim($_POST['password']??'');
    if (login($staff_id, $password)) { header('Location: dashboard.php'); exit; }
    else $error = 'Invalid Staff ID or password. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — HR Admin System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-brand">
            <div class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            </div>
            <h1 class="brand-name">HR Admin System</h1>
            <p class="brand-sub">FGV Group — Human Resources &amp; Administration Portal</p>
        </div>
        <?php if ($success): ?>
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="staff_id">Staff ID</label>
                <input type="text" id="staff_id" name="staff_id" placeholder="e.g. 0000001" required value="<?= htmlspecialchars($_POST['staff_id']??'') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Sign In</button>
            <a href="forgot.php" style="display:block;text-align:center;margin-top:.85rem;font-size:.85rem;color:#6366f1;text-decoration:none;">Forgot password?</a>
        </form>
        <div class="login-hint">
            <p><strong>🔧 Admin (IT):</strong> 0000001 / password</p>
            <p><strong>👤 Admin (HR):</strong> 0000002 / hr123</p>
            <p><strong>👷 Staff:</strong> 3600134 / staff123</p>
        </div>
    </div>
</div>
</body>
</html>
