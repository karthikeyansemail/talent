<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'sales');
$pageTitle = 'Leads';

$db = db();

$filterStatus   = $_GET['status']   ?? '';
$filterSource   = $_GET['source']   ?? '';
$filterCampaign = $_GET['campaign'] ?? '';
$filterAssigned = $_GET['assigned'] ?? '';
$filterSearch   = trim($_GET['search'] ?? '');
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 25;

$where  = ['1=1'];
$params = [];

if ($filterStatus) {
    $where[]  = 'l.status = ?';
    $params[] = $filterStatus;
}
if ($filterSource) {
    $where[]  = 'l.source = ?';
    $params[] = $filterSource;
}
if ($filterCampaign) {
    $where[]  = 'l.campaign_id = ?';
    $params[] = (int)$filterCampaign;
}
if ($filterAssigned) {
    $where[]  = 'l.assigned_to = ?';
    $params[] = (int)$filterAssigned;
}
if ($filterSearch) {
    $where[]  = '(l.name LIKE ? OR l.email LIKE ? OR l.company LIKE ?)';
    $s = '%' . $filterSearch . '%';
    $params = array_merge($params, [$s, $s, $s]);
}

$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM leads l WHERE {$whereStr}");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$offset     = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT l.*, c.name as campaign_name, a.name as assigned_name
     FROM leads l
     LEFT JOIN campaigns c ON c.id = l.campaign_id
     LEFT JOIN admin_users a ON a.id = l.assigned_to
     WHERE {$whereStr}
     ORDER BY l.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$leads = $stmt->fetchAll();

// For filters
$campaignList = $db->query('SELECT id, name FROM campaigns ORDER BY name')->fetchAll();
$salesUsers   = $db->query("SELECT id, name FROM admin_users WHERE role IN ('admin','sales') AND is_active=1 ORDER BY name")->fetchAll();

$leadErrors    = [];
$leadModalOpen = false;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $_POST['bulk_action'] ?? '';

    // Create lead via modal
    if ($action === 'create_lead') {
        $lName      = trim($_POST['lead_name'] ?? '');
        $lEmail     = trim($_POST['lead_email'] ?? '');
        $lPhone     = trim($_POST['lead_phone'] ?? '');
        $lCompany   = trim($_POST['lead_company'] ?? '');
        $lSource    = $_POST['lead_source'] ?? 'manual';
        $lCampaign  = (int)($_POST['lead_campaign'] ?? 0) ?: null;
        $lAssigned  = (int)($_POST['lead_assigned'] ?? 0) ?: null;
        $lNotes     = trim($_POST['lead_notes'] ?? '');

        if (!$lName) $leadErrors[] = 'Name is required.';
        if ($lEmail && !filter_var($lEmail, FILTER_VALIDATE_EMAIL)) $leadErrors[] = 'Invalid email.';
        if (!in_array($lSource, ['google_ads','meta_ads','linkedin','website','referral','manual'], true)) $leadErrors[] = 'Invalid source.';

        if (empty($leadErrors)) {
            $db->prepare(
                'INSERT INTO leads (name, email, phone, company, source, campaign_id, assigned_to, notes, status) VALUES (?,?,?,?,?,?,?,?,?)'
            )->execute([$lName, $lEmail ?: null, $lPhone ?: null, $lCompany ?: null, $lSource, $lCampaign, $lAssigned, $lNotes ?: null, 'new']);
            flash('success', 'Lead added.');
            header('Location: ' . BASE . '/leads.php?' . http_build_query($_GET));
            exit;
        }
        $leadModalOpen = true;
    }

    // Bulk actions
    if (in_array($action, ['assign','change_status'], true)) {
        $ids = $_POST['lead_ids'] ?? [];
        if ($ids && is_array($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $idParams = array_map('intval', $ids);
            if ($action === 'assign' && !empty($_POST['assign_to'])) {
                $db->prepare("UPDATE leads SET assigned_to = ? WHERE id IN ({$placeholders})")
                   ->execute(array_merge([(int)$_POST['assign_to']], $idParams));
                flash('success', count($ids) . ' lead(s) assigned.');
            }
            if ($action === 'change_status' && !empty($_POST['new_status'])) {
                $db->prepare("UPDATE leads SET status = ? WHERE id IN ({$placeholders})")
                   ->execute(array_merge([$_POST['new_status']], $idParams));
                flash('success', count($ids) . ' lead(s) updated.');
            }
        }
        header('Location: ' . BASE . '/leads.php?' . http_build_query($_GET));
        exit;
    }
}

include __DIR__ . '/includes/layout-start.php';
?>

<!-- Modal: New Lead -->
<div class="modal-overlay hidden" id="lead-modal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h2 class="modal-title">New Lead</h2>
            <button class="modal-close" onclick="closeLeadModal()">&times;</button>
        </div>
        <?php if (!empty($leadErrors)): ?>
        <div class="alert alert-error" style="margin:16px 24px 0">
            <?php foreach ($leadErrors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="create_lead">
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="lead_name" class="form-control" required autofocus value="<?= h($_POST['lead_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="lead_email" class="form-control" value="<?= h($_POST['lead_email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="lead_phone" class="form-control" value="<?= h($_POST['lead_phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="lead_company" class="form-control" value="<?= h($_POST['lead_company'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Source</label>
                        <select name="lead_source" class="form-control">
                            <?php foreach (['manual'=>'Manual','website'=>'Website','referral'=>'Referral','google_ads'=>'Google Ads','meta_ads'=>'Meta Ads','linkedin'=>'LinkedIn'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($_POST['lead_source'] ?? 'manual') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Campaign</label>
                        <select name="lead_campaign" class="form-control">
                            <option value="">None</option>
                            <?php foreach ($campaignList as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($_POST['lead_campaign'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label>Assign To</label>
                        <select name="lead_assigned" class="form-control">
                            <option value="">Unassigned</option>
                            <?php foreach ($salesUsers as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($_POST['lead_assigned'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= h($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label>Notes</label>
                        <textarea name="lead_notes" class="form-control" rows="2"><?= h($_POST['lead_notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeLeadModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Lead</button>
            </div>
        </form>
    </div>
</div>
<script>
function openLeadModal()  { document.getElementById('lead-modal').classList.remove('hidden'); }
function closeLeadModal() { document.getElementById('lead-modal').classList.add('hidden'); }
document.getElementById('lead-modal').addEventListener('click', function(e) { if (e.target === this) closeLeadModal(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeLeadModal(); });
<?php if ($leadModalOpen): ?>document.addEventListener('DOMContentLoaded', openLeadModal);<?php endif; ?>
</script>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <input type="text" name="search" class="form-control" placeholder="Name, email, company…" value="<?= h($filterSearch) ?>">
        <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <?php foreach (['new','contacted','qualified','proposal','negotiation','won','lost'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="source" class="form-control">
            <option value="">All Sources</option>
            <?php foreach (['google_ads','meta_ads','linkedin','website','referral','manual'] as $src): ?>
            <option value="<?= $src ?>" <?= $filterSource === $src ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $src)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="assigned" class="form-control">
            <option value="">All Assignees</option>
            <?php foreach ($salesUsers as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $filterAssigned == $u['id'] ? 'selected' : '' ?>><?= h($u['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="<?= BASE ?>/leads.php" class="btn btn-secondary">Clear</a>
    </form>
    <div style="display:flex;align-items:center;gap:12px">
        <div class="filter-count"><?= $totalRows ?> lead<?= $totalRows !== 1 ? 's' : '' ?></div>
        <button class="btn btn-primary" onclick="openLeadModal()">+ New Lead</button>
    </div>
</div>

<form method="POST" id="bulkForm">
<div class="card">
    <!-- Bulk actions bar -->
    <div style="padding:8px 16px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border);font-size:13px">
        <select name="bulk_action" class="form-control form-control-xs" style="width:140px">
            <option value="">Bulk Action</option>
            <option value="assign">Assign To</option>
            <option value="change_status">Change Status</option>
        </select>
        <select name="assign_to" class="form-control form-control-xs" style="width:140px">
            <option value="">Select user…</option>
            <?php foreach ($salesUsers as $u): ?>
            <option value="<?= $u['id'] ?>"><?= h($u['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="new_status" class="form-control form-control-xs" style="width:140px">
            <option value="">Select status…</option>
            <?php foreach (['new','contacted','qualified','proposal','negotiation','won','lost'] as $s): ?>
            <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Apply</button>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width:30px"><input type="checkbox" id="selectAll"></th>
                <th>Name</th><th>Company</th><th>Source</th><th>Campaign</th><th>Status</th><th>Assigned To</th><th>Created</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($leads as $l): ?>
        <tr>
            <td><input type="checkbox" name="lead_ids[]" value="<?= $l['id'] ?>"></td>
            <td>
                <a href="<?= BASE ?>/lead-view.php?id=<?= $l['id'] ?>" class="link"><?= h($l['name']) ?></a>
                <?php if ($l['email']): ?><div class="td-secondary"><?= h($l['email']) ?></div><?php endif; ?>
            </td>
            <td class="td-secondary"><?= h($l['company'] ?? '—') ?></td>
            <td><?= platform_badge($l['source']) ?></td>
            <td class="td-secondary"><?= h($l['campaign_name'] ?? '—') ?></td>
            <td><?= lead_status_badge($l['status']) ?></td>
            <td class="td-secondary"><?= h($l['assigned_name'] ?? 'Unassigned') ?></td>
            <td class="td-secondary"><?= time_ago($l['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($leads)): ?>
        <tr><td colspan="8" class="empty-row">No leads found.</td></tr>
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
</form>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="lead_ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
