<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'sales');

$db  = db();
$id  = (int)($_GET['id'] ?? 0);
$isNew = isset($_GET['new']);
$lead = null;
$activities = [];
$appointments = [];
$errors = [];

// Sales users for assignment
$salesUsers = $db->query("SELECT id, name FROM admin_users WHERE role IN ('admin','sales') AND is_active=1 ORDER BY name")->fetchAll();
$campaignList = $db->query('SELECT id, name FROM campaigns ORDER BY name')->fetchAll();

if ($id) {
    $stmt = $db->prepare(
        'SELECT l.*, c.name as campaign_name, a.name as assigned_name, cu.name as customer_name
         FROM leads l
         LEFT JOIN campaigns c ON c.id = l.campaign_id
         LEFT JOIN admin_users a ON a.id = l.assigned_to
         LEFT JOIN customers cu ON cu.id = l.customer_id
         WHERE l.id = ?'
    );
    $stmt->execute([$id]);
    $lead = $stmt->fetch();
    if (!$lead) {
        flash('error', 'Lead not found.');
        header('Location: ' . BASE . '/leads.php');
        exit;
    }

    // Activities
    $actStmt = $db->prepare(
        'SELECT la.*, u.name as user_name FROM lead_activities la
         LEFT JOIN admin_users u ON u.id = la.user_id
         WHERE la.lead_id = ? ORDER BY la.created_at DESC'
    );
    $actStmt->execute([$id]);
    $activities = $actStmt->fetchAll();

    // Appointments
    $aptStmt = $db->prepare(
        'SELECT ap.*, u.name as assigned_name FROM appointments ap
         LEFT JOIN admin_users u ON u.id = ap.assigned_to
         WHERE ap.lead_id = ? ORDER BY ap.scheduled_at DESC'
    );
    $aptStmt->execute([$id]);
    $appointments = $aptStmt->fetchAll();
}

$pageTitle = $id ? h($lead['name']) : 'New Lead';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_lead';

    if ($action === 'save_lead') {
        $name      = trim($_POST['name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $company   = trim($_POST['company'] ?? '');
        $source    = $_POST['source'] ?? 'manual';
        $campaignId = (int)($_POST['campaign_id'] ?? 0) ?: null;
        $status    = $_POST['status'] ?? 'new';
        $assignedTo = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $notes     = trim($_POST['notes'] ?? '');

        if (!$name) $errors[] = 'Name is required.';

        if (empty($errors)) {
            if ($id) {
                $oldStatus = $lead['status'];
                $db->prepare(
                    'UPDATE leads SET name=?, email=?, phone=?, company=?, source=?, campaign_id=?, status=?, assigned_to=?, notes=? WHERE id=?'
                )->execute([$name, $email, $phone, $company, $source, $campaignId, $status, $assignedTo, $notes, $id]);

                // Log status change
                if ($oldStatus !== $status) {
                    $db->prepare('INSERT INTO lead_activities (lead_id, user_id, type, description) VALUES (?,?,?,?)')
                       ->execute([$id, $_SESSION['admin_id'], 'status_change', "Status changed from {$oldStatus} to {$status}"]);
                }
                flash('success', 'Lead updated.');
            } else {
                $db->prepare(
                    'INSERT INTO leads (name, email, phone, company, source, campaign_id, status, assigned_to, notes) VALUES (?,?,?,?,?,?,?,?,?)'
                )->execute([$name, $email, $phone, $company, $source, $campaignId, $status, $assignedTo, $notes]);
                $id = (int)$db->lastInsertId();
                flash('success', 'Lead created.');
            }
            header('Location: ' . BASE . '/lead-view.php?id=' . $id);
            exit;
        }
        $lead = [
            'id' => $id, 'name' => $name, 'email' => $email, 'phone' => $phone,
            'company' => $company, 'source' => $source, 'campaign_id' => $campaignId,
            'status' => $status, 'assigned_to' => $assignedTo, 'notes' => $notes,
        ];
    }

    if ($action === 'add_activity' && $id) {
        $type = $_POST['activity_type'] ?? 'note';
        $desc = trim($_POST['description'] ?? '');
        if ($desc) {
            $db->prepare('INSERT INTO lead_activities (lead_id, user_id, type, description) VALUES (?,?,?,?)')
               ->execute([$id, $_SESSION['admin_id'], $type, $desc]);
            flash('success', 'Activity logged.');
        }
        header('Location: ' . BASE . '/lead-view.php?id=' . $id);
        exit;
    }

    if ($action === 'change_status' && $id) {
        $newStatus = $_POST['new_status'] ?? '';
        $allowed = ['new','contacted','qualified','proposal','negotiation','won','lost'];
        if (in_array($newStatus, $allowed, true)) {
            $oldStatus = $lead['status'];
            $db->prepare('UPDATE leads SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
            if ($oldStatus !== $newStatus) {
                $db->prepare('INSERT INTO lead_activities (lead_id, user_id, type, description) VALUES (?,?,?,?)')
                   ->execute([$id, $_SESSION['admin_id'], 'status_change', "Status changed from {$oldStatus} to {$newStatus}"]);
            }
            if ($newStatus === 'won') {
                $db->prepare('UPDATE leads SET converted_at = NOW() WHERE id = ? AND converted_at IS NULL')->execute([$id]);
            }
            flash('success', 'Status updated.');
        }
        header('Location: ' . BASE . '/lead-view.php?id=' . $id);
        exit;
    }

    if ($action === 'convert_to_customer' && $id) {
        // Create customer from lead
        $db->prepare(
            'INSERT INTO customers (name, email, company, created_at) VALUES (?, ?, ?, NOW())'
        )->execute([$lead['name'], $lead['email'], $lead['company']]);
        $customerId = (int)$db->lastInsertId();
        $db->prepare('UPDATE leads SET customer_id = ?, status = ?, converted_at = NOW() WHERE id = ?')
           ->execute([$customerId, 'won', $id]);
        $db->prepare('INSERT INTO lead_activities (lead_id, user_id, type, description) VALUES (?,?,?,?)')
           ->execute([$id, $_SESSION['admin_id'], 'status_change', 'Converted to customer #' . $customerId]);
        flash('success', 'Lead converted to customer! Now create their order/subscription.');
        // ── Workflow: redirect to order creation pre-filled with this customer ──
        $plan = $_POST['plan'] ?? 'cloud_enterprise';
        header('Location: ' . BASE . '/order-new.php?customer_id=' . $customerId
             . '&lead_id=' . $id . '&plan=' . urlencode($plan));
        exit;
    }
}

include __DIR__ . '/includes/layout-start.php';
?>

<div style="margin-bottom:16px;display:flex;align-items:center;gap:12px">
    <a href="<?= BASE ?>/leads.php" style="color:var(--primary);font-size:13px;text-decoration:none">← Back to Leads</a>
    <?php if ($id): ?>
        <?= lead_status_badge($lead['status']) ?>
        <?php if ($lead['customer_id']): ?>
            <span style="color:var(--success);font-size:12px;font-weight:600">Converted</span>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $err): ?><div><?= h($err) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($isNew || !$id): ?>
<!-- Create form -->
<div class="card" style="max-width:560px">
    <div class="card-header"><span>Create Lead</span></div>
    <div class="card-body" style="padding:24px">
        <form method="POST">
            <input type="hidden" name="action" value="save_lead">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" class="form-control" value="<?= h($lead['name'] ?? '') ?>" required>
            </div>
            <div style="display:flex;gap:12px">
                <div class="form-group" style="flex:1">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= h($lead['email'] ?? '') ?>">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= h($lead['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Company</label>
                <input type="text" name="company" class="form-control" value="<?= h($lead['company'] ?? '') ?>">
            </div>
            <div style="display:flex;gap:12px">
                <div class="form-group" style="flex:1">
                    <label>Source</label>
                    <select name="source" class="form-control">
                        <?php foreach (['manual'=>'Manual','google_ads'=>'Google Ads','meta_ads'=>'Meta Ads','linkedin'=>'LinkedIn','website'=>'Website','referral'=>'Referral'] as $sVal => $sLabel): ?>
                        <option value="<?= $sVal ?>" <?= ($lead['source'] ?? 'manual') === $sVal ? 'selected' : '' ?>><?= $sLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Campaign</label>
                    <select name="campaign_id" class="form-control">
                        <option value="">None</option>
                        <?php foreach ($campaignList as $camp): ?>
                        <option value="<?= $camp['id'] ?>" <?= ($lead['campaign_id'] ?? '') == $camp['id'] ? 'selected' : '' ?>><?= h($camp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Assigned To</label>
                <select name="assigned_to" class="form-control">
                    <option value="">Unassigned</option>
                    <?php foreach ($salesUsers as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($lead['assigned_to'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= h($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?= h($lead['notes'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:8px;margin-top:20px">
                <button type="submit" class="btn btn-primary">Create Lead</button>
                <a href="<?= BASE ?>/leads.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- View/edit existing lead -->
<div class="two-col" style="align-items:flex-start">
    <!-- Main content -->
    <div style="flex:2;display:flex;flex-direction:column;gap:16px">
        <!-- Lead detail card -->
        <div class="card">
            <div class="card-header">
                <span>Lead Details</span>
                <?php if (!$lead['customer_id'] && $lead['status'] !== 'lost'): ?>
                <form method="POST" style="display:inline;display:flex;align-items:center;gap:6px">
                    <input type="hidden" name="action" value="convert_to_customer">
                    <select name="plan" class="form-control form-control-xs" style="width:auto">
                        <option value="cloud_enterprise">Cloud Enterprise</option>
                        <option value="self_hosted">Self-Hosted</option>
                        <option value="free">Free</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Convert this lead to a customer and create an order?')">Convert &amp; Create Order</button>
                </form>
                <?php elseif ($lead['customer_id']): ?>
                <a href="<?= BASE ?>/order-new.php?customer_id=<?= $lead['customer_id'] ?>&lead_id=<?= $id ?>" class="btn btn-secondary btn-sm">+ New Order</a>
                <?php endif; ?>
            </div>
            <div style="padding:20px">
                <form method="POST">
                    <input type="hidden" name="action" value="save_lead">
                    <div style="display:flex;gap:12px">
                        <div class="form-group" style="flex:1">
                            <label>Name *</label>
                            <input type="text" name="name" class="form-control" value="<?= h($lead['name']) ?>" required>
                        </div>
                        <div class="form-group" style="flex:1">
                            <label>Company</label>
                            <input type="text" name="company" class="form-control" value="<?= h($lead['company'] ?? '') ?>">
                        </div>
                    </div>
                    <div style="display:flex;gap:12px">
                        <div class="form-group" style="flex:1">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?= h($lead['email'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="flex:1">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= h($lead['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div style="display:flex;gap:12px">
                        <div class="form-group" style="flex:1">
                            <label>Source</label>
                            <select name="source" class="form-control">
                                <?php foreach (['manual'=>'Manual','google_ads'=>'Google Ads','meta_ads'=>'Meta Ads','linkedin'=>'LinkedIn','website'=>'Website','referral'=>'Referral'] as $sVal => $sLabel): ?>
                                <option value="<?= $sVal ?>" <?= $lead['source'] === $sVal ? 'selected' : '' ?>><?= $sLabel ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex:1">
                            <label>Campaign</label>
                            <select name="campaign_id" class="form-control">
                                <option value="">None</option>
                                <?php foreach ($campaignList as $camp): ?>
                                <option value="<?= $camp['id'] ?>" <?= $lead['campaign_id'] == $camp['id'] ? 'selected' : '' ?>><?= h($camp['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="display:flex;gap:12px">
                        <div class="form-group" style="flex:1">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <?php foreach (['new','contacted','qualified','proposal','negotiation','won','lost'] as $s): ?>
                                <option value="<?= $s ?>" <?= $lead['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex:1">
                            <label>Assigned To</label>
                            <select name="assigned_to" class="form-control">
                                <option value="">Unassigned</option>
                                <?php foreach ($salesUsers as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $lead['assigned_to'] == $u['id'] ? 'selected' : '' ?>><?= h($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3"><?= h($lead['notes'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Lead</button>
                </form>
            </div>
        </div>

        <!-- Activity log + add activity -->
        <div class="card">
            <div class="card-header"><span>Activity Timeline</span></div>
            <div style="padding:16px">
                <form method="POST" style="margin-bottom:16px">
                    <input type="hidden" name="action" value="add_activity">
                    <div style="display:flex;gap:8px;align-items:flex-end">
                        <div class="form-group" style="width:130px">
                            <select name="activity_type" class="form-control">
                                <option value="note">Note</option>
                                <option value="call">Call</option>
                                <option value="email">Email</option>
                                <option value="meeting">Meeting</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex:1">
                            <input type="text" name="description" class="form-control" placeholder="Add activity note…" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="height:36px">Add</button>
                    </div>
                </form>

                <ul class="activity-timeline">
                <?php foreach ($activities as $act): ?>
                    <li class="activity-item">
                        <span class="activity-type"><?= h($act['type']) ?></span>
                        — <?= h($act['description']) ?>
                        <div class="activity-time"><?= h($act['user_name'] ?? 'System') ?> · <?= time_ago($act['created_at']) ?></div>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($activities)): ?>
                    <li class="activity-item" style="color:var(--gray-400)">No activities yet.</li>
                <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Side panel -->
    <div style="display:flex;flex-direction:column;gap:12px">
        <!-- Quick status -->
        <div class="card">
            <div class="card-header"><span>Pipeline Stage</span></div>
            <div style="padding:16px">
                <form method="POST">
                    <input type="hidden" name="action" value="change_status">
                    <div class="form-group">
                        <select name="new_status" class="form-control">
                            <?php foreach (['new','contacted','qualified','proposal','negotiation','won','lost'] as $s): ?>
                            <option value="<?= $s ?>" <?= $lead['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary" style="width:100%">Update Stage</button>
                </form>
            </div>
        </div>

        <!-- Info -->
        <div class="card">
            <div class="card-header"><span>Info</span></div>
            <div style="padding:16px;font-size:13px;color:var(--gray-600);display:flex;flex-direction:column;gap:6px">
                <div>Source: <?= platform_badge($lead['source']) ?></div>
                <?php if ($lead['campaign_name']): ?><div>Campaign: <?= h($lead['campaign_name']) ?></div><?php endif; ?>
                <div>Assigned: <?= h($lead['assigned_name'] ?? 'Unassigned') ?></div>
                <div>Created: <?= date('d M Y H:i', strtotime($lead['created_at'])) ?></div>
                <div>Updated: <?= date('d M Y H:i', strtotime($lead['updated_at'])) ?></div>
                <?php if ($lead['converted_at']): ?>
                <div>Converted: <?= date('d M Y H:i', strtotime($lead['converted_at'])) ?></div>
                <?php endif; ?>
                <?php if ($lead['customer_name']): ?>
                <div>Customer: <?= h($lead['customer_name']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Appointments -->
        <div class="card">
            <div class="card-header">
                <span>Appointments</span>
                <a href="<?= BASE ?>/appointment-edit.php?lead_id=<?= $id ?>" class="btn btn-primary btn-sm">Schedule</a>
            </div>
            <div style="padding:12px;font-size:13px">
                <?php if ($appointments): ?>
                    <?php foreach ($appointments as $apt): ?>
                    <div style="padding:6px 0;border-bottom:1px solid var(--border)">
                        <div style="font-weight:600"><?= ucfirst($apt['type']) ?></div>
                        <div class="td-secondary"><?= date('d M Y H:i', strtotime($apt['scheduled_at'])) ?> · <?= $apt['duration_min'] ?>min</div>
                        <div><?= status_badge($apt['status']) ?> <?= h($apt['assigned_name'] ?? '') ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="color:var(--gray-400)">No appointments yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
