<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'sales');
$pageTitle = 'Create Order';

$db = db();
$errors = [];

// Pre-fill from conversion: ?customer_id=X&company=Y&plan=self_hosted
$preCustomerId = (int)($_GET['customer_id'] ?? 0);
$prePlan       = $_GET['plan'] ?? 'cloud_enterprise';
$preLeadId     = (int)($_GET['lead_id'] ?? 0);

// Load customer list
$customers = $db->query('SELECT id, name, email, company FROM customers ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId     = (int)($_POST['customer_id'] ?? 0);
    $plan           = $_POST['plan'] ?? 'cloud_enterprise';
    $amount         = (float)($_POST['amount'] ?? 0);
    $currency       = $_POST['currency'] ?? 'USD';
    $billingPeriod  = $_POST['billing_period'] ?? 'annual';
    $gateway        = trim($_POST['payment_gateway'] ?? '');
    $txnId          = trim($_POST['gateway_txn_id'] ?? '');
    $status         = $_POST['status'] ?? 'active';
    $startsAt       = $_POST['starts_at'] ?? date('Y-m-d');
    $expiresAt      = $_POST['expires_at'] ?? '';

    if (!$customerId)  $errors[] = 'Customer is required.';
    if ($amount <= 0 && $plan !== 'free') $errors[] = 'Amount must be greater than zero for paid plans.';
    if (!in_array($plan, ['free','cloud_enterprise','self_hosted'], true)) $errors[] = 'Invalid plan.';
    if (!in_array($currency, ['USD','INR'], true)) $errors[] = 'Invalid currency.';
    if (!in_array($status, ['pending','active','cancelled','expired','refunded'], true)) $errors[] = 'Invalid status.';

    if (empty($errors)) {
        $db->prepare(
            'INSERT INTO orders (customer_id, plan, amount, currency, billing_period, payment_gateway, gateway_txn_id, status, starts_at, expires_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $customerId, $plan, $amount, $currency, $billingPeriod,
            $gateway ?: null, $txnId ?: null, $status,
            $startsAt ?: null, $expiresAt ?: null,
        ]);
        $orderId = (int)$db->lastInsertId();

        // Log activity on the originating lead if present
        if ($preLeadId) {
            $db->prepare('INSERT INTO lead_activities (lead_id, user_id, type, description) VALUES (?,?,?,?)')
               ->execute([$preLeadId, $_SESSION['admin_id'], 'note',
                 "Order #{$orderId} created — " . plan_label($plan) . " ({$currency} {$amount})"]);
        }

        flash('success', 'Order #' . $orderId . ' created.');

        // For self_hosted plans, go to instance provisioning
        if ($plan === 'self_hosted' && $status === 'active') {
            header('Location: ' . BASE . '/instances.php?provision=1&order_id=' . $orderId . '&customer_id=' . $customerId);
        } else {
            header('Location: ' . BASE . '/orders.php');
        }
        exit;
    }
}

include __DIR__ . '/includes/layout-start.php';
?>

<div style="margin-bottom:16px">
    <a href="<?= BASE ?>/orders.php" style="color:var(--primary);font-size:13px;text-decoration:none">← Back to Orders</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $err): ?><div><?= h($err) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:600px">
    <div class="card-header"><span>Create Order / Invoice</span></div>
    <div class="card-body" style="padding:24px">
        <form method="POST">
            <div class="form-group">
                <label>Customer *</label>
                <select name="customer_id" class="form-control" required id="sel-customer">
                    <option value="">Select customer…</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"
                        data-company="<?= h($c['company'] ?? '') ?>"
                        <?= ($preCustomerId && $preCustomerId == $c['id']) || ($_POST['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= h($c['name']) ?><?= $c['company'] ? ' — ' . h($c['company']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Plan *</label>
                <select name="plan" class="form-control" id="sel-plan" onchange="onPlanChange(this.value)">
                    <option value="free"             <?= ($prePlan === 'free' || ($_POST['plan'] ?? '') === 'free')             ? 'selected' : '' ?>>Free</option>
                    <option value="cloud_enterprise" <?= ($prePlan === 'cloud_enterprise' || ($_POST['plan'] ?? '') === 'cloud_enterprise') ? 'selected' : '' ?>>Cloud Enterprise</option>
                    <option value="self_hosted"      <?= ($prePlan === 'self_hosted' || ($_POST['plan'] ?? '') === 'self_hosted')      ? 'selected' : '' ?>>Self-Hosted Enterprise</option>
                </select>
            </div>

            <div id="amount-row" style="display:flex;gap:12px">
                <div class="form-group" style="flex:2">
                    <label>Amount</label>
                    <input type="number" name="amount" class="form-control" min="0" step="0.01"
                           value="<?= h($_POST['amount'] ?? '0') ?>" id="inp-amount">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Currency</label>
                    <select name="currency" class="form-control">
                        <option value="USD" <?= ($_POST['currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD</option>
                        <option value="INR" <?= ($_POST['currency'] ?? '') === 'INR' ? 'selected' : '' ?>>INR</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Billing</label>
                    <select name="billing_period" class="form-control">
                        <?php foreach (['monthly'=>'Monthly','quarterly'=>'Quarterly','annual'=>'Annual','one_time'=>'One-time'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($_POST['billing_period'] ?? 'annual') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:12px">
                <div class="form-group" style="flex:1">
                    <label>Payment Gateway</label>
                    <select name="payment_gateway" class="form-control">
                        <option value="">None / Manual</option>
                        <?php foreach (['stripe'=>'Stripe','razorpay'=>'Razorpay','bank_transfer'=>'Bank Transfer','cash'=>'Cash'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($_POST['payment_gateway'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Transaction ID</label>
                    <input type="text" name="gateway_txn_id" class="form-control"
                           value="<?= h($_POST['gateway_txn_id'] ?? '') ?>" placeholder="TXN / Invoice #">
                </div>
            </div>

            <div style="display:flex;gap:12px">
                <div class="form-group" style="flex:1">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <?php foreach (['pending'=>'Pending','active'=>'Active','cancelled'=>'Cancelled'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($_POST['status'] ?? 'active') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Starts</label>
                    <input type="date" name="starts_at" class="form-control"
                           value="<?= h($_POST['starts_at'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Expires</label>
                    <input type="date" name="expires_at" class="form-control"
                           value="<?= h($_POST['expires_at'] ?? '') ?>">
                </div>
            </div>

            <?php if ($preLeadId): ?>
            <input type="hidden" name="lead_id" value="<?= $preLeadId ?>">
            <?php endif; ?>

            <div id="self-hosted-note" style="display:none;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px;font-size:13px;color:#1e40af;margin-bottom:16px">
                <strong>Self-Hosted plan:</strong> After saving, you'll be taken to instance provisioning to register this customer's server.
            </div>

            <div style="display:flex;gap:8px;margin-top:20px">
                <button type="submit" class="btn btn-primary">Create Order</button>
                <a href="<?= BASE ?>/orders.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function onPlanChange(plan) {
    var amountRow = document.getElementById('amount-row');
    var note = document.getElementById('self-hosted-note');
    var amt = document.getElementById('inp-amount');
    if (plan === 'free') {
        amountRow.style.opacity = '0.4';
        amt.value = '0';
        note.style.display = 'none';
    } else {
        amountRow.style.opacity = '1';
        note.style.display = plan === 'self_hosted' ? 'block' : 'none';
    }
}
// Init on load
onPlanChange(document.getElementById('sel-plan').value);
</script>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
