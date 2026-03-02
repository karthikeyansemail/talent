<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();
$pageTitle = 'Orders';

$db = db();

// Filters
$filterStatus   = $_GET['status']   ?? '';
$filterPlan     = $_GET['plan']     ?? '';
$filterCurrency = $_GET['currency'] ?? '';
$filterSearch   = trim($_GET['search'] ?? '');
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 20;

$where  = ['1=1'];
$params = [];

if ($filterStatus) {
    $where[] = 'o.status = ?';
    $params[] = $filterStatus;
}
if ($filterPlan) {
    $where[] = 'o.plan = ?';
    $params[] = $filterPlan;
}
if ($filterCurrency) {
    $where[] = 'o.currency = ?';
    $params[] = $filterCurrency;
}
if ($filterSearch) {
    $where[] = '(c.name LIKE ? OR c.email LIKE ? OR c.company LIKE ? OR o.gateway_txn_id LIKE ?)';
    $s = '%' . $filterSearch . '%';
    $params = array_merge($params, [$s, $s, $s, $s]);
}

$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM orders o JOIN customers c ON c.id=o.customer_id WHERE {$whereStr}");
$total->execute($params);
$totalRows = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT o.*, c.name as cname, c.email as cemail, c.company as ccompany
     FROM orders o JOIN customers c ON c.id=o.customer_id
     WHERE {$whereStr}
     ORDER BY o.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Handle status change (quick action)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_status') {
        $id     = (int)$_POST['order_id'];
        $status = $_POST['new_status'];
        $allowed = ['pending','active','cancelled','expired','refunded'];
        if (in_array($status, $allowed, true)) {
            $db->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status, $id]);
            flash('success', 'Order status updated.');
        }
    }
    header('Location: ' . BASE . '/orders.php?' . http_build_query($_GET));
    exit;
}

include __DIR__ . '/includes/layout-start.php';
?>

<!-- Filters -->
<div class="filter-bar">
    <form method="GET" action="<?= BASE ?>/orders.php" class="filter-form">
        <input type="text" name="search" class="form-control" placeholder="Search name, email, TXN ID…" value="<?= h($filterSearch) ?>">
        <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <?php foreach (['pending','active','cancelled','expired','refunded'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="plan" class="form-control">
            <option value="">All Plans</option>
            <option value="free" <?= $filterPlan === 'free' ? 'selected' : '' ?>>Free</option>
            <option value="cloud_enterprise" <?= $filterPlan === 'cloud_enterprise' ? 'selected' : '' ?>>Cloud Enterprise</option>
            <option value="self_hosted" <?= $filterPlan === 'self_hosted' ? 'selected' : '' ?>>Self-Hosted</option>
        </select>
        <select name="currency" class="form-control">
            <option value="">All Currencies</option>
            <option value="USD" <?= $filterCurrency === 'USD' ? 'selected' : '' ?>>USD</option>
            <option value="INR" <?= $filterCurrency === 'INR' ? 'selected' : '' ?>>INR</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="<?= BASE ?>/orders.php" class="btn btn-secondary">Clear</a>
    </form>
    <div class="filter-count"><?= $totalRows ?> order<?= $totalRows !== 1 ? 's' : '' ?></div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th><th>Customer</th><th>Plan</th><th>Amount</th>
                <th>Gateway</th><th>Status</th><th>Period</th><th>Date</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
            <td class="td-secondary"><?= $o['id'] ?></td>
            <td>
                <div class="td-primary"><?= h($o['cname']) ?></div>
                <div class="td-secondary"><?= h($o['cemail']) ?></div>
                <?php if ($o['ccompany']): ?><div class="td-secondary"><?= h($o['ccompany']) ?></div><?php endif; ?>
            </td>
            <td><?= plan_badge($o['plan']) ?></td>
            <td><?= format_currency((float)$o['amount'], $o['currency']) ?></td>
            <td class="td-secondary"><?= h($o['payment_gateway'] ?? '—') ?></td>
            <td><?= status_badge($o['status']) ?></td>
            <td class="td-secondary"><?= h($o['billing_period'] ?? '—') ?></td>
            <td class="td-secondary"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            <td>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="change_status">
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                    <select name="new_status" class="form-control form-control-xs" onchange="this.form.submit()">
                        <?php foreach (['pending','active','cancelled','expired','refunded'] as $s): ?>
                        <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
        <tr><td colspan="9" class="empty-row">No orders found.</td></tr>
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
