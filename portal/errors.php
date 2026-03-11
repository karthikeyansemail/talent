<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'dev');
$pageTitle = 'Error Logs';

$db = db();

// Handle bulk resolve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resolve') {
    $eid = (int)($_POST['error_id'] ?? 0);
    if ($eid) {
        $db->prepare('UPDATE error_logs SET is_resolved = 1, resolved_by = ?, resolved_at = NOW() WHERE id = ?')
           ->execute([$_SESSION['admin_id'], $eid]);
        flash('success', 'Error marked as resolved.');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Filters
$filterInstance = (int)($_GET['instance'] ?? 0);
$filterLevel    = $_GET['level'] ?? '';
$filterResolved = $_GET['resolved'] ?? '';
$filterSearch   = trim($_GET['search'] ?? '');
$filterFrom     = $_GET['from'] ?? '';
$filterTo       = $_GET['to'] ?? '';
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 30;

$where  = ['1=1'];
$params = [];

if ($filterInstance) {
    $where[]  = 'e.instance_id = ?';
    $params[] = $filterInstance;
}
if ($filterLevel && in_array($filterLevel, ['error','warning','critical','notice'], true)) {
    $where[]  = 'e.level = ?';
    $params[] = $filterLevel;
}
if ($filterResolved !== '') {
    $where[]  = 'e.is_resolved = ?';
    $params[] = (int)$filterResolved;
}
if ($filterSearch) {
    $where[] = '(e.message LIKE ? OR e.exception_class LIKE ? OR e.file LIKE ?)';
    $s = '%' . $filterSearch . '%';
    $params = array_merge($params, [$s, $s, $s]);
}
if ($filterFrom) {
    $where[]  = 'e.last_seen_at >= ?';
    $params[] = $filterFrom . ' 00:00:00';
}
if ($filterTo) {
    $where[]  = 'e.last_seen_at <= ?';
    $params[] = $filterTo . ' 23:59:59';
}

$whereStr = implode(' AND ', $where);

// Instances for dropdown
$instances = $db->query('SELECT id, domain FROM instances ORDER BY domain')->fetchAll();

// Count + paginate
$countStmt = $db->prepare("SELECT COUNT(*) FROM error_logs e WHERE {$whereStr}");
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT e.*, i.domain as instance_domain
     FROM error_logs e
     JOIN instances i ON i.id = e.instance_id
     WHERE {$whereStr}
     ORDER BY e.last_seen_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$errors = $stmt->fetchAll();

include __DIR__ . '/includes/layout-start.php';
?>

<div class="filter-bar" style="margin-bottom:16px">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="text" name="search" class="form-control" placeholder="Search message / class / file..." value="<?= h($filterSearch) ?>" style="flex:1;min-width:180px">
        <select name="instance" class="form-control" onchange="this.form.submit()" style="width:160px">
            <option value="">All Instances</option>
            <?php foreach ($instances as $inst): ?>
            <option value="<?= $inst['id'] ?>" <?= $filterInstance === (int)$inst['id'] ? 'selected' : '' ?>><?= h($inst['domain']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="level" class="form-control" onchange="this.form.submit()" style="width:120px">
            <option value="">All Levels</option>
            <?php foreach (['critical','error','warning','notice'] as $lv): ?>
            <option value="<?= $lv ?>" <?= $filterLevel === $lv ? 'selected' : '' ?>><?= ucfirst($lv) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="resolved" class="form-control" onchange="this.form.submit()" style="width:130px">
            <option value="">All Status</option>
            <option value="0" <?= $filterResolved === '0' ? 'selected' : '' ?>>Unresolved</option>
            <option value="1" <?= $filterResolved === '1' ? 'selected' : '' ?>>Resolved</option>
        </select>
        <input type="date" name="from" class="form-control" value="<?= h($filterFrom) ?>" style="width:140px" title="From date">
        <input type="date" name="to" class="form-control" value="<?= h($filterTo) ?>" style="width:140px" title="To date">
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:70px">Level</th>
                <th>Exception</th>
                <th>File</th>
                <th>Instance</th>
                <th style="width:60px">Count</th>
                <th style="width:100px">Last Seen</th>
                <th style="width:80px">Status</th>
                <th style="width:100px"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($errors as $err): ?>
            <tr>
                <td><?= level_badge($err['level']) ?></td>
                <td>
                    <a href="<?= BASE ?>/error-view.php?id=<?= $err['id'] ?>" class="link" style="font-weight:600"><?= h(basename($err['exception_class'] ?: 'Error')) ?></a>
                    <div class="td-secondary" style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h(mb_substr($err['message'], 0, 80)) ?></div>
                </td>
                <td class="td-secondary" style="font-size:12px;font-family:monospace"><?= h(basename($err['file'] ?? '')) ?>:<?= $err['line'] ?></td>
                <td class="td-secondary"><?= h($err['instance_domain']) ?></td>
                <td style="text-align:center"><strong><?= number_format($err['occurrence_count']) ?></strong></td>
                <td class="td-secondary"><?= time_ago($err['last_seen_at']) ?></td>
                <td><?= $err['is_resolved'] ? status_badge('resolved') : status_badge('open') ?></td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center">
                        <a href="<?= BASE ?>/error-view.php?id=<?= $err['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                        <?php if (!$err['is_resolved']): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="resolve">
                            <input type="hidden" name="error_id" value="<?= $err['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-primary" style="padding:4px 8px;font-size:11px">Resolve</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($errors)): ?>
            <tr><td colspan="8" class="empty-row">No errors found.</td></tr>
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

<div style="margin-top:12px;font-size:12px;color:var(--gray-400)"><?= number_format($totalRows) ?> total errors</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
