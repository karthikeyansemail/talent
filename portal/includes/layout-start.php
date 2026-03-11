<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle ?? 'Portal') ?> — Nalam Pulse</title>
<link rel="stylesheet" href="<?= BASE ?>/css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
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

            <?php if (has_role('admin', 'sales')): ?>
            <div class="sidebar-section">Marketing</div>
            <a href="<?= BASE ?>/campaigns.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'campaign') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                </span>
                Campaigns
            </a>
            <a href="<?= BASE ?>/leads.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'lead') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
                </span>
                Leads
            </a>

            <div class="sidebar-section">Sales</div>
            <a href="<?= BASE ?>/appointments.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'appointment') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                </span>
                Appointments
            </a>
            <a href="<?= BASE ?>/pipeline.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'pipeline') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </span>
                Pipeline
            </a>
            <a href="<?= BASE ?>/customers.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'customers') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                Customers
            </a>
            <a href="<?= BASE ?>/orders.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'orders') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </span>
                Orders
            </a>
            <?php endif; ?>

            <?php if (has_role('admin', 'support')): ?>
            <div class="sidebar-section">Support</div>
            <a href="<?= BASE ?>/tickets.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'ticket') && !str_contains($_SERVER['PHP_SELF'], 'dev-ticket') ? 'active' : '' ?>">
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
            <?php endif; ?>

            <?php if (has_role('admin', 'dev')): ?>
            <div class="sidebar-section">Dev Tools</div>
            <a href="<?= BASE ?>/errors.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'error') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                Error Logs
            </a>
            <a href="<?= BASE ?>/dev-tickets.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'dev-ticket') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                </span>
                Dev Tickets
            </a>
            <a href="<?= BASE ?>/instances.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'instance') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
                </span>
                Instances
            </a>
            <?php endif; ?>

            <?php if (has_role('admin')): ?>
            <div class="sidebar-section">Settings</div>
            <a href="<?= BASE ?>/users.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'user') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                </span>
                Users
            </a>
            <a href="<?= BASE ?>/integrations.php" class="sidebar-link <?= str_contains($_SERVER['PHP_SELF'], 'integration') ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </span>
                Integrations
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <?php $me = admin(); ?>
            <div class="sidebar-user">
                <div class="user-avatar"><?= strtoupper(substr($me['name'] ?? 'A', 0, 2)) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= h($me['name'] ?? 'Admin') ?></div>
                    <div class="user-email"><?= h($me['email'] ?? '') ?></div>
                    <div class="user-role-label"><?= role_badge($me['role'] ?? 'admin') ?></div>
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
