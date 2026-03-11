<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'dev');
$pageTitle = 'Dev Tickets';

$db = db();

// Filters
$filterStatus   = $_GET['status'] ?? '';
$filterPriority = $_GET['priority'] ?? '';
$filterInstance = (int)($_GET['instance'] ?? 0);
$filterSearch   = trim($_GET['search'] ?? '');
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 30;

$where  = ['1=1'];
$params = [];

if ($filterStatus && in_array($filterStatus, ['open','investigating','in_progress','resolved','closed'], true)) {
    $where[]  = 'd.status = ?';
    $params[] = $filterStatus;
}
if ($filterPriority && in_array($filterPriority, ['low','normal','high','critical'], true)) {
    $where[]  = 'd.priority = ?';
    $params[] = $filterPriority;
}
if ($filterInstance) {
    $where[]  = 'd.instance_id = ?';
    $params[] = $filterInstance;
}
if ($filterSearch) {
    $where[]  = '(d.title LIKE ? OR d.description LIKE ?)';
    $s = '%' . $filterSearch . '%';
    $params[] = $s;
    $params[] = $s;
}

$whereStr = implode(' AND ', $where);

$instances = $db->query('SELECT id, domain FROM instances ORDER BY domain')->fetchAll();

// Fetch dev users for assignment display
$devUsers = $db->query("SELECT id, name FROM admin_users WHERE role IN ('admin','dev') AND is_active=1 ORDER BY name")->fetchAll();

$countStmt = $db->prepare("SELECT COUNT(*) FROM dev_tickets d WHERE {$whereStr}");
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT d.*, i.domain as instance_domain, a.name as assigned_name, c.name as creator_name
     FROM dev_tickets d
     LEFT JOIN instances i ON i.id = d.instance_id
     LEFT JOIN admin_users a ON a.id = d.assigned_to
     LEFT JOIN admin_users c ON c.id = d.created_by
     WHERE {$whereStr}
     ORDER BY FIELD(d.status,'open','investigating','in_progress','resolved','closed'), d.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

include __DIR__ . '/includes/layout-start.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div></div>
    <a href="<?= BASE ?>/dev-ticket-view.php?new=1" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="vertical-align:-2px;margin-right:4px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Ticket
    </a>
</div>

<div class="filter-bar" style="margin-bottom:16px">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="text" name="search" class="form-control" placeholder="Search title / description..." value="<?= h($filterSearch) ?>" style="flex:1;min-width:180px">
        <select name="status" class="form-control" onchange="this.form.submit()" style="width:140px">
            <option value="">All Status</option>
            <?php foreach (['open','investigating','in_progress','resolved','closed'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="priority" class="form-control" onchange="this.form.submit()" style="width:120px">
            <option value="">All Priority</option>
            <?php foreach (['critical','high','normal','low'] as $p): ?>
            <option value="<?= $p ?>" <?= $filterPriority === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="instance" class="form-control" onchange="this.form.submit()" style="width:160px">
            <option value="">All Instances</option>
            <?php foreach ($instances as $inst): ?>
            <option value="<?= $inst['id'] ?>" <?= $filterInstance === (int)$inst['id'] ? 'selected' : '' ?>><?= h($inst['domain']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Title</th>
                <th style="width:80px">Source</th>
                <th>Instance</th>
                <th style="width:70px">Priority</th>
                <th style="width:100px">Status</th>
                <th>Assigned</th>
                <th style="width:90px">Created</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
            <tr>
                <td class="td-secondary"><?= $t['id'] ?></td>
                <td>
                    <a href="<?= BASE ?>/dev-ticket-view.php?id=<?= $t['id'] ?>" class="link" style="font-weight:600"><?= h($t['title']) ?></a>
                </td>
                <td>
                    <?php if ($t['error_log_id']): ?>
                    <span style="font-size:11px;color:var(--danger)">Error</span>
                    <?php elseif ($t['support_ticket_id']): ?>
                    <span style="font-size:11px;color:var(--info)">Support</span>
                    <?php else: ?>
                    <span style="font-size:11px;color:var(--gray-400)">Manual</span>
                    <?php endif; ?>
                </td>
                <td class="td-secondary"><?= h($t['instance_domain'] ?? '-') ?></td>
                <td><?= priority_badge($t['priority']) ?></td>
                <td><?= status_badge($t['status']) ?></td>
                <td class="td-secondary"><?= h($t['assigned_name'] ?? 'Unassigned') ?></td>
                <td class="td-secondary"><?= time_ago($t['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($tickets)): ?>
            <tr><td colspan="8" class="empty-row">No dev tickets found.</td></tr>
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
