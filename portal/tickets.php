<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();
$pageTitle = 'Tickets';

$db = db();

$filterStatus   = $_GET['status']   ?? '';
$filterPriority = $_GET['priority'] ?? '';
$filterSearch   = trim($_GET['search'] ?? '');
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 20;

$where  = ['1=1'];
$params = [];

if ($filterStatus) {
    $where[] = 't.status = ?';
    $params[] = $filterStatus;
}
if ($filterPriority) {
    $where[] = 't.priority = ?';
    $params[] = $filterPriority;
}
if ($filterSearch) {
    $where[] = '(c.name LIKE ? OR c.email LIKE ? OR t.subject LIKE ?)';
    $s = '%' . $filterSearch . '%';
    $params = array_merge($params, [$s, $s, $s]);
}

$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM tickets t JOIN customers c ON c.id=t.customer_id WHERE {$whereStr}");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$offset     = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT t.*, c.name as cname, c.email as cemail,
        (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id=t.id) as msg_count
     FROM tickets t JOIN customers c ON c.id=t.customer_id
     WHERE {$whereStr}
     ORDER BY t.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

include __DIR__ . '/includes/layout-start.php';
?>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <input type="text" name="search" class="form-control" placeholder="Customer, subject…" value="<?= h($filterSearch) ?>">
        <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <?php foreach (['open','in_progress','resolved','closed'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="priority" class="form-control">
            <option value="">All Priorities</option>
            <?php foreach (['low','normal','high','urgent'] as $p): ?>
            <option value="<?= $p ?>" <?= $filterPriority === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="<?= BASE ?>/tickets.php" class="btn btn-secondary">Clear</a>
    </form>
    <div class="filter-count"><?= $totalRows ?> ticket<?= $totalRows !== 1 ? 's' : '' ?></div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr><th>#</th><th>Customer</th><th>Subject</th><th>Priority</th><th>Status</th><th>Messages</th><th>Created</th></tr>
        </thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
        <tr>
            <td class="td-secondary"><?= $t['id'] ?></td>
            <td>
                <div class="td-primary"><?= h($t['cname']) ?></div>
                <div class="td-secondary"><?= h($t['cemail']) ?></div>
            </td>
            <td>
                <a href="<?= BASE ?>/ticket-view.php?id=<?= $t['id'] ?>" class="link"><?= h($t['subject']) ?></a>
            </td>
            <td><?= status_badge($t['priority']) ?></td>
            <td><?= status_badge($t['status']) ?></td>
            <td class="td-secondary"><?= $t['msg_count'] ?></td>
            <td class="td-secondary"><?= time_ago($t['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($tickets)): ?>
        <tr><td colspan="7" class="empty-row">No tickets found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
           class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
