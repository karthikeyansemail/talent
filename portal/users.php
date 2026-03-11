<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');
$pageTitle = 'Users';

$db = db();

// Handle toggle active (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($_POST['action'] === 'toggle_active' && $uid && $uid !== (int)$_SESSION['admin_id']) {
        $db->prepare('UPDATE admin_users SET is_active = NOT is_active WHERE id = ?')->execute([$uid]);
        flash('success', 'User status updated.');
    }
    header('Location: ' . BASE . '/users.php');
    exit;
}

// Filters
$search  = trim($_GET['search'] ?? '');
$role    = $_GET['role'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[] = '(name LIKE ? OR email LIKE ?)';
    $s = '%' . $search . '%';
    $params[] = $s;
    $params[] = $s;
}
if ($role && in_array($role, ['admin','sales','support','dev'], true)) {
    $where[] = 'role = ?';
    $params[] = $role;
}

$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM admin_users WHERE {$whereStr}");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT id, name, email, role, is_active, created_at
     FROM admin_users WHERE {$whereStr}
     ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$users = $stmt->fetchAll();

include __DIR__ . '/includes/layout-start.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div></div>
    <a href="<?= BASE ?>/user-edit.php" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="vertical-align:-2px;margin-right:4px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New User
    </a>
</div>

<div class="filter-bar" style="margin-bottom:16px">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="text" name="search" class="form-control" placeholder="Search name / email..." value="<?= h($search) ?>" style="flex:1;min-width:180px">
        <select name="role" class="form-control" onchange="this.form.submit()" style="width:140px">
            <option value="">All Roles</option>
            <?php foreach (['admin','sales','support','dev'] as $r): ?>
            <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th style="width:140px"></th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><strong><?= h($u['name']) ?></strong></td>
                <td><?= h($u['email']) ?></td>
                <td><?= role_badge($u['role']) ?></td>
                <td><?= $u['is_active'] ? status_badge('active') : status_badge('cancelled') ?></td>
                <td class="td-secondary"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:8px;align-items:center">
                        <a href="<?= BASE ?>/user-edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <?php if ((int)$u['id'] !== (int)$_SESSION['admin_id']): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-danger' : 'btn-primary' ?>" data-confirm="<?= $u['is_active'] ? 'Deactivate this user?' : 'Activate this user?' ?>">
                                <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
            <tr><td colspan="6" class="empty-row">No users found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
