<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'sales');
$pageTitle = 'Appointments';

$db = db();

$filterStatus   = $_GET['status']   ?? '';
$filterType     = $_GET['type']     ?? '';
$filterAssigned = $_GET['assigned'] ?? '';
$filterSearch   = trim($_GET['search'] ?? '');
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 20;

$where  = ['1=1'];
$params = [];

if ($filterStatus) {
    $where[]  = 'ap.status = ?';
    $params[] = $filterStatus;
}
if ($filterType) {
    $where[]  = 'ap.type = ?';
    $params[] = $filterType;
}
if ($filterAssigned) {
    $where[]  = 'ap.assigned_to = ?';
    $params[] = (int)$filterAssigned;
}
if ($filterSearch) {
    $where[]  = '(l.name LIKE ? OR l.company LIKE ?)';
    $s = '%' . $filterSearch . '%';
    $params = array_merge($params, [$s, $s]);
}

$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM appointments ap JOIN leads l ON l.id=ap.lead_id WHERE {$whereStr}");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$offset     = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT ap.*, l.name as lead_name, l.company as lead_company, u.name as assigned_name
     FROM appointments ap
     JOIN leads l ON l.id = ap.lead_id
     LEFT JOIN admin_users u ON u.id = ap.assigned_to
     WHERE {$whereStr}
     ORDER BY ap.scheduled_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$salesUsers = $db->query("SELECT id, name FROM admin_users WHERE role IN ('admin','sales') AND is_active=1 ORDER BY name")->fetchAll();
$leadsList  = $db->query("SELECT id, name, company FROM leads WHERE status NOT IN ('won','lost') ORDER BY name")->fetchAll();

$apptErrors    = [];
$apptModalOpen = false;
$preLeadId     = (int)($_GET['lead_id'] ?? 0);

// Handle create appointment via modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_appointment') {
    $leadId      = (int)($_POST['lead_id'] ?? 0);
    $type        = $_POST['appt_type'] ?? 'discovery';
    $scheduledAt = trim($_POST['scheduled_at'] ?? '');
    $durationMin = (int)($_POST['duration_min'] ?? 30);
    $assignedTo  = (int)($_POST['assigned_to'] ?? 0) ?: null;
    $notes       = trim($_POST['notes'] ?? '');

    if (!$leadId)      $apptErrors[] = 'Lead is required.';
    if (!$scheduledAt) $apptErrors[] = 'Date & time is required.';
    if (!in_array($type, ['discovery','demo','follow_up','closing'], true)) $apptErrors[] = 'Invalid type.';

    if (empty($apptErrors)) {
        $db->prepare(
            'INSERT INTO appointments (lead_id, type, scheduled_at, duration_min, assigned_to, notes, status) VALUES (?,?,?,?,?,?,?)'
        )->execute([$leadId, $type, $scheduledAt, $durationMin, $assignedTo, $notes ?: null, 'scheduled']);
        flash('success', 'Appointment scheduled.');
        header('Location: ' . BASE . '/appointments.php?' . http_build_query($_GET));
        exit;
    }
    $apptModalOpen = true;
}

// Handle quick status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_status') {
    $apId      = (int)$_POST['appointment_id'];
    $newStatus = $_POST['new_status'];
    $allowed   = ['scheduled','completed','cancelled','no_show'];
    if (in_array($newStatus, $allowed, true)) {
        $db->prepare('UPDATE appointments SET status = ? WHERE id = ?')->execute([$newStatus, $apId]);
        flash('success', 'Appointment status updated.');
    }
    header('Location: ' . BASE . '/appointments.php?' . http_build_query($_GET));
    exit;
}

include __DIR__ . '/includes/layout-start.php';
?>

<!-- Modal: New Appointment -->
<div class="modal-overlay hidden" id="appt-modal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">New Appointment</h2>
            <button class="modal-close" onclick="closeApptModal()">&times;</button>
        </div>
        <?php if (!empty($apptErrors)): ?>
        <div class="alert alert-error" style="margin:16px 24px 0">
            <?php foreach ($apptErrors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="create_appointment">
            <div class="modal-body">
                <div class="form-group">
                    <label>Lead *</label>
                    <select name="lead_id" class="form-control" required>
                        <option value="">Select lead…</option>
                        <?php foreach ($leadsList as $l): ?>
                        <option value="<?= $l['id'] ?>"
                            <?= ($preLeadId && $preLeadId == $l['id']) || ($_POST['lead_id'] ?? '') == $l['id'] ? 'selected' : '' ?>>
                            <?= h($l['name']) ?><?= $l['company'] ? ' — ' . h($l['company']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Type</label>
                        <select name="appt_type" class="form-control">
                            <?php foreach (['discovery'=>'Discovery','demo'=>'Demo','follow_up'=>'Follow Up','closing'=>'Closing'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($_POST['appt_type'] ?? 'discovery') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Duration (min)</label>
                        <input type="number" name="duration_min" class="form-control" min="5" step="5" value="<?= h($_POST['duration_min'] ?? '30') ?>">
                    </div>
                    <div class="form-group">
                        <label>Date & Time *</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" required value="<?= h($_POST['scheduled_at'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Assign To</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">Unassigned</option>
                            <?php foreach ($salesUsers as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($_POST['assigned_to'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= h($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?= h($_POST['notes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeApptModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Schedule Appointment</button>
            </div>
        </form>
    </div>
</div>
<script>
function openApptModal()  { document.getElementById('appt-modal').classList.remove('hidden'); }
function closeApptModal() { document.getElementById('appt-modal').classList.add('hidden'); }
document.getElementById('appt-modal').addEventListener('click', function(e) { if (e.target === this) closeApptModal(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeApptModal(); });
<?php if ($apptModalOpen || $preLeadId): ?>document.addEventListener('DOMContentLoaded', openApptModal);<?php endif; ?>
</script>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <input type="text" name="search" class="form-control" placeholder="Lead name, company…" value="<?= h($filterSearch) ?>">
        <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <?php foreach (['scheduled','completed','cancelled','no_show'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="form-control">
            <option value="">All Types</option>
            <?php foreach (['discovery','demo','follow_up','closing'] as $t): ?>
            <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $t)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="assigned" class="form-control">
            <option value="">All Assignees</option>
            <?php foreach ($salesUsers as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $filterAssigned == $u['id'] ? 'selected' : '' ?>><?= h($u['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="<?= BASE ?>/appointments.php" class="btn btn-secondary">Clear</a>
    </form>
    <div style="display:flex;align-items:center;gap:12px">
        <div class="filter-count"><?= $totalRows ?> appointment<?= $totalRows !== 1 ? 's' : '' ?></div>
        <button class="btn btn-primary" onclick="openApptModal()">+ New Appointment</button>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr><th>Lead</th><th>Type</th><th>Scheduled</th><th>Duration</th><th>Assigned To</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($appointments as $ap): ?>
        <tr>
            <td>
                <a href="<?= BASE ?>/lead-view.php?id=<?= $ap['lead_id'] ?>" class="link"><?= h($ap['lead_name']) ?></a>
                <?php if ($ap['lead_company']): ?><div class="td-secondary"><?= h($ap['lead_company']) ?></div><?php endif; ?>
            </td>
            <td><?= status_badge($ap['type'] === 'follow_up' ? 'in_progress' : ($ap['type'] === 'closing' ? 'active' : ($ap['type'] === 'demo' ? 'open' : 'draft'))) ?> <span style="font-size:12px"><?= ucfirst(str_replace('_', ' ', $ap['type'])) ?></span></td>
            <td>
                <div class="td-primary"><?= date('d M Y', strtotime($ap['scheduled_at'])) ?></div>
                <div class="td-secondary"><?= date('H:i', strtotime($ap['scheduled_at'])) ?></div>
            </td>
            <td class="td-secondary"><?= $ap['duration_min'] ?> min</td>
            <td class="td-secondary"><?= h($ap['assigned_name'] ?? 'Unassigned') ?></td>
            <td><?= status_badge($ap['status']) ?></td>
            <td>
                <div style="display:flex;gap:4px">
                    <a href="<?= BASE ?>/appointment-edit.php?id=<?= $ap['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="change_status">
                        <input type="hidden" name="appointment_id" value="<?= $ap['id'] ?>">
                        <select name="new_status" class="form-control form-control-xs" onchange="this.form.submit()" style="width:auto">
                            <?php foreach (['scheduled','completed','cancelled','no_show'] as $s): ?>
                            <option value="<?= $s ?>" <?= $ap['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($appointments)): ?>
        <tr><td colspan="7" class="empty-row">No appointments found.</td></tr>
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
