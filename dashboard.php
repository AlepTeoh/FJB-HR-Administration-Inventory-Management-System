<?php
// dashboard.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$user = currentUser();
$page = $_GET['page'] ?? 'home';
$pendingCount = getPendingRequestCount();

// --- ALLOWED PAGES LOGIC ---
$validPages = ['home', 'training', 'family', 'rooms']; // Default pages for all users (Staff)

// Admins get extra pages
if (isAdminIT()) {
    $validPages = ['home','staff','training','training_report','family','report','rooms','requests','travel','settings','users','staffid'];
} elseif (isAdminHR()) {
    $validPages = ['home','staff','training','training_report','family','report','rooms','requests','travel','staffid'];
}

// FORCE ALLOW IMPORT PAGE FOR ALL ADMINS
if (isAdmin() && !in_array('import_training', $validPages)) {
    $validPages[] = 'import_training';
}

// Module toggles check
if (in_array($page, ['training','training_report','family','rooms','requests','staff','travel']) && !isAdminIT() && !isModuleEnabled($page)) {
    $page = 'home';
}

// Final security check
if (!in_array($page, $validPages)) {
    $page = 'home';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        </div>
        <span>HR Admin</span>
    </div>
    <nav class="sidebar-nav">
        <a href="?page=home" class="nav-item <?= $page==='home'?'active':'' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Dashboard
        </a>

        <?php if (isAdmin() && isModuleEnabled('staff')): ?>
        <div class="nav-group" id="navGroupRegistry">
            <div class="nav-group-toggle <?= in_array($page,['staff','family','report'])?'open has-active':'' ?>" 
                 onclick="toggleNavGroup('navGroupRegistry')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Staff Registry</span>
                <svg class="toggle-arrow" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </div>
            <div class="nav-group-children <?= in_array($page,['staff','family','report'])?'open':'' ?>">
                <div class="nav-group-children-inner">
                    <a href="?page=staff" class="nav-child <?= $page==='staff'?'active':'' ?>">Staff List</a>
                    <?php if (isModuleEnabled('family')): ?>
                    <a href="?page=family" class="nav-child <?= $page==='family'?'active':'' ?>">Family Info</a>
                    <?php endif; ?>
                    <a href="?page=report" class="nav-child <?= $page==='report'?'active':'' ?>">Registry Report</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isModuleEnabled('training')): ?>
        <div class="nav-group" id="navGroupTraining">
            <div class="nav-group-toggle <?= in_array($page,['training','training_report','import_training'])?'open has-active':'' ?>" 
                 onclick="toggleNavGroup('navGroupTraining')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>
                <span>Training</span>
                <svg class="toggle-arrow" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </div>
            <div class="nav-group-children <?= in_array($page,['training','training_report','import_training'])?'open':'' ?>">
                <div class="nav-group-children-inner">
                    <a href="?page=training" class="nav-child <?= $page==='training'?'active':'' ?>">Training Records</a>
                    <?php if (isAdmin()): ?>
                    <a href="?page=training_report" class="nav-child <?= $page==='training_report'?'active':'' ?>">Training Report</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isModuleEnabled('rooms')): ?>
        <a href="?page=rooms" class="nav-item <?= $page==='rooms'?'active':'' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            Meeting Rooms
        </a>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
        <a href="?page=travel" class="nav-item <?= $page==='travel'?'active':'' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.6 12 19.79 19.79 0 0 1 1.58 3.47 2 2 0 0 1 3.55 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.27a16 16 0 0 0 6.29 6.29l1.63-1.83a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 14.92z"/></svg>
            Business Travel
        </a>
        <?php endif; ?>

        <?php if (isAdmin() && isModuleEnabled('requests')): ?>
        <a href="?page=requests" class="nav-item <?= $page==='requests'?'active':'' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>
            Update Requests
            <?php if ($pendingCount > 0): ?><span class="badge-count"><?= $pendingCount ?></span><?php endif; ?>
        </a>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
        <div class="nav-divider"></div>
        <?php if (isAdminIT()): ?>
        <a href="?page=users" class="nav-item <?= $page==='users'?'active':'' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 10-16 0"/></svg>
            User Accounts
        </a>
        <?php endif; ?>
        <a href="?page=staffid" class="nav-item <?= $page==='staffid'?'active':'' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="12" y2="13"/><circle cx="15" cy="15" r="2"/><line x1="16.4" y1="16.4" x2="18" y2="18"/></svg>
            Staff ID
        </a>
        <?php if (isAdminIT()): ?>
        <a href="?page=settings" class="nav-item <?= $page==='settings'?'active':'' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            System Settings
        </a>
        <?php endif; ?>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars(explode(' ',$user['name'])[0]) ?></span>
                <span class="user-role <?= str_replace('_','-',$user['role']) ?>"><?= getRoleLabel() ?></span>
            </div>
        </div>
    </div>
</aside>
<div class="main-wrapper">
    <header class="topbar">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
        <div class="topbar-title">
            <?php 
            $titles=[
                'home'=>'Dashboard',
                'staff'=>'Staff Registry',
                'training'=>'Training Records',
                'training_report'=>'Training Report',
                'family'=>'Family Information',
                'report'=>'Staff Registry Report',
                'rooms'=>'Meeting Rooms',
                'travel'=>'Business Travel',
                'requests'=>'Update Requests',
                'settings'=>'System Settings',
                'users'=>'User Accounts',
                'staffid'=>'Staff ID Management',
                'import_training'=>'Import CSV Data'
            ]; 
            echo $titles[$page]??'Dashboard'; 
            ?>
        </div>
        <div class="topbar-right">
            <span class="topbar-user">
                <strong><?= htmlspecialchars($user['name']) ?></strong>
                <span class="role-badge <?= str_replace('_','-',$user['role']) ?>"><?= getRoleLabel() ?></span>
            </span>
            <a href="logout.php" class="btn btn-outline btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Logout
            </a>
        </div>
    </header>
    <main class="content-area">
        <?php
        switch($page) {
            case 'staff':           if(isAdmin()) include 'pages/staff.php'; break;
            case 'training':        include 'pages/training.php'; break;
            case 'training_report': if(isAdmin()) include 'pages/training_report.php'; break; 
            case 'family':          include 'pages/family.php'; break;
            case 'report':          if(isAdmin()) include 'pages/report.php'; break;
            case 'rooms':           include 'pages/rooms.php'; break;
            case 'travel':          if(isAdmin()) include 'pages/travel.php'; break;
            case 'requests':        if(isAdmin()) include 'pages/requests.php'; break;
            case 'settings':        if(isAdminIT()) include 'pages/settings.php'; break;
            case 'users':           if(isAdminIT()) include 'pages/users.php'; break;
            case 'staffid':         if(isAdmin()) include 'pages/staffid.php'; break;
            case 'import_training': if(isAdmin()) include 'pages/import_training.php'; break;
            default:                include 'pages/home.php'; break;
        }
        ?>
    </main>
</div>
<div class="modal-overlay" id="modalOverlay" onclick="closeModal()"></div>
<script src="assets/js/app.js"></script>
</body>
</html>