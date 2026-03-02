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

function admin(): ?array
{
    if (empty($_SESSION['admin_id'])) return null;
    static $me = null;
    if ($me === null) {
        $stmt = db()->prepare('SELECT id, name, email FROM admin_users WHERE id = ? AND is_active = 1');
        $stmt->execute([$_SESSION['admin_id']]);
        $me = $stmt->fetch() ?: null;
    }
    return $me;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}
