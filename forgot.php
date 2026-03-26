<?php
// forgot.php — 3-step password reset flow
require_once 'includes/auth.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

// Handle restart before any output
if (isset($_GET['restart'])) { session_destroy(); header('Location: forgot.php'); exit; }

$step  = $_SESSION['fp_step']      ?? 'request';   // request | verify | reset
$name  = $_SESSION['fp_user_name'] ?? '';
$otp   = $_SESSION['fp_otp']       ?? null;         // shown once after generation
$error = $_SESSION['fp_error']     ?? '';
unset($_SESSION['fp_error']);

// Step labels for progress indicator
$steps = ['request' => 1, 'verify' => 2, 'reset' => 3];
$current_step = $steps[$step] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — HR Admin System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  .progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin-bottom: 1.75rem;
  }
  .progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .35rem;
  }
  .progress-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    font-weight: 600;
    color: #9ca3af;
    transition: all .3s;
  }
  .progress-circle.done  { background:#6366f1; border-color:#6366f1; color:#fff; }
  .progress-circle.active{ background:#6366f1; border-color:#6366f1; color:#fff; box-shadow:0 0 0 4px rgba(99,102,241,.15); }
  .progress-label { font-size: .72rem; color: #9ca3af; white-space:nowrap; }
  .progress-label.active { color:#6366f1; font-weight:600; }
  .progress-line {
    flex: 1;
    height: 2px;
    background: #e5e7eb;
    margin: 0 6px;
    margin-bottom: 18px;
    min-width: 32px;
    max-width: 56px;
    transition: background .3s;
  }
  .progress-line.done { background: #6366f1; }

  .otp-display {
    background: linear-gradient(135deg, #f0f0ff 0%, #e8e8ff 100%);
    border: 2px dashed #6366f1;
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    margin: 1rem 0 1.25rem;
  }
  .otp-code {
    font-size: 2.4rem;
    font-weight: 700;
    letter-spacing: .35em;
    color: #4f46e5;
    font-family: monospace;
    line-height: 1;
  }
  .otp-note {
    font-size: .78rem;
    color: #6b7280;
    margin-top: .5rem;
  }
  .otp-inputs {
    display: flex;
    gap: .5rem;
    justify-content: center;
    margin: 1rem 0;
  }
  .otp-inputs input {
    width: 44px !important;
    height: 52px;
    text-align: center;
    font-size: 1.4rem;
    font-weight: 700;
    border: 2px solid #d1d5db;
    border-radius: 8px;
    font-family: monospace;
    padding: 0;
    transition: border-color .2s;
  }
  .otp-inputs input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.15);
  }
  .back-link {
    display: block;
    text-align: center;
    margin-top: 1rem;
    font-size: .85rem;
    color: #6b7280;
    text-decoration: none;
  }
  .back-link:hover { color: #6366f1; }
  .pw-strength {
    height: 4px;
    border-radius: 2px;
    background: #e5e7eb;
    margin-top: .4rem;
    overflow: hidden;
  }
  .pw-strength-bar {
    height: 100%;
    border-radius: 2px;
    transition: width .3s, background .3s;
    width: 0;
  }
  .success-icon {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: #d1fae5;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem;
  }
</style>
</head>
<body class="login-body">
<div class="login-wrapper">
  <div class="login-card">

    <!-- Brand -->
    <div class="login-brand" style="margin-bottom:1.25rem;">
      <div class="brand-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </div>
      <h1 class="brand-name" style="font-size:1.3rem;">Reset Password</h1>
      <p class="brand-sub">HR Admin System</p>
    </div>

    <!-- Progress Bar -->
    <div class="progress-bar">
      <div class="progress-step">
        <div class="progress-circle <?= $current_step >= 1 ? ($current_step > 1 ? 'done' : 'active') : '' ?>">
          <?php if ($current_step > 1): ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          <?php else: ?>1<?php endif; ?>
        </div>
        <span class="progress-label <?= $current_step === 1 ? 'active' : '' ?>">Staff ID</span>
      </div>
      <div class="progress-line <?= $current_step > 1 ? 'done' : '' ?>"></div>
      <div class="progress-step">
        <div class="progress-circle <?= $current_step >= 2 ? ($current_step > 2 ? 'done' : 'active') : '' ?>">
          <?php if ($current_step > 2): ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          <?php else: ?>2<?php endif; ?>
        </div>
        <span class="progress-label <?= $current_step === 2 ? 'active' : '' ?>">Enter OTP</span>
      </div>
      <div class="progress-line <?= $current_step > 2 ? 'done' : '' ?>"></div>
      <div class="progress-step">
        <div class="progress-circle <?= $current_step === 3 ? 'active' : '' ?>">3</div>
        <span class="progress-label <?= $current_step === 3 ? 'active' : '' ?>">New Password</span>
      </div>
    </div>

    <!-- Error -->
    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:1rem;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php /* ═══════════════ STEP 1: Enter Staff ID ═══════════════ */ ?>
    <?php if ($step === 'request'): ?>
    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1.25rem;text-align:center;">
      Enter your Staff ID to receive a one-time password.
    </p>
    <form method="POST" action="actions/forgot.php">
      <input type="hidden" name="action" value="request_otp">
      <div class="form-group">
        <label for="staff_no">Staff ID</label>
        <input type="text" id="staff_no" name="staff_no" placeholder="e.g. 0000001"
               required autofocus style="font-family:monospace;letter-spacing:.05em;">
      </div>
      <button type="submit" class="btn btn-primary btn-full">Generate OTP</button>
    </form>
    <a href="index.php" class="back-link">← Back to Login</a>

    <?php /* ═══════════════ STEP 2: Show OTP + Verify ═══════════════ */ ?>
    <?php elseif ($step === 'verify'): ?>
    <p style="font-size:.88rem;color:#6b7280;margin-bottom:.75rem;text-align:center;">
      Hello, <strong><?= htmlspecialchars($name) ?></strong>. Your one-time password is:
    </p>

    <?php if ($otp): // Show OTP only immediately after generation ?>
    <div class="otp-display">
      <div class="otp-code"><?= htmlspecialchars($otp) ?></div>
      <div class="otp-note">⏱ Valid for 15 minutes &nbsp;·&nbsp; Do not share this code</div>
    </div>
    <?php endif; ?>

    <p style="font-size:.84rem;color:#6b7280;text-align:center;margin-bottom:.75rem;">
      Enter the 6-digit code above to continue.
    </p>
    <form method="POST" action="actions/forgot.php" id="otpForm">
      <input type="hidden" name="action" value="verify_otp">
      <input type="hidden" name="otp" id="otp_hidden" value="">
      <div class="otp-inputs">
        <?php for ($i = 0; $i < 6; $i++): ?>
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
               class="otp-digit" data-index="<?= $i ?>" autocomplete="off">
        <?php endfor; ?>
      </div>
      <button type="submit" class="btn btn-primary btn-full" id="verifyBtn" disabled>Verify OTP</button>
    </form>
    <a href="forgot.php?restart=1" class="back-link" onclick="return confirmRestart()">← Start over</a>

    <?php /* ═══════════════ STEP 3: New Password ═══════════════ */ ?>
    <?php elseif ($step === 'reset'): ?>
    <div class="success-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1.25rem;text-align:center;">
      OTP verified! Set a new password for <strong><?= htmlspecialchars($name) ?></strong>.
    </p>
    <form method="POST" action="actions/forgot.php" id="resetForm">
      <input type="hidden" name="action" value="reset_password">
      <div class="form-group">
        <label for="password">New Password</label>
        <input type="password" id="password" name="password" required minlength="6"
               placeholder="Minimum 6 characters" oninput="checkStrength(this.value)">
        <div class="pw-strength"><div class="pw-strength-bar" id="strengthBar"></div></div>
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required
               placeholder="Re-enter new password" oninput="checkMatch()">
        <small id="matchMsg" style="font-size:.78rem;"></small>
      </div>
      <button type="submit" class="btn btn-primary btn-full" id="resetBtn">Set New Password</button>
    </form>
    <?php endif; ?>

  </div>
</div>

<script>
// ── OTP digit inputs ────────────────────────────────────────────────────────
const digits  = document.querySelectorAll('.otp-digit');
const hidden  = document.getElementById('otp_hidden');
const verifyBtn = document.getElementById('verifyBtn');

if (digits.length) {
  digits.forEach((inp, i) => {
    inp.addEventListener('input', e => {
      // allow only digits
      inp.value = inp.value.replace(/\D/g, '').slice(-1);
      if (inp.value && i < digits.length - 1) digits[i + 1].focus();
      syncHidden();
    });
    inp.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !inp.value && i > 0) {
        digits[i - 1].focus();
        digits[i - 1].value = '';
        syncHidden();
      }
    });
    inp.addEventListener('paste', e => {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'');
      [...text.slice(0,6)].forEach((ch, j) => { if (digits[j]) digits[j].value = ch; });
      syncHidden();
      if (digits[Math.min(text.length, 5)]) digits[Math.min(text.length, 5)].focus();
    });
  });
}

function syncHidden() {
  const val = [...digits].map(d => d.value).join('');
  if (hidden) hidden.value = val;
  if (verifyBtn) verifyBtn.disabled = val.length < 6;
}

// ── Password strength ───────────────────────────────────────────────────────
function checkStrength(pw) {
  const bar = document.getElementById('strengthBar');
  if (!bar) return;
  let score = 0;
  if (pw.length >= 6)  score++;
  if (pw.length >= 10) score++;
  if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
  if (/\d/.test(pw))   score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const pct   = ['0%','25%','50%','75%','100%'][score] || '0%';
  const color = ['#ef4444','#f97316','#eab308','#22c55e','#16a34a'][score-1] || '#e5e7eb';
  bar.style.width = pct;
  bar.style.background = color;
  checkMatch();
}

function checkMatch() {
  const pw  = document.getElementById('password')?.value || '';
  const cfm = document.getElementById('confirm_password')?.value || '';
  const msg = document.getElementById('matchMsg');
  const btn = document.getElementById('resetBtn');
  if (!msg) return;
  if (!cfm) { msg.textContent = ''; return; }
  if (pw === cfm) {
    msg.style.color = '#16a34a';
    msg.textContent = '✓ Passwords match';
    if (btn) btn.disabled = false;
  } else {
    msg.style.color = '#ef4444';
    msg.textContent = '✗ Passwords do not match';
    if (btn) btn.disabled = true;
  }
}

// ── Restart confirmation ────────────────────────────────────────────────────
function confirmRestart() {
  return confirm('Start over? Your current OTP will be cancelled.');
}
</script>
</body>
</html>
