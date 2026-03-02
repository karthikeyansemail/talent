<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();
$pageTitle = 'Customers';

$db = db();

$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[] = '(c.name LIKE ? OR c.email LIKE ? OR c.company LIKE ?)';
    $s = '%' . $search . '%';
    $params = [$s, $s, $s];
}
$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM customers c WHERE {$whereStr}");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$offset     = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT c.*,
        (SELECT plan FROM orders WHERE customer_id=c.id AND status='active' ORDER BY created_at DESC LIMIT 1) as current_plan,
        (SELECT COUNT(*) FROM orders WHERE customer_id=c.id) as order_count,
        (SELECT COUNT(*) FROM tickets WHERE customer_id=c.id AND status IN ('open','in_progress')) as open_tickets
     FROM customers c WHERE {$whereStr}
     ORDER BY c.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$customers = $stmt->fetchAll();

include __DIR__ . '/includes/layout-start.php';
?>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <input type="text" name="search" class="form-control" placeholder="Name, email, company…" value="<?= h($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="<?= BASE ?>/customers.php" class="btn btn-secondary">Clear</a>
    </form>
    <div class="filter-count"><?= $totalRows ?> customer<?= $totalRows !== 1 ? 's' : '' ?></div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr><th>Customer</th><th>Company</th><th>Current Plan</th><th>Orders</th><th>Open Tickets</th><th>Since</th></tr>
        </thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
        <tr>
            <td>
                <div class="td-primary"><?= h($c['name']) ?></div>
                <div class="td-secondary"><?= h($c['email']) ?></div>
            </td>
            <td class="td-secondary"><?= h($c['company'] ?? '—') ?></td>
            <td><?= plan_badge($c['current_plan'] ?? 'free') ?></td>
            <td><?= $c['order_count'] ?></td>
            <td><?= $c['open_tickets'] > 0 ? "<span style=\"color:#ef4444;font-weight:600\">{$c['open_tickets']}</span>" : '0' ?></td>
            <td class="td-secondary"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($customers)): ?>
        <tr><td colspan="6" class="empty-row">No customers found.</td></tr>
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
