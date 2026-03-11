<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'sales');

$db = db();
$id = (int)($_GET['id'] ?? 0);
$campaign = null;
$errors = [];
$spendEntries = [];

if ($id) {
    $stmt = $db->prepare('SELECT * FROM campaigns WHERE id = ?');
    $stmt->execute([$id]);
    $campaign = $stmt->fetch();
    if (!$campaign) {
        flash('error', 'Campaign not found.');
        header('Location: ' . BASE . '/campaigns.php');
        exit;
    }
    // Load spend entries
    $spendStmt = $db->prepare('SELECT * FROM ad_spend WHERE campaign_id = ? ORDER BY period_start DESC');
    $spendStmt->execute([$id]);
    $spendEntries = $spendStmt->fetchAll();
}

$pageTitle = $id ? 'Edit Campaign' : 'New Campaign';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_campaign';

    if ($action === 'save_campaign') {
        $name      = trim($_POST['name'] ?? '');
        $platform  = $_POST['platform'] ?? 'manual';
        $status    = $_POST['status'] ?? 'draft';
        $budget    = $_POST['budget'] !== '' ? (float)$_POST['budget'] : null;
        $currency  = $_POST['currency'] ?? 'USD';
        $startDate = $_POST['start_date'] ?: null;
        $endDate   = $_POST['end_date'] ?: null;
        $notes     = trim($_POST['notes'] ?? '');

        if (!$name) $errors[] = 'Name is required.';
        if (!in_array($platform, ['google_ads','meta_ads','linkedin','manual','other'], true)) $errors[] = 'Invalid platform.';
        if (!in_array($status, ['draft','active','paused','completed'], true)) $errors[] = 'Invalid status.';

        if (empty($errors)) {
            if ($id) {
                $db->prepare(
                    'UPDATE campaigns SET name=?, platform=?, status=?, budget=?, currency=?, start_date=?, end_date=?, notes=? WHERE id=?'
                )->execute([$name, $platform, $status, $budget, $currency, $startDate, $endDate, $notes, $id]);
                flash('success', 'Campaign updated.');
            } else {
                $db->prepare(
                    'INSERT INTO campaigns (name, platform, status, budget, currency, start_date, end_date, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)'
                )->execute([$name, $platform, $status, $budget, $currency, $startDate, $endDate, $notes, $_SESSION['admin_id']]);
                $id = (int)$db->lastInsertId();
                flash('success', 'Campaign created.');
            }
            header('Location: ' . BASE . '/campaign-edit.php?id=' . $id);
            exit;
        }
        // Preserve form on error
        $campaign = [
            'id' => $id, 'name' => $name, 'platform' => $platform, 'status' => $status,
            'budget' => $budget, 'currency' => $currency, 'start_date' => $startDate,
            'end_date' => $endDate, 'notes' => $notes,
        ];
    }

    if ($action === 'add_spend' && $id) {
        $periodStart = $_POST['period_start'] ?? '';
        $periodEnd   = $_POST['period_end'] ?? '';
        $amount      = (float)($_POST['spend_amount'] ?? 0);
        $currency    = $_POST['spend_currency'] ?? 'USD';
        $impressions = (int)($_POST['impressions'] ?? 0);
        $clicks      = (int)($_POST['clicks'] ?? 0);
        $leadsGen    = (int)($_POST['leads_generated'] ?? 0);

        if ($periodStart && $periodEnd && $amount > 0) {
            $db->prepare(
                'INSERT INTO ad_spend (campaign_id, period_start, period_end, amount, currency, impressions, clicks, leads_generated)
                 VALUES (?,?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE amount=VALUES(amount), period_end=VALUES(period_end), impressions=VALUES(impressions), clicks=VALUES(clicks), leads_generated=VALUES(leads_generated)'
            )->execute([$id, $periodStart, $periodEnd, $amount, $currency, $impressions, $clicks, $leadsGen]);
            flash('success', 'Spend entry saved.');
        } else {
            flash('error', 'Period dates and amount are required.');
        }
        header('Location: ' . BASE . '/campaign-edit.php?id=' . $id);
        exit;
    }

    if ($action === 'delete_spend' && $id) {
        $spendId = (int)($_POST['spend_id'] ?? 0);
        $db->prepare('DELETE FROM ad_spend WHERE id = ? AND campaign_id = ?')->execute([$spendId, $id]);
        flash('success', 'Spend entry removed.');
        header('Location: ' . BASE . '/campaign-edit.php?id=' . $id);
        exit;
    }
}

include __DIR__ . '/includes/layout-start.php';
?>

<div style="margin-bottom:16px">
    <a href="<?= BASE ?>/campaigns.php" style="color:var(--primary);font-size:13px;text-decoration:none">← Back to Campaigns</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $err): ?>
    <div><?= h($err) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="display:flex;gap:20px;align-items:flex-start">
    <!-- Campaign form -->
    <div class="card" style="flex:1;max-width:560px">
        <div class="card-header"><span><?= $id ? 'Edit Campaign' : 'Create Campaign' ?></span></div>
        <div class="card-body" style="padding:24px">
            <form method="POST">
                <input type="hidden" name="action" value="save_campaign">
                <div class="form-group">
                    <label>Campaign Name</label>
                    <input type="text" name="name" class="form-control" value="<?= h($campaign['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Platform</label>
                    <select name="platform" class="form-control">
                        <?php foreach (['google_ads'=>'Google Ads','meta_ads'=>'Meta Ads (Facebook/Instagram)','linkedin'=>'LinkedIn','manual'=>'Manual','other'=>'Other'] as $pVal => $pLabel): ?>
                        <option value="<?= $pVal ?>" <?= ($campaign['platform'] ?? 'manual') === $pVal ? 'selected' : '' ?>><?= $pLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <?php foreach (['draft'=>'Draft','active'=>'Active','paused'=>'Paused','completed'=>'Completed'] as $sVal => $sLabel): ?>
                        <option value="<?= $sVal ?>" <?= ($campaign['status'] ?? 'draft') === $sVal ? 'selected' : '' ?>><?= $sLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex;gap:12px">
                    <div class="form-group" style="flex:1">
                        <label>Budget</label>
                        <input type="number" name="budget" class="form-control" step="0.01" min="0" value="<?= h($campaign['budget'] ?? '') ?>" placeholder="0.00">
                    </div>
                    <div class="form-group" style="width:100px">
                        <label>Currency</label>
                        <select name="currency" class="form-control">
                            <option value="USD" <?= ($campaign['currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD</option>
                            <option value="INR" <?= ($campaign['currency'] ?? 'USD') === 'INR' ? 'selected' : '' ?>>INR</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:12px">
                    <div class="form-group" style="flex:1">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= h($campaign['start_date'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?= h($campaign['end_date'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?= h($campaign['notes'] ?? '') ?></textarea>
                </div>
                <div style="display:flex;gap:8px;margin-top:20px">
                    <button type="submit" class="btn btn-primary"><?= $id ? 'Update Campaign' : 'Create Campaign' ?></button>
                    <a href="<?= BASE ?>/campaigns.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Ad Spend (only for existing campaigns) -->
    <?php if ($id): ?>
    <div style="flex:1;display:flex;flex-direction:column;gap:16px">
        <div class="card">
            <div class="card-header"><span>Ad Spend</span></div>
            <div style="padding:16px">
                <form method="POST">
                    <input type="hidden" name="action" value="add_spend">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
                        <div class="form-group" style="flex:1;min-width:120px">
                            <label>Period Start</label>
                            <input type="date" name="period_start" class="form-control" required>
                        </div>
                        <div class="form-group" style="flex:1;min-width:120px">
                            <label>Period End</label>
                            <input type="date" name="period_end" class="form-control" required>
                        </div>
                        <div class="form-group" style="width:100px">
                            <label>Amount</label>
                            <input type="number" name="spend_amount" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="form-group" style="width:80px">
                            <label>Cur.</label>
                            <select name="spend_currency" class="form-control">
                                <option value="USD">USD</option>
                                <option value="INR">INR</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-top:8px">
                        <div class="form-group" style="width:100px">
                            <label>Impressions</label>
                            <input type="number" name="impressions" class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group" style="width:100px">
                            <label>Clicks</label>
                            <input type="number" name="clicks" class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group" style="width:100px">
                            <label>Leads Gen.</label>
                            <input type="number" name="leads_generated" class="form-control" min="0" value="0">
                        </div>
                        <button type="submit" class="btn btn-primary" style="height:36px">Add Spend</button>
                    </div>
                </form>
            </div>

            <?php if ($spendEntries): ?>
            <table class="data-table">
                <thead>
                    <tr><th>Period</th><th>Amount</th><th>Impressions</th><th>Clicks</th><th>Leads</th><th>CPL</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($spendEntries as $sp): ?>
                <tr>
                    <td class="td-secondary"><?= date('d M', strtotime($sp['period_start'])) ?> — <?= date('d M Y', strtotime($sp['period_end'])) ?></td>
                    <td><?= format_currency((float)$sp['amount'], $sp['currency']) ?></td>
                    <td class="td-secondary"><?= number_format($sp['impressions']) ?></td>
                    <td class="td-secondary"><?= number_format($sp['clicks']) ?></td>
                    <td class="td-secondary"><?= $sp['leads_generated'] ?></td>
                    <td class="td-secondary"><?= $sp['leads_generated'] > 0 ? format_currency((float)$sp['amount'] / $sp['leads_generated'], $sp['currency']) : '—' ?></td>
                    <td>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Remove this entry?')">
                            <input type="hidden" name="action" value="delete_spend">
                            <input type="hidden" name="spend_id" value="<?= $sp['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger)">×</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
