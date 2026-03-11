<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'dev');

$db    = db();
$id    = (int)($_GET['id'] ?? 0);
$isNew = isset($_GET['new']);

$ticket = null;
$errors = [];

if ($id) {
    $stmt = $db->prepare(
        'SELECT d.*, i.domain as instance_domain, a.name as assigned_name, c.name as creator_name
         FROM dev_tickets d
         LEFT JOIN instances i ON i.id = d.instance_id
         LEFT JOIN admin_users a ON a.id = d.assigned_to
         LEFT JOIN admin_users c ON c.id = d.created_by
         WHERE d.id = ?'
    );
    $stmt->execute([$id]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        flash('error', 'Ticket not found.');
        header('Location: ' . BASE . '/dev-tickets.php');
        exit;
    }
}

$pageTitle = $id ? 'Dev Ticket #' . $id : 'New Dev Ticket';

// Fetch dev users + instances for forms
$devUsers  = $db->query("SELECT id, name FROM admin_users WHERE role IN ('admin','dev') AND is_active=1 ORDER BY name")->fetchAll();
$instances = $db->query('SELECT id, domain FROM instances ORDER BY domain')->fetchAll();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority    = $_POST['priority'] ?? 'normal';
        $assignedTo  = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $instanceId  = (int)($_POST['instance_id'] ?? 0) ?: null;

        if (!$title) $errors[] = 'Title is required.';
        if (!in_array($priority, ['low','normal','high','critical'], true)) $errors[] = 'Invalid priority.';

        if (empty($errors)) {
            $db->prepare(
                'INSERT INTO dev_tickets (title, description, priority, assigned_to, instance_id, created_by) VALUES (?,?,?,?,?,?)'
            )->execute([$title, $description, $priority, $assignedTo, $instanceId, $_SESSION['admin_id']]);
            flash('success', 'Dev ticket created.');
            header('Location: ' . BASE . '/dev-tickets.php');
            exit;
        }
    }

    if ($action === 'update' && $ticket) {
        $status     = $_POST['status'] ?? $ticket['status'];
        $priority   = $_POST['priority'] ?? $ticket['priority'];
        $assignedTo = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $resolvedAt = ($status === 'resolved' && $ticket['status'] !== 'resolved') ? date('Y-m-d H:i:s') : $ticket['resolved_at'];

        $db->prepare('UPDATE dev_tickets SET status=?, priority=?, assigned_to=?, resolved_at=? WHERE id=?')
           ->execute([$status, $priority, $assignedTo, $resolvedAt, $id]);

        // ── Workflow: dev ticket resolved → auto-resolve linked support ticket ──
        if ($status === 'resolved' && $ticket['status'] !== 'resolved' && $ticket['support_ticket_id']) {
            $supportId = $ticket['support_ticket_id'];
            // Only auto-resolve if not already resolved/closed
            $supportTicket = $db->prepare('SELECT status FROM tickets WHERE id = ?');
            $supportTicket->execute([$supportId]);
            $supportTicket = $supportTicket->fetch();
            if ($supportTicket && !in_array($supportTicket['status'], ['resolved','closed'], true)) {
                $db->prepare('UPDATE tickets SET status=? WHERE id=?')->execute(['resolved', $supportId]);
                $db->prepare(
                    'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, body) VALUES (?,?,?,?)'
                )->execute([
                    $supportId, 'admin', $_SESSION['admin_id'],
                    "Issue resolved by the development team (Dev Ticket #{$id}). "
                    . "The fix has been addressed. Please verify and let us know if you need further assistance.",
                ]);
            }
        }

        flash('success', 'Ticket updated.' . ($status === 'resolved' && $ticket['support_ticket_id'] ? ' Linked support ticket auto-resolved.' : ''));
        header('Location: ' . BASE . '/dev-ticket-view.php?id=' . $id);
        exit;
    }
}

include __DIR__ . '/includes/layout-start.php';
?>

<div style="margin-bottom:16px">
    <a href="<?= BASE ?>/dev-tickets.php" style="color:var(--primary);font-size:13px;text-decoration:none">← Back to Dev Tickets</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $err): ?>
    <div><?= h($err) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($isNew && !$ticket): ?>
<!-- Create Form -->
<div class="card" style="max-width:620px">
    <div class="card-header"><span>New Dev Ticket</span></div>
    <div class="card-body" style="padding:24px">
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required value="<?= h($_POST['title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="5"><?= h($_POST['description'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:12px">
                <div class="form-group" style="flex:1">
                    <label>Priority</label>
                    <select name="priority" class="form-control">
                        <?php foreach (['low','normal','high','critical'] as $p): ?>
                        <option value="<?= $p ?>" <?= ($_POST['priority'] ?? 'normal') === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Assign To</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">Unassigned</option>
                        <?php foreach ($devUsers as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= h($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Instance</label>
                    <select name="instance_id" class="form-control">
                        <option value="">None</option>
                        <?php foreach ($instances as $inst): ?>
                        <option value="<?= $inst['id'] ?>"><?= h($inst['domain']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px">
                <button type="submit" class="btn btn-primary">Create Ticket</button>
                <a href="<?= BASE ?>/dev-tickets.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($ticket): ?>
<!-- View/Edit Ticket -->
<div class="two-col" style="gap:20px;align-items:start">
    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header">
                <span style="display:flex;align-items:center;gap:8px">
                    <?= priority_badge($ticket['priority']) ?>
                    <?= status_badge($ticket['status']) ?>
                </span>
            </div>
            <div class="card-body" style="padding:20px">
                <h3 style="margin:0 0 12px;font-size:16px"><?= h($ticket['title']) ?></h3>
                <?php if ($ticket['description']): ?>
                <div style="font-size:14px;color:var(--gray-700);line-height:1.7;white-space:pre-wrap"><?= h($ticket['description']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Linked Error -->
        <?php if ($ticket['error_log_id']): ?>
        <?php
            $linkedError = $db->prepare('SELECT id, level, message, exception_class, file, line, occurrence_count FROM error_logs WHERE id = ?');
            $linkedError->execute([$ticket['error_log_id']]);
            $linkedError = $linkedError->fetch();
        ?>
        <?php if ($linkedError): ?>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span>Linked Error</span></div>
            <div class="card-body" style="padding:16px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                    <?= level_badge($linkedError['level']) ?>
                    <strong><?= h(basename($linkedError['exception_class'] ?: 'Error')) ?></strong>
                    <span class="td-secondary"><?= $linkedError['occurrence_count'] ?> occurrences</span>
                </div>
                <p style="font-size:13px;color:var(--gray-600);margin:0 0 8px"><?= h(mb_substr($linkedError['message'], 0, 200)) ?></p>
                <a href="<?= BASE ?>/error-view.php?id=<?= $linkedError['id'] ?>" class="btn btn-sm btn-secondary">View Error</a>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Linked Support Ticket -->
        <?php if ($ticket['support_ticket_id']): ?>
        <?php
            $linkedTicket = $db->prepare('SELECT t.id, t.subject, t.status, t.priority, c.name as cname FROM tickets t JOIN customers c ON c.id=t.customer_id WHERE t.id = ?');
            $linkedTicket->execute([$ticket['support_ticket_id']]);
            $linkedTicket = $linkedTicket->fetch();
        ?>
        <?php if ($linkedTicket): ?>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span>Linked Support Ticket</span></div>
            <div class="card-body" style="padding:16px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                    <?= status_badge($linkedTicket['status']) ?>
                    <?= priority_badge($linkedTicket['priority']) ?>
                </div>
                <p style="font-size:14px;font-weight:600;margin:0 0 4px"><?= h($linkedTicket['subject']) ?></p>
                <p style="font-size:13px;color:var(--gray-500);margin:0 0 8px">Customer: <?= h($linkedTicket['cname']) ?></p>
                <a href="<?= BASE ?>/ticket-view.php?id=<?= $linkedTicket['id'] ?>" class="btn btn-sm btn-secondary">View Ticket</a>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Right: Controls -->
    <div>
        <div class="card">
            <div class="card-header"><span>Update Ticket</span></div>
            <div class="card-body" style="padding:16px">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <?php foreach (['open','investigating','in_progress','resolved','closed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" class="form-control">
                            <?php foreach (['low','normal','high','critical'] as $p): ?>
                            <option value="<?= $p ?>" <?= $ticket['priority'] === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Assign To</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">Unassigned</option>
                            <?php foreach ($devUsers as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= (int)$ticket['assigned_to'] === (int)$u['id'] ? 'selected' : '' ?>><?= h($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">Update</button>
                </form>
            </div>
        </div>

        <div class="card" style="margin-top:16px">
            <div class="card-header"><span>Info</span></div>
            <div class="card-body" style="padding:16px">
                <table class="info-table">
                    <tr><td class="info-label">Instance</td><td><?= h($ticket['instance_domain'] ?? '-') ?></td></tr>
                    <tr><td class="info-label">Created By</td><td><?= h($ticket['creator_name'] ?? '-') ?></td></tr>
                    <tr><td class="info-label">Created</td><td><?= date('d M Y H:i', strtotime($ticket['created_at'])) ?></td></tr>
                    <?php if ($ticket['resolved_at']): ?>
                    <tr><td class="info-label">Resolved</td><td><?= date('d M Y H:i', strtotime($ticket['resolved_at'])) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
