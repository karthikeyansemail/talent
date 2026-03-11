<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'sales');
$pageTitle = 'Campaigns';

$db = db();

$filterPlatform = $_GET['platform'] ?? '';
$filterStatus   = $_GET['status']   ?? '';
$filterSearch   = trim($_GET['search'] ?? '');
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 20;

$where  = ['1=1'];
$params = [];

if ($filterPlatform) {
    $where[]  = 'c.platform = ?';
    $params[] = $filterPlatform;
}
if ($filterStatus) {
    $where[]  = 'c.status = ?';
    $params[] = $filterStatus;
}
if ($filterSearch) {
    $where[]  = 'c.name LIKE ?';
    $params[] = '%' . $filterSearch . '%';
}

$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM campaigns c WHERE {$whereStr}");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$offset     = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT c.*,
        COALESCE((SELECT SUM(amount) FROM ad_spend WHERE campaign_id=c.id), 0) as total_spend,
        (SELECT COUNT(*) FROM leads WHERE campaign_id=c.id) as lead_count
     FROM campaigns c WHERE {$whereStr}
     ORDER BY c.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$campaigns = $stmt->fetchAll();

$campErrors    = [];
$campModalOpen = false;

// Handle modal campaign creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_campaign') {
    $campName  = trim($_POST['camp_name'] ?? '');
    $platform  = $_POST['platform'] ?? 'manual';
    $campStatus = $_POST['camp_status'] ?? 'draft';
    $budget    = strlen(trim($_POST['budget'] ?? '')) ? (float)$_POST['budget'] : null;
    $currency  = $_POST['currency'] ?? 'USD';
    $startDate = $_POST['start_date'] ?? '';
    $endDate   = $_POST['end_date'] ?? '';
    $notes     = trim($_POST['notes'] ?? '');

    if (!$campName) $campErrors[] = 'Campaign name is required.';
    if (!in_array($platform, ['google_ads','meta_ads','linkedin','manual','other'], true)) $campErrors[] = 'Invalid platform.';
    if (!in_array($campStatus, ['draft','active','paused','completed'], true)) $campErrors[] = 'Invalid status.';

    if (empty($campErrors)) {
        $db->prepare(
            'INSERT INTO campaigns (name, platform, status, budget, currency, start_date, end_date, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)'
        )->execute([$campName, $platform, $campStatus, $budget, $currency, $startDate ?: null, $endDate ?: null, $notes ?: null, $_SESSION['admin_id']]);
        flash('success', 'Campaign created.');
        header('Location: ' . BASE . '/campaigns.php');
        exit;
    }
    $campModalOpen = true;
}

include __DIR__ . '/includes/layout-start.php';
?>

<!-- Modal: New Campaign -->
<div class="modal-overlay hidden" id="camp-modal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">New Campaign</h2>
            <button class="modal-close" onclick="closeCampModal()">&times;</button>
        </div>
        <?php if (!empty($campErrors)): ?>
        <div class="alert alert-error" style="margin:16px 24px 0">
            <?php foreach ($campErrors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="create_campaign">
            <div class="modal-body">
                <div class="form-group">
                    <label>Campaign Name *</label>
                    <input type="text" name="camp_name" class="form-control" required autofocus value="<?= h($_POST['camp_name'] ?? '') ?>">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Platform</label>
                        <select name="platform" class="form-control">
                            <?php foreach (['google_ads'=>'Google Ads','meta_ads'=>'Meta Ads','linkedin'=>'LinkedIn','manual'=>'Manual','other'=>'Other'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($_POST['platform'] ?? 'manual') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="camp_status" class="form-control">
                            <?php foreach (['draft'=>'Draft','active'=>'Active','paused'=>'Paused','completed'=>'Completed'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($_POST['camp_status'] ?? 'draft') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Budget</label>
                        <input type="number" name="budget" class="form-control" min="0" step="0.01" placeholder="Leave blank if unknown" value="<?= h($_POST['budget'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Currency</label>
                        <select name="currency" class="form-control">
                            <option value="USD" <?= ($_POST['currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD</option>
                            <option value="INR" <?= ($_POST['currency'] ?? '') === 'INR' ? 'selected' : '' ?>>INR</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= h($_POST['start_date'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?= h($_POST['end_date'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?= h($_POST['notes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCampModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Campaign</button>
            </div>
        </form>
    </div>
</div>
<script>
function openCampModal()  { document.getElementById('camp-modal').classList.remove('hidden'); }
function closeCampModal() { document.getElementById('camp-modal').classList.add('hidden'); }
document.getElementById('camp-modal').addEventListener('click', function(e) { if (e.target === this) closeCampModal(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeCampModal(); });
<?php if ($campModalOpen): ?>document.addEventListener('DOMContentLoaded', openCampModal);<?php endif; ?>
</script>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <input type="text" name="search" class="form-control" placeholder="Campaign name…" value="<?= h($filterSearch) ?>">
        <select name="platform" class="form-control">
            <option value="">All Platforms</option>
            <?php foreach (['google_ads','meta_ads','linkedin','manual','other'] as $p): ?>
            <option value="<?= $p ?>" <?= $filterPlatform === $p ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $p)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <?php foreach (['draft','active','paused','completed'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="<?= BASE ?>/campaigns.php" class="btn btn-secondary">Clear</a>
    </form>
    <div style="display:flex;align-items:center;gap:12px">
        <div class="filter-count"><?= $totalRows ?> campaign<?= $totalRows !== 1 ? 's' : '' ?></div>
        <button class="btn btn-primary" onclick="openCampModal()">+ New Campaign</button>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr><th>Name</th><th>Platform</th><th>Status</th><th>Budget</th><th>Spend</th><th>Leads</th><th>Dates</th><th>CPL</th></tr>
        </thead>
        <tbody>
        <?php foreach ($campaigns as $c): ?>
        <tr>
            <td><a href="<?= BASE ?>/campaign-edit.php?id=<?= $c['id'] ?>" class="link"><?= h($c['name']) ?></a></td>
            <td><?= platform_badge($c['platform']) ?></td>
            <td><?= status_badge($c['status']) ?></td>
            <td class="td-secondary"><?= $c['budget'] ? format_currency((float)$c['budget'], $c['currency']) : '—' ?></td>
            <td class="td-secondary"><?= $c['total_spend'] > 0 ? format_currency((float)$c['total_spend'], $c['currency']) : '—' ?></td>
            <td class="td-secondary"><?= $c['lead_count'] ?></td>
            <td class="td-secondary">
                <?php if ($c['start_date']): ?>
                    <?= date('d M', strtotime($c['start_date'])) ?><?= $c['end_date'] ? ' — ' . date('d M Y', strtotime($c['end_date'])) : '+' ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
            <td class="td-secondary">
                <?php if ($c['lead_count'] > 0 && $c['total_spend'] > 0): ?>
                    <?= format_currency((float)$c['total_spend'] / $c['lead_count'], $c['currency']) ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($campaigns)): ?>
        <tr><td colspan="8" class="empty-row">No campaigns found.</td></tr>
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
