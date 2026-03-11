<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'sales');
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

// Load customer list for the create-order modal
$allCustomers = $db->query('SELECT id, name, email, company FROM customers ORDER BY name')->fetchAll();

$orderErrors     = [];
$orderModalOpen  = false;
$preCustomerId   = (int)($_GET['customer_id'] ?? 0);
$preLeadId       = (int)($_GET['lead_id']     ?? 0);
$prePlan         = $_GET['plan'] ?? 'cloud_enterprise';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_status') {
        $id      = (int)$_POST['order_id'];
        $status  = $_POST['new_status'];
        $allowed = ['pending','active','cancelled','expired','refunded'];
        if (in_array($status, $allowed, true)) {
            $db->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status, $id]);
            flash('success', 'Order status updated.');
        }
        header('Location: ' . BASE . '/orders.php?' . http_build_query($_GET));
        exit;
    }

    if ($_POST['action'] === 'create_order') {
        $customerId    = (int)($_POST['customer_id'] ?? 0);
        $plan          = $_POST['plan'] ?? 'cloud_enterprise';
        $amount        = (float)($_POST['amount'] ?? 0);
        $currency      = $_POST['currency'] ?? 'USD';
        $billingPeriod = $_POST['billing_period'] ?? 'annual';
        $gateway       = trim($_POST['payment_gateway'] ?? '');
        $txnId         = trim($_POST['gateway_txn_id'] ?? '');
        $orderStatus   = $_POST['order_status'] ?? 'active';
        $startsAt      = $_POST['starts_at'] ?? date('Y-m-d');
        $expiresAt     = $_POST['expires_at'] ?? '';
        $fromLeadId    = (int)($_POST['lead_id'] ?? 0);

        if (!$customerId) $orderErrors[] = 'Customer is required.';
        if ($amount <= 0 && $plan !== 'free') $orderErrors[] = 'Amount must be greater than zero for paid plans.';
        if (!in_array($plan, ['free','cloud_enterprise','self_hosted'], true)) $orderErrors[] = 'Invalid plan.';
        if (!in_array($currency, ['USD','INR'], true)) $orderErrors[] = 'Invalid currency.';
        if (!in_array($orderStatus, ['pending','active','cancelled','expired','refunded'], true)) $orderErrors[] = 'Invalid status.';

        if (empty($orderErrors)) {
            $db->prepare(
                'INSERT INTO orders (customer_id, plan, amount, currency, billing_period, payment_gateway, gateway_txn_id, status, starts_at, expires_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $customerId, $plan, $amount, $currency, $billingPeriod,
                $gateway ?: null, $txnId ?: null, $orderStatus,
                $startsAt ?: null, $expiresAt ?: null,
            ]);
            $orderId = (int)$db->lastInsertId();
            if ($fromLeadId) {
                $db->prepare('INSERT INTO lead_activities (lead_id, user_id, type, description) VALUES (?,?,?,?)')
                   ->execute([$fromLeadId, $_SESSION['admin_id'], 'note',
                     "Order #{$orderId} created — " . plan_label($plan) . " ({$currency} {$amount})"]);
            }
            flash('success', 'Order #' . $orderId . ' created.');
            if ($plan === 'self_hosted' && $orderStatus === 'active') {
                header('Location: ' . BASE . '/instances.php?provision=1&order_id=' . $orderId . '&customer_id=' . $customerId);
            } else {
                header('Location: ' . BASE . '/orders.php');
            }
            exit;
        }
        $orderModalOpen = true; // re-open modal on validation errors
    }
}

include __DIR__ . '/includes/layout-start.php';
?>

<!-- Modal: Create Order -->
<div class="modal-overlay hidden" id="order-modal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h2 class="modal-title">Create Order</h2>
            <button class="modal-close" onclick="closeOrderModal()">&times;</button>
        </div>
        <?php if (!empty($orderErrors)): ?>
        <div class="alert alert-error" style="margin:16px 24px 0">
            <?php foreach ($orderErrors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="create_order">
            <?php if ($preLeadId): ?><input type="hidden" name="lead_id" value="<?= $preLeadId ?>"><?php endif; ?>
            <div class="modal-body">
                <div class="form-group">
                    <label>Customer *</label>
                    <select name="customer_id" class="form-control" required>
                        <option value="">Select customer…</option>
                        <?php foreach ($allCustomers as $c): ?>
                        <option value="<?= $c['id'] ?>"
                            <?= ($preCustomerId && $preCustomerId == $c['id']) || ($_POST['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= h($c['name']) ?><?= $c['company'] ? ' — ' . h($c['company']) : '' ?> &lt;<?= h($c['email']) ?>&gt;
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Plan *</label>
                    <select name="plan" class="form-control" id="ord-plan" onchange="onOrderPlanChange(this.value)">
                        <option value="free"             <?= ($prePlan === 'free'             || ($_POST['plan'] ?? '') === 'free')             ? 'selected' : '' ?>>Free</option>
                        <option value="cloud_enterprise" <?= ($prePlan === 'cloud_enterprise' || ($_POST['plan'] ?? '') === 'cloud_enterprise') ? 'selected' : '' ?>>Cloud Enterprise</option>
                        <option value="self_hosted"      <?= ($prePlan === 'self_hosted'      || ($_POST['plan'] ?? '') === 'self_hosted')      ? 'selected' : '' ?>>Self-Hosted Enterprise</option>
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px" id="ord-amount-row">
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" name="amount" id="ord-amount" class="form-control" min="0" step="0.01" value="<?= h($_POST['amount'] ?? '0') ?>">
                    </div>
                    <div class="form-group">
                        <label>Currency</label>
                        <select name="currency" class="form-control">
                            <option value="USD" <?= ($_POST['currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD</option>
                            <option value="INR" <?= ($_POST['currency'] ?? '') === 'INR' ? 'selected' : '' ?>>INR</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Billing</label>
                        <select name="billing_period" class="form-control">
                            <?php foreach (['monthly'=>'Monthly','quarterly'=>'Quarterly','annual'=>'Annual','one_time'=>'One-time'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($_POST['billing_period'] ?? 'annual') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Payment Gateway</label>
                        <select name="payment_gateway" class="form-control">
                            <option value="">None / Manual</option>
                            <?php foreach (['stripe'=>'Stripe','razorpay'=>'Razorpay','bank_transfer'=>'Bank Transfer','cash'=>'Cash'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($_POST['payment_gateway'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Transaction ID</label>
                        <input type="text" name="gateway_txn_id" class="form-control" placeholder="TXN / Invoice #" value="<?= h($_POST['gateway_txn_id'] ?? '') ?>">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="order_status" class="form-control">
                            <?php foreach (['pending'=>'Pending','active'=>'Active','cancelled'=>'Cancelled'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($_POST['order_status'] ?? 'active') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Starts</label>
                        <input type="date" name="starts_at" class="form-control" value="<?= h($_POST['starts_at'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="form-group">
                        <label>Expires</label>
                        <input type="date" name="expires_at" class="form-control" value="<?= h($_POST['expires_at'] ?? '') ?>">
                    </div>
                </div>

                <div id="ord-selfhosted-note" style="display:none;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;font-size:13px;color:#1e40af">
                    <strong>Self-Hosted plan:</strong> After saving you'll be taken to instance provisioning.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeOrderModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Order</button>
            </div>
        </form>
    </div>
</div>

<script>
function openOrderModal()  { document.getElementById('order-modal').classList.remove('hidden'); onOrderPlanChange(document.getElementById('ord-plan').value); }
function closeOrderModal() { document.getElementById('order-modal').classList.add('hidden'); }
document.getElementById('order-modal').addEventListener('click', function(e) { if (e.target === this) closeOrderModal(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeOrderModal(); });
function onOrderPlanChange(plan) {
    var row  = document.getElementById('ord-amount-row');
    var amt  = document.getElementById('ord-amount');
    var note = document.getElementById('ord-selfhosted-note');
    row.style.opacity = plan === 'free' ? '0.4' : '1';
    if (plan === 'free') amt.value = '0';
    note.style.display = plan === 'self_hosted' ? 'block' : 'none';
}
<?php if ($orderModalOpen || $preCustomerId): ?>document.addEventListener('DOMContentLoaded', openOrderModal);<?php endif; ?>
</script>

<div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <button class="btn btn-primary" onclick="openOrderModal()">+ Create Order</button>
</div>

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
                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="change_status">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <select name="new_status" class="form-control form-control-xs" onchange="this.form.submit()">
                            <?php foreach (['pending','active','cancelled','expired','refunded'] as $s): ?>
                            <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php if ($o['plan'] === 'self_hosted' && $o['status'] === 'active' && empty($o['instance_id'])): ?>
                    <a href="<?= BASE ?>/instances.php?provision=1&order_id=<?= $o['id'] ?>&customer_id=<?= $o['customer_id'] ?>"
                       class="btn btn-sm btn-primary" style="white-space:nowrap" title="Provision self-hosted instance for this customer">
                        Provision Instance
                    </a>
                    <?php elseif (!empty($o['instance_id'])): ?>
                    <a href="<?= BASE ?>/instances.php" class="td-secondary" style="font-size:11px" title="Instance registered">&#x2713; Instance</a>
                    <?php endif; ?>
                </div>
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
