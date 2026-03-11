<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'sales');

$db = db();
$id = (int)($_GET['id'] ?? 0);
$appointment = null;
$errors = [];

// Pre-select lead if coming from lead-view
$preLeadId = (int)($_GET['lead_id'] ?? 0);

if ($id) {
    $stmt = $db->prepare('SELECT * FROM appointments WHERE id = ?');
    $stmt->execute([$id]);
    $appointment = $stmt->fetch();
    if (!$appointment) {
        flash('error', 'Appointment not found.');
        header('Location: ' . BASE . '/appointments.php');
        exit;
    }
}

$pageTitle = $id ? 'Edit Appointment' : 'New Appointment';

// Fetch leads and sales users
$leadList   = $db->query("SELECT id, name, company FROM leads WHERE status NOT IN ('won','lost') ORDER BY name")->fetchAll();
$allLeads   = $db->query("SELECT id, name, company FROM leads ORDER BY name")->fetchAll();
$salesUsers = $db->query("SELECT id, name FROM admin_users WHERE role IN ('admin','sales') AND is_active=1 ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leadId     = (int)($_POST['lead_id'] ?? 0);
    $assignedTo = (int)($_POST['assigned_to'] ?? 0) ?: null;
    $scheduledAt = $_POST['scheduled_at'] ?? '';
    $durationMin = (int)($_POST['duration_min'] ?? 30);
    $type       = $_POST['type'] ?? 'discovery';
    $status     = $_POST['status'] ?? 'scheduled';
    $notes      = trim($_POST['notes'] ?? '');
    $outcome    = trim($_POST['outcome'] ?? '');

    if (!$leadId) $errors[] = 'Lead is required.';
    if (!$scheduledAt) $errors[] = 'Scheduled date/time is required.';
    if (!in_array($type, ['discovery','demo','follow_up','closing'], true)) $errors[] = 'Invalid type.';
    if (!in_array($status, ['scheduled','completed','cancelled','no_show'], true)) $errors[] = 'Invalid status.';

    if (empty($errors)) {
        if ($id) {
            $oldStatus = $appointment['status'] ?? 'scheduled';
            $db->prepare(
                'UPDATE appointments SET lead_id=?, assigned_to=?, scheduled_at=?, duration_min=?, type=?, status=?, notes=?, outcome=? WHERE id=?'
            )->execute([$leadId, $assignedTo, $scheduledAt, $durationMin, $type, $status, $notes, $outcome, $id]);

            // ── Workflow: appointment completed → advance lead pipeline stage ──
            if ($status === 'completed' && $oldStatus !== 'completed') {
                $leadRow = $db->prepare('SELECT id, status FROM leads WHERE id = ?');
                $leadRow->execute([$leadId]);
                $leadRow = $leadRow->fetch();
                if ($leadRow) {
                    // Determine next stage based on appointment type
                    $nextStatus = null;
                    if (in_array($leadRow['status'], ['new', 'contacted'], true)) {
                        $nextStatus = ($type === 'demo') ? 'proposal' : 'qualified';
                    } elseif ($leadRow['status'] === 'qualified' && in_array($type, ['demo', 'follow_up'], true)) {
                        $nextStatus = 'proposal';
                    } elseif ($leadRow['status'] === 'proposal' && $type === 'closing') {
                        $nextStatus = 'negotiation';
                    }
                    if ($nextStatus) {
                        $db->prepare('UPDATE leads SET status = ? WHERE id = ?')->execute([$nextStatus, $leadId]);
                        $actDesc = "Appointment completed ({$type}) — lead advanced to " . ucfirst($nextStatus);
                        if ($outcome) $actDesc .= ". Outcome: {$outcome}";
                        $db->prepare('INSERT INTO lead_activities (lead_id, user_id, type, description) VALUES (?,?,?,?)')
                           ->execute([$leadId, $_SESSION['admin_id'], 'meeting', $actDesc]);
                    }
                }
            }

            // ── Workflow: no_show → log activity on lead ──
            if ($status === 'no_show' && $oldStatus !== 'no_show') {
                $db->prepare('INSERT INTO lead_activities (lead_id, user_id, type, description) VALUES (?,?,?,?)')
                   ->execute([$leadId, $_SESSION['admin_id'], 'note',
                     "Appointment no-show ({$type}) on " . date('d M Y', strtotime($scheduledAt))]);
            }

            flash('success', 'Appointment updated.');
        } else {
            $db->prepare(
                'INSERT INTO appointments (lead_id, assigned_to, scheduled_at, duration_min, type, status, notes) VALUES (?,?,?,?,?,?,?)'
            )->execute([$leadId, $assignedTo, $scheduledAt, $durationMin, $type, $status, $notes]);
            // Log activity on lead
            $db->prepare('INSERT INTO lead_activities (lead_id, user_id, type, description) VALUES (?,?,?,?)')
               ->execute([$leadId, $_SESSION['admin_id'], 'meeting',
                 ucfirst($type) . ' appointment scheduled for ' . date('d M Y H:i', strtotime($scheduledAt))]);
            flash('success', 'Appointment scheduled.');
        }
        // Return to lead view if came from there, otherwise appointments list
        $returnTo = $preLeadId ? BASE . '/lead-view.php?id=' . $preLeadId : BASE . '/appointments.php';
        if ($id) {
            $appt = $db->prepare('SELECT lead_id FROM appointments WHERE id = ?');
            $appt->execute([$id]);
            $appt = $appt->fetch();
            $returnTo = $appt ? BASE . '/lead-view.php?id=' . $appt['lead_id'] : BASE . '/appointments.php';
        }
        header('Location: ' . $returnTo);
        exit;
    }

    $appointment = [
        'id' => $id, 'lead_id' => $leadId, 'assigned_to' => $assignedTo,
        'scheduled_at' => $scheduledAt, 'duration_min' => $durationMin,
        'type' => $type, 'status' => $status, 'notes' => $notes, 'outcome' => $outcome,
    ];
}

include __DIR__ . '/includes/layout-start.php';
?>

<div style="margin-bottom:16px">
    <a href="<?= BASE ?>/appointments.php" style="color:var(--primary);font-size:13px;text-decoration:none">← Back to Appointments</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $err): ?><div><?= h($err) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:560px">
    <div class="card-header"><span><?= $id ? 'Edit Appointment' : 'Schedule Appointment' ?></span></div>
    <div class="card-body" style="padding:24px">
        <form method="POST">
            <div class="form-group">
                <label>Lead *</label>
                <select name="lead_id" class="form-control" required>
                    <option value="">Select lead…</option>
                    <?php foreach ($id ? $allLeads : $leadList as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= ($appointment['lead_id'] ?? $preLeadId) == $l['id'] ? 'selected' : '' ?>>
                        <?= h($l['name']) ?><?= $l['company'] ? ' — ' . h($l['company']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:12px">
                <div class="form-group" style="flex:2">
                    <label>Scheduled Date & Time *</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control" value="<?= h($appointment['scheduled_at'] ?? '') ?>" required>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Duration (min)</label>
                    <input type="number" name="duration_min" class="form-control" min="5" step="5" value="<?= h($appointment['duration_min'] ?? 30) ?>">
                </div>
            </div>
            <div style="display:flex;gap:12px">
                <div class="form-group" style="flex:1">
                    <label>Type</label>
                    <select name="type" class="form-control">
                        <?php foreach (['discovery'=>'Discovery Call','demo'=>'Demo','follow_up'=>'Follow Up','closing'=>'Closing'] as $tVal => $tLabel): ?>
                        <option value="<?= $tVal ?>" <?= ($appointment['type'] ?? 'discovery') === $tVal ? 'selected' : '' ?>><?= $tLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Assigned To</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">Unassigned</option>
                        <?php foreach ($salesUsers as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($appointment['assigned_to'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= h($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php if ($id): ?>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <?php foreach (['scheduled'=>'Scheduled','completed'=>'Completed','cancelled'=>'Cancelled','no_show'=>'No Show'] as $sVal => $sLabel): ?>
                    <option value="<?= $sVal ?>" <?= ($appointment['status'] ?? 'scheduled') === $sVal ? 'selected' : '' ?>><?= $sLabel ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Preparation notes, agenda…"><?= h($appointment['notes'] ?? '') ?></textarea>
            </div>
            <?php if ($id): ?>
            <div class="form-group">
                <label>Outcome</label>
                <textarea name="outcome" class="form-control" rows="3" placeholder="Meeting outcome, next steps…"><?= h($appointment['outcome'] ?? '') ?></textarea>
            </div>
            <?php endif; ?>
            <div style="display:flex;gap:8px;margin-top:20px">
                <button type="submit" class="btn btn-primary"><?= $id ? 'Update Appointment' : 'Schedule Appointment' ?></button>
                <a href="<?= BASE ?>/appointments.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
