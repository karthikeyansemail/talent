<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'sales');
$pageTitle = 'Customers';

$db = db();

// Handle direct customer creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_customer') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $errors  = [];

    if (!$name)  $errors[] = 'Name is required.';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if ($email) {
        $dup = $db->prepare('SELECT id FROM customers WHERE email = ?');
        $dup->execute([$email]);
        if ($dup->fetch()) $errors[] = 'A customer with this email already exists.';
    }

    if (empty($errors)) {
        $db->prepare(
            'INSERT INTO customers (name, email, company, country) VALUES (?,?,?,?)'
        )->execute([$name, $email ?: null, $company ?: null, trim($_POST['country'] ?? '') ?: null]);
        $newId = $db->lastInsertId();
        $goOrder = !empty($_POST['create_order']);
        flash('success', 'Customer created successfully.');
        if ($goOrder) {
            header('Location: ' . BASE . '/orders.php?customer_id=' . $newId);
        } else {
            header('Location: ' . BASE . '/customers.php');
        }
        exit;
    }
    // On error, fall through to render page with errors
}

$createErrors = $errors ?? [];

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
        (SELECT COUNT(*) FROM tickets WHERE customer_id=c.id AND status IN ('open','in_progress')) as open_tickets,
        (SELECT l.source FROM leads l WHERE l.customer_id=c.id LIMIT 1) as lead_source,
        (SELECT l.id FROM leads l WHERE l.customer_id=c.id LIMIT 1) as lead_id
     FROM customers c WHERE {$whereStr}
     ORDER BY c.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$customers = $stmt->fetchAll();

include __DIR__ . '/includes/layout-start.php';
?>

<!-- Modal: New Customer -->
<div class="modal-overlay hidden" id="customer-modal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">New Customer</h2>
            <button class="modal-close" onclick="closeCustomerModal()">&times;</button>
        </div>
        <?php if (!empty($createErrors)): ?>
        <div class="alert alert-error" style="margin:16px 24px 0">
            <?php foreach ($createErrors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST" id="customer-form">
            <input type="hidden" name="action" value="create_customer">
            <input type="hidden" name="create_order" id="create-order-flag" value="">
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group">
                        <label>Name <span style="color:#ef4444">*</span></label>
                        <input type="text" name="name" class="form-control" required autofocus value="<?= h($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= h($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="company" class="form-control" value="<?= h($_POST['company'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" class="form-control" placeholder="e.g. India" value="<?= h($_POST['country'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCustomerModal()">Cancel</button>
                <button type="submit" class="btn btn-secondary" onclick="document.getElementById('create-order-flag').value='1'">Save &amp; Add Order</button>
                <button type="submit" class="btn btn-primary" onclick="document.getElementById('create-order-flag').value=''">Create Customer</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCustomerModal() { document.getElementById('customer-modal').classList.remove('hidden'); }
function closeCustomerModal() { document.getElementById('customer-modal').classList.add('hidden'); }
document.getElementById('customer-modal').addEventListener('click', function(e) { if (e.target === this) closeCustomerModal(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeCustomerModal(); });
<?php if (!empty($createErrors)): ?>document.addEventListener('DOMContentLoaded', openCustomerModal);<?php endif; ?>
</script>

<div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <button class="btn btn-primary" onclick="openCustomerModal()">+ New Customer</button>
</div>

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
            <tr><th>Customer</th><th>Company</th><th>Source</th><th>Current Plan</th><th>Orders</th><th>Open Tickets</th><th>Since</th></tr>
        </thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
        <tr>
            <td>
                <div class="td-primary"><?= h($c['name']) ?></div>
                <div class="td-secondary"><?= h($c['email']) ?></div>
            </td>
            <td class="td-secondary"><?= h($c['company'] ?? '—') ?></td>
            <td>
                <?php if ($c['lead_source']): ?>
                    <a href="<?= BASE ?>/lead-view.php?id=<?= $c['lead_id'] ?>" class="link"><?= platform_badge($c['lead_source']) ?></a>
                <?php else: ?>
                    <span class="td-secondary">Direct</span>
                <?php endif; ?>
            </td>
            <td><?= plan_badge($c['current_plan'] ?? 'free') ?></td>
            <td><?= $c['order_count'] ?></td>
            <td><?= $c['open_tickets'] > 0 ? "<span style=\"color:#ef4444;font-weight:600\">{$c['open_tickets']}</span>" : '0' ?></td>
            <td class="td-secondary"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($customers)): ?>
        <tr><td colspan="7" class="empty-row">No customers found.</td></tr>
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
