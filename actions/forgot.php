<?php
// actions/forgot.php
require_once '../includes/auth.php';
require_once '../includes/config.php';

$pdo    = getDB();
$action = $_POST['action'] ?? '';

// ── Step 1: Verify staff ID, generate OTP ──────────────────────────────────
if ($action === 'request_otp') {
    $staff_no = trim($_POST['staff_no'] ?? '');

    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE staff_no = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$staff_no]);
    $user = $stmt->fetch();

    if (!$user) {
        // Redirect back with error but don't reveal if ID exists
        $_SESSION['fp_error'] = 'Staff ID not found. Please check and try again.';
        header('Location: ../forgot.php');
        exit;
    }

    // Invalidate any existing unused OTPs for this user
    $pdo->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0")->execute([$user['id']]);

    // Generate 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $pdo->prepare("INSERT INTO password_resets (user_id, otp_code, expires_at) VALUES (?, ?, NOW() + INTERVAL 15 MINUTE)")
        ->execute([$user['id'], $otp]);

    // Store in session to carry to step 2 (we show OTP on screen)
    $_SESSION['fp_user_id']   = $user['id'];
    $_SESSION['fp_user_name'] = $user['name'];
    $_SESSION['fp_otp']       = $otp;          // shown on screen once
    $_SESSION['fp_step']      = 'verify';

    header('Location: ../forgot.php');
    exit;
}

// ── Step 2: Verify OTP ─────────────────────────────────────────────────────
if ($action === 'verify_otp') {
    $entered = trim($_POST['otp'] ?? '');
    $user_id = $_SESSION['fp_user_id'] ?? null;

    if (!$user_id) {
        header('Location: ../forgot.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM password_resets
                            WHERE user_id = ? AND otp_code = ? AND used = 0 AND expires_at > NOW()
                            LIMIT 1");
    $stmt->execute([$user_id, $entered]);
    $record = $stmt->fetch();

    if (!$record) {
        $_SESSION['fp_error'] = 'Invalid or expired OTP. Please try again.';
        header('Location: ../forgot.php');
        exit;
    }

    // Mark OTP as used
    $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")->execute([$record['id']]);

    $_SESSION['fp_step']      = 'reset';
    $_SESSION['fp_otp']       = null; // clear OTP from session
    header('Location: ../forgot.php');
    exit;
}

// ── Step 3: Set new password ───────────────────────────────────────────────
if ($action === 'reset_password') {
    $user_id  = $_SESSION['fp_user_id'] ?? null;
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$user_id || $_SESSION['fp_step'] !== 'reset') {
        header('Location: ../forgot.php');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['fp_error'] = 'Password must be at least 6 characters.';
        header('Location: ../forgot.php');
        exit;
    }

    if ($password !== $confirm) {
        $_SESSION['fp_error'] = 'Passwords do not match.';
        header('Location: ../forgot.php');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user_id]);

    // Clear all session reset data
    unset($_SESSION['fp_user_id'], $_SESSION['fp_user_name'], $_SESSION['fp_otp'], $_SESSION['fp_step'], $_SESSION['fp_error']);

    header('Location: ../index.php?reset=success');
    exit;
}

// Fallback
header('Location: ../forgot.php');
exit;
