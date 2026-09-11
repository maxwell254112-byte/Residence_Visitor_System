<?php
declare(strict_types=1);

$user = current_user();
$pageTitle = $pageTitle ?? APP_NAME;
$currentNav = $currentNav ?? '';
$unread = $user ? unread_notification_count((int) $user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body class="app-body">
<div class="app-shell">
    <aside class="app-sidebar">
        <div class="brand">
            <span class="brand-mark"><i class="fa-solid fa-shield-halved"></i></span>
            <div>
                <strong>Residence</strong>
                <small>Visitor Control</small>
            </div>
        </div>
        <nav class="side-nav">
            <?php if ($user && $user['role_slug'] === 'admin'): ?>
                <a class="<?= $currentNav === 'dashboard' ? 'active' : '' ?>" href="<?= e(app_url('admin/dashboard.php')) ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <a class="<?= $currentNav === 'residents' ? 'active' : '' ?>" href="<?= e(app_url('admin/residents/index.php')) ?>"><i class="fa-solid fa-house-user"></i> Residents</a>
                <a class="<?= $currentNav === 'visitors' ? 'active' : '' ?>" href="<?= e(app_url('admin/visitors/index.php')) ?>"><i class="fa-solid fa-id-card-clip"></i> Visitors</a>
                <a class="<?= $currentNav === 'blacklist' ? 'active' : '' ?>" href="<?= e(app_url('admin/blacklist/index.php')) ?>"><i class="fa-solid fa-ban"></i> Blacklist</a>
                <a class="<?= $currentNav === 'staff' ? 'active' : '' ?>" href="<?= e(app_url('admin/staff/index.php')) ?>"><i class="fa-solid fa-user-tie"></i> Staff</a>
                <a class="<?= $currentNav === 'announcements' ? 'active' : '' ?>" href="<?= e(app_url('admin/announcements/index.php')) ?>"><i class="fa-solid fa-bullhorn"></i> Announcements</a>
                <a class="<?= $currentNav === 'reports' ? 'active' : '' ?>" href="<?= e(app_url('admin/reports/index.php')) ?>"><i class="fa-solid fa-chart-column"></i> Reports</a>
                <a class="<?= $currentNav === 'logs' ? 'active' : '' ?>" href="<?= e(app_url('admin/logs/index.php')) ?>"><i class="fa-solid fa-clipboard-list"></i> Activity logs</a>
            <?php elseif ($user && $user['role_slug'] === 'security'): ?>
                <a class="<?= $currentNav === 'dashboard' ? 'active' : '' ?>" href="<?= e(app_url('security/dashboard.php')) ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <a class="<?= $currentNav === 'scan' ? 'active' : '' ?>" href="<?= e(app_url('security/scan.php')) ?>"><i class="fa-solid fa-qrcode"></i> QR scan</a>
                <a class="<?= $currentNav === 'visitors' ? 'active' : '' ?>" href="<?= e(app_url('security/dashboard.php')) ?>#inside"><i class="fa-solid fa-door-open"></i> Inside now</a>
            <?php elseif ($user && $user['role_slug'] === 'resident'): ?>
                <a class="<?= $currentNav === 'dashboard' ? 'active' : '' ?>" href="<?= e(app_url('resident/dashboard.php')) ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <a class="<?= $currentNav === 'qr' ? 'active' : '' ?>" href="<?= e(app_url('resident/qr.php')) ?>"><i class="fa-solid fa-qrcode"></i> My QR</a>
                <a class="<?= $currentNav === 'visitors' ? 'active' : '' ?>" href="<?= e(app_url('resident/visitors/index.php')) ?>"><i class="fa-solid fa-id-card-clip"></i> My visitors</a>
                <a class="<?= $currentNav === 'announcements' ? 'active' : '' ?>" href="<?= e(app_url('resident/announcements.php')) ?>"><i class="fa-solid fa-bullhorn"></i> Announcements</a>
            <?php endif; ?>
            <?php if ($user): ?>
                <a class="<?= $currentNav === 'notifications' ? 'active' : '' ?>" href="<?= e(app_url('notifications.php')) ?>"><i class="fa-solid fa-bell"></i> Notifications<?php if ($unread > 0): ?> <span class="nav-count"><?= (int) $unread ?></span><?php endif; ?></a>
                <a class="<?= $currentNav === 'password' ? 'active' : '' ?>" href="<?= e(app_url('change-password.php')) ?>"><i class="fa-solid fa-key"></i> Password</a>
            <?php endif; ?>
        </nav>
    </aside>
    <div class="app-main">
        <header class="app-topbar">
            <button class="btn btn-sm btn-outline-light d-lg-none" type="button" data-sidebar-toggle aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="topbar-title"><?= e($pageTitle) ?></div>
            <?php if ($user): ?>
                <div class="topbar-user">
                    <span><?= e($user['full_name']) ?></span>
                    <small><?= e($user['role_name']) ?></small>
                    <a class="btn btn-sm btn-outline-light" href="<?= e(app_url('logout.php')) ?>">Sign out</a>
                </div>
            <?php endif; ?>
        </header>
        <main class="app-content">
            <?php foreach (flash_all() as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
