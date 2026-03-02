<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle ?? 'Portal') ?> — Nalam Pulse</title>
<link rel="stylesheet" href="<?= BASE ?>/css/style.css">
<link rel="icon" type="image/svg+xml" href="https://nalampulse.com/favicon.svg">
</head>
<body>

<div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <a href="<?= BASE ?>/dashboard.php" class="logo-link">
                <span class="logo-icon">NP</span>
                <span class="logo-text">Nalam Pulse</span>
            </a>
            <small class="logo-sub">Admin Portal</small>
        </div>

        <nav class="sidebar-nav">
            <a href="<?= BASE ?>/dashboard.php" class="sidebar-link <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </span>
                Dashboard
            </a>

            <div class="sidebar-section">Sales</div>
            <a href="<?= BASE ?>/orders.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'orders') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </span>
                Orders
            </a>
            <a href="<?= BASE ?>/customers.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'customers') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                Customers
            </a>

            <div class="sidebar-section">Support</div>
            <a href="<?= BASE ?>/tickets.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'ticket') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </span>
                Tickets
            </a>
            <a href="<?= BASE ?>/chat.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'chat') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                </span>
                Chat
            </a>
        </nav>

        <div class="sidebar-footer">
            <?php $me = admin(); ?>
            <div class="sidebar-user">
                <div class="user-avatar"><?= strtoupper(substr($me['name'] ?? 'A', 0, 2)) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= h($me['name'] ?? 'Admin') ?></div>
                    <div class="user-role"><?= h($me['email'] ?? '') ?></div>
                </div>
            </div>
            <a href="<?= BASE ?>/logout.php" class="logout-link" title="Sign out">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </a>
        </div>
    </aside>

    <!-- Main -->
    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title"><?= h($pageTitle ?? 'Dashboard') ?></h1>
            <div class="topbar-right">
                <a href="<?= MAIN_URL ?>" target="_blank" class="btn btn-sm btn-secondary">nalampulse.com ↗</a>
            </div>
        </div>

        <?php $flash = get_flash(); if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div>
        <?php endif; ?>

        <div class="content">
