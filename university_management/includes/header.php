<?php
/**
 * includes/header.php - Common page header for logged-in pages
 * ----------------------------------------------------------------------
 * Prints the <head>, the sidebar navigation and the top navigation bar.
 * Before including this file, a page must set:
 *   $pageTitle  - text shown in the browser tab and top bar
 *   $activePage - key of the sidebar link to highlight
 */
$pageTitle  = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? '';
$fullName   = $_SESSION['full_name'] ?? 'User';

// Initials for the avatar circle, e.g. "System Administrator" -> "SA"
$initials = '';
foreach (array_slice(explode(' ', $fullName), 0, 2) as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}

// Sidebar links: key => [label, url, icon]
$navLinks = [
    'dashboard' => ['Dashboard',     'dashboard.php',     'dashboard'],
    'add'       => ['Add Student',   'add_student.php',   'user-plus'],
    'view'      => ['View Students', 'view_students.php', 'users'],
    'xml'       => ['XML Records',   'xml_view.php',      'xml'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">

    <!-- ============ Sidebar navigation ============ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="brand-logo"><?= icon('cap') ?></span>
            <div>
                <strong><?= e(APP_NAME) ?></strong>
                <small>Academic Management</small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <p class="nav-heading">Main Menu</p>
            <?php foreach ($navLinks as $key => $link): ?>
                <a href="<?= e($link[1]) ?>" class="nav-link<?= $activePage === $key ? ' active' : '' ?>">
                    <?= icon($link[2]) ?><span><?= e($link[0]) ?></span>
                </a>
            <?php endforeach; ?>

            <p class="nav-heading">Account</p>
            <a href="logout.php?token=<?= e(csrf_token()) ?>" class="nav-link" data-confirm="Are you sure you want to log out?">
                <?= icon('logout') ?><span>Logout</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <small>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?><br>BCA Final Year Project</small>
        </div>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ============ Main area ============ -->
    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="menu-toggle" id="menuToggle" aria-label="Toggle navigation"><?= icon('menu') ?></button>
                <div>
                    <h1 class="page-title"><?= e($pageTitle) ?></h1>
                    <p class="page-date"><?= date('l, d F Y') ?></p>
                </div>
            </div>
            <div class="topbar-right">
                <div class="user-chip">
                    <span class="avatar"><?= e($initials) ?></span>
                    <span class="user-info">
                        <strong><?= e($fullName) ?></strong>
                        <small>@<?= e($_SESSION['username'] ?? '') ?></small>
                    </span>
                </div>
                <a href="logout.php?token=<?= e(csrf_token()) ?>" class="btn btn-outline btn-sm" data-confirm="Are you sure you want to log out?">
                    <?= icon('logout') ?><span class="hide-sm">Logout</span>
                </a>
            </div>
        </header>

        <main class="content">
            <?php
            // Show the flash message (if any) at the top of every page.
            $flash = get_flash();
            if ($flash) {
                render_alert($flash['type'], $flash['message']);
            }
            ?>
