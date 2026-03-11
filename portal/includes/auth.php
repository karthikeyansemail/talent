<?php
/**
 * Session-based admin auth helpers
 */
function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . BASE . '/login.php');
        exit;
    }
}

/**
 * Require that the logged-in user has one of the given roles.
 */
function require_role(string ...$roles): void
{
    require_admin();
    $me = admin();
    if (!$me || !in_array($me['role'], $roles, true)) {
        http_response_code(403);
        $pageTitle = 'Access Denied';
        include __DIR__ . '/layout-start.php';
        echo '<div class="card" style="padding:48px;text-align:center">';
        echo '<svg viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="1.5" width="48" height="48" style="margin:0 auto 16px;display:block"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>';
        echo '<h2 style="color:var(--danger);margin:0 0 8px">403 — Forbidden</h2>';
        echo '<p class="text-muted" style="margin:0 0 20px">You do not have permission to access this page.</p>';
        echo '<a href="' . BASE . '/dashboard.php" class="btn btn-primary">Back to Dashboard</a>';
        echo '</div>';
        include __DIR__ . '/layout-end.php';
        exit;
    }
}

/**
 * Check if logged-in user has any of the specified roles.
 */
function has_role(string ...$roles): bool
{
    $me = admin();
    return $me && in_array($me['role'], $roles, true);
}

function admin(): ?array
{
    if (empty($_SESSION['admin_id'])) return null;
    static $me = null;
    if ($me === null) {
        $stmt = db()->prepare('SELECT id, name, email, role FROM admin_users WHERE id = ? AND is_active = 1');
        $stmt->execute([$_SESSION['admin_id']]);
        $me = $stmt->fetch() ?: null;
    }
    return $me;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}
