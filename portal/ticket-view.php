<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'support');

$db  = db();
$id  = (int)($_GET['id'] ?? 0);

$ticket = $db->prepare(
    'SELECT t.*, c.name as cname, c.email as cemail
     FROM tickets t JOIN customers c ON c.id=t.customer_id
     WHERE t.id = ?'
);
$ticket->execute([$id]);
$ticket = $ticket->fetch();
if (!$ticket) { header('HTTP/1.1 404 Not Found'); echo '404 — Ticket not found'; exit; }

$pageTitle = 'Ticket #' . $id;

// Handle reply or status change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'reply' && !empty(trim($_POST['body'] ?? ''))) {
        $stmt = $db->prepare(
            'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, body) VALUES (?,?,?,?)'
        );
        $stmt->execute([$id, 'admin', $_SESSION['admin_id'], trim($_POST['body'])]);
        if ($ticket['status'] === 'open') {
            $db->prepare('UPDATE tickets SET status=? WHERE id=?')->execute(['in_progress', $id]);
        }
        flash('success', 'Reply sent.');
    }

    if ($action === 'change_status' && !empty($_POST['new_status'])) {
        $allowed = ['open','in_progress','resolved','closed'];
        if (in_array($_POST['new_status'], $allowed, true)) {
            $db->prepare('UPDATE tickets SET status=? WHERE id=?')->execute([$_POST['new_status'], $id]);
            flash('success', 'Status updated.');
        }
    }

    // ── Workflow: set ticket type (billing / software_bug / tech_support / general) ──
    if ($action === 'set_type' && !empty($_POST['ticket_type'])) {
        $allowed = ['general','billing','software_bug','tech_support'];
        if (in_array($_POST['ticket_type'], $allowed, true)) {
            $db->prepare('UPDATE tickets SET ticket_type=? WHERE id=?')->execute([$_POST['ticket_type'], $id]);
            flash('success', 'Ticket type updated.');
        }
    }

    // ── Workflow: escalate to dev ──
    if ($action === 'escalate_to_dev' && empty($ticket['dev_ticket_id'])) {
        $devTitle = 'Support Escalation: ' . $ticket['subject'];
        $devDesc  = "Escalated from support ticket #{$id}\nCustomer: {$ticket['cname']} ({$ticket['cemail']})\n\n";
        $recentMsgs = $db->prepare('SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY created_at DESC LIMIT 5');
        $recentMsgs->execute([$id]);
        foreach ($recentMsgs->fetchAll() as $m) {
            $sender = $m['sender_type'] === 'admin' ? 'Support' : $ticket['cname'];
            $devDesc .= "[{$sender}] " . $m['body'] . "\n\n";
        }
        // Link error_log if provided
        $errorLogId = (int)($_POST['error_log_id'] ?? 0) ?: null;
        $db->prepare(
            'INSERT INTO dev_tickets (support_ticket_id, error_log_id, title, description, priority, created_by) VALUES (?,?,?,?,?,?)'
        )->execute([$id, $errorLogId, $devTitle, $devDesc, $ticket['priority'], $_SESSION['admin_id']]);
        $devTicketId = (int)$db->lastInsertId();
        $db->prepare('UPDATE tickets SET dev_ticket_id=?, escalated_at=NOW(), ticket_type=? WHERE id=?')
           ->execute([$devTicketId, 'software_bug', $id]);
        flash('success', 'Ticket escalated to Dev team.');
    }

    // ── Workflow: forward billing ticket to sales (log on customer's lead) ──
    if ($action === 'forward_billing') {
        // Find the customer's originating lead
        $leadStmt = $db->prepare('SELECT id FROM leads WHERE customer_id = ? ORDER BY created_at DESC LIMIT 1');
        $leadStmt->execute([$ticket['customer_id']]);
        $leadRow = $leadStmt->fetch();
        $note = trim($_POST['billing_note'] ?? 'Customer raised a billing issue via support ticket #' . $id . '.');
        if ($leadRow) {
            $db->prepare('INSERT INTO lead_activities (lead_id, user_id, type, description) VALUES (?,?,?,?)')
               ->execute([$leadRow['id'], $_SESSION['admin_id'], 'note', '[Billing] ' . $note]);
        }
        $db->prepare('UPDATE tickets SET ticket_type=?, billing_forwarded_at=NOW() WHERE id=?')
           ->execute(['billing', $id]);
        flash('success', 'Billing issue forwarded to Sales' . ($leadRow ? ' and logged on lead #' . $leadRow['id'] : '') . '.');
    }

    header('Location: ' . BASE . '/ticket-view.php?id=' . $id);
    exit;
}

// Fetch messages
$messages = $db->prepare('SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY created_at ASC');
$messages->execute([$id]);
$messages = $messages->fetchAll();

// ── Fetch customer's instances + recent errors (for support context) ──
$customerInstances = $db->prepare(
    'SELECT i.id, i.domain, i.name, i.is_active, i.last_seen_at,
            (SELECT COUNT(*) FROM error_logs WHERE instance_id=i.id AND is_resolved=0) as open_errors
     FROM instances i WHERE i.customer_id = ? ORDER BY i.created_at DESC'
);
$customerInstances->execute([$ticket['customer_id']]);
$customerInstances = $customerInstances->fetchAll();

// Fetch recent unresolved errors from all customer instances
$instanceIds = array_column($customerInstances, 'id');
$recentErrors = [];
if ($instanceIds) {
    $placeholders = implode(',', array_fill(0, count($instanceIds), '?'));
    $errStmt = $db->prepare(
        "SELECT e.id, e.level, e.message, e.exception_class, e.occurrence_count, e.last_seen_at, i.domain
         FROM error_logs e JOIN instances i ON i.id = e.instance_id
         WHERE e.instance_id IN ({$placeholders}) AND e.is_resolved = 0
         ORDER BY e.last_seen_at DESC LIMIT 5"
    );
    $errStmt->execute($instanceIds);
    $recentErrors = $errStmt->fetchAll();
}

include __DIR__ . '/includes/layout-start.php';
?>

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
    <a href="<?= BASE ?>/tickets.php" class="btn btn-secondary btn-sm">← Back</a>
    <h2 style="margin:0;font-size:18px"><?= h($ticket['subject']) ?></h2>
    <?= status_badge($ticket['status']) ?>
    <?= status_badge($ticket['priority']) ?>
    <?php
        $ticketType = $ticket['ticket_type'] ?? 'general';
        $typeColors = ['billing'=>'#f59e0b','software_bug'=>'#ef4444','tech_support'=>'#3b82f6','general'=>'#6b7280'];
        $typeColor = $typeColors[$ticketType] ?? '#6b7280';
    ?>
    <span style="display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;background:<?= $typeColor ?>22;color:<?= $typeColor ?>;border:1px solid <?= $typeColor ?>44">
        <?= ucfirst(str_replace('_', ' ', $ticketType)) ?>
    </span>
    <?php if (!empty($ticket['dev_ticket_id'])): ?>
        <a href="<?= BASE ?>/dev-ticket-view.php?id=<?= $ticket['dev_ticket_id'] ?>" style="font-size:12px;font-weight:600;color:var(--warning);text-decoration:none">Escalated to Dev →</a>
    <?php endif; ?>
    <?php if (!empty($ticket['billing_forwarded_at'])): ?>
        <span style="font-size:12px;font-weight:600;color:#f59e0b">Forwarded to Sales ✓</span>
    <?php endif; ?>
</div>

<div class="two-col" style="align-items:flex-start">
    <!-- Thread -->
    <div style="flex:2;display:flex;flex-direction:column;gap:16px">
        <div class="card">
            <div class="card-header"><span>Conversation</span></div>
            <div style="padding:16px;display:flex;flex-direction:column;gap:12px">
            <?php foreach ($messages as $m): ?>
            <div class="message <?= $m['sender_type'] === 'admin' ? 'message-admin' : 'message-customer' ?>">
                <div class="message-meta">
                    <strong><?= $m['sender_type'] === 'admin' ? 'Support Team' : h($ticket['cname']) ?></strong>
                    <span><?= time_ago($m['created_at']) ?></span>
                </div>
                <div class="message-body"><?= nl2br(h($m['body'])) ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($messages)): ?>
            <p class="text-muted">No messages yet.</p>
            <?php endif; ?>
            </div>
            <div style="padding:0 16px 16px">
                <form method="POST">
                    <input type="hidden" name="action" value="reply">
                    <div class="form-group">
                        <label>Your Reply</label>
                        <textarea name="body" rows="4" class="form-control" placeholder="Type your reply…" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </form>
            </div>
        </div>

        <!-- Customer Instance Errors Panel -->
        <?php if (!empty($customerInstances)): ?>
        <div class="card">
            <div class="card-header">
                <span>Customer Instances</span>
                <span style="font-size:12px;color:var(--gray-500)"><?= count($customerInstances) ?> registered</span>
            </div>
            <div style="padding:12px 16px;display:flex;flex-direction:column;gap:8px">
                <?php foreach ($customerInstances as $inst): ?>
                <div style="display:flex;align-items:center;gap:8px;font-size:13px">
                    <span class="health-dot health-<?= $inst['is_active'] ? 'green' : 'gray' ?>"></span>
                    <strong><?= h($inst['domain']) ?></strong>
                    <?php if ($inst['open_errors'] > 0): ?>
                    <a href="<?= BASE ?>/errors.php?instance=<?= $inst['id'] ?>&resolved=0"
                       style="color:var(--danger);font-weight:600;font-size:12px"><?= $inst['open_errors'] ?> open errors</a>
                    <?php else: ?>
                    <span style="color:var(--success);font-size:12px">No open errors</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($recentErrors)): ?>
            <div style="border-top:1px solid var(--border);padding:12px 16px">
                <div style="font-size:12px;font-weight:600;color:var(--gray-500);margin-bottom:8px">RECENT ERRORS</div>
                <?php foreach ($recentErrors as $err): ?>
                <div style="padding:8px 0;border-bottom:1px solid var(--gray-100);display:flex;align-items:flex-start;gap:8px">
                    <div style="flex:1">
                        <?= level_badge($err['level']) ?>
                        <span style="font-size:13px;font-weight:500;margin-left:6px"><?= h(basename($err['exception_class'] ?: 'Error')) ?></span>
                        <span style="font-size:11px;color:var(--gray-400);margin-left:6px"><?= $err['occurrence_count'] ?>x</span>
                        <div style="font-size:12px;color:var(--gray-500);margin-top:2px"><?= h(mb_substr($err['message'], 0, 120)) ?></div>
                        <div style="font-size:11px;color:var(--gray-400)"><?= h($err['domain']) ?> · <?= time_ago($err['last_seen_at']) ?></div>
                    </div>
                    <?php if (empty($ticket['dev_ticket_id'])): ?>
                    <form method="POST" style="flex-shrink:0">
                        <input type="hidden" name="action" value="escalate_to_dev">
                        <input type="hidden" name="error_log_id" value="<?= $err['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-secondary"
                                onclick="return confirm('Escalate this ticket to Dev, linked to this error?')">
                            Escalate + Link Error
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Info panel -->
    <div style="display:flex;flex-direction:column;gap:12px">
        <div class="card">
            <div class="card-header"><span>Customer</span></div>
            <div style="padding:16px">
                <div class="td-primary"><?= h($ticket['cname']) ?></div>
                <div class="td-secondary"><?= h($ticket['cemail']) ?></div>
            </div>
        </div>

        <!-- Ticket Type & Routing -->
        <div class="card">
            <div class="card-header"><span>Issue Type &amp; Routing</span></div>
            <div style="padding:16px">
                <form method="POST" style="margin-bottom:12px">
                    <input type="hidden" name="action" value="set_type">
                    <div class="form-group" style="margin-bottom:8px">
                        <select name="ticket_type" class="form-control" onchange="this.form.submit()">
                            <?php foreach (['general'=>'General','billing'=>'Billing Issue','software_bug'=>'Software Bug','tech_support'=>'Tech Support'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= $ticketType === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <?php if ($ticketType === 'billing' && empty($ticket['billing_forwarded_at'])): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="forward_billing">
                    <div class="form-group" style="margin-bottom:8px">
                        <textarea name="billing_note" class="form-control" rows="2"
                            placeholder="Brief note for sales team…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%"
                            onclick="return confirm('Forward this billing issue to the Sales team?')">
                        Forward to Sales
                    </button>
                </form>
                <?php elseif ($ticketType === 'billing' && !empty($ticket['billing_forwarded_at'])): ?>
                <p style="font-size:12px;color:var(--success)">✓ Forwarded to Sales on <?= date('d M', strtotime($ticket['billing_forwarded_at'])) ?></p>
                <?php endif; ?>

                <?php if ($ticketType === 'software_bug' && empty($ticket['dev_ticket_id'])): ?>
                <form method="POST" onsubmit="return confirm('Escalate to Dev team?')">
                    <input type="hidden" name="action" value="escalate_to_dev">
                    <button type="submit" class="btn btn-primary" style="width:100%">Escalate to Dev</button>
                </form>
                <?php elseif (!empty($ticket['dev_ticket_id'])): ?>
                <a href="<?= BASE ?>/dev-ticket-view.php?id=<?= $ticket['dev_ticket_id'] ?>" class="btn btn-secondary" style="width:100%;text-align:center">View Dev Ticket #<?= $ticket['dev_ticket_id'] ?> →</a>
                <?php endif; ?>

                <?php if ($ticketType === 'tech_support' && $ticket['status'] === 'open'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="change_status">
                    <input type="hidden" name="new_status" value="in_progress">
                    <button type="submit" class="btn btn-secondary" style="width:100%">Take Ownership</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span>Status</span></div>
            <div style="padding:16px">
                <form method="POST">
                    <input type="hidden" name="action" value="change_status">
                    <div class="form-group">
                        <select name="new_status" class="form-control">
                            <?php foreach (['open','in_progress','resolved','closed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary" style="width:100%">Update Status</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span>Info</span></div>
            <div style="padding:16px;font-size:13px;color:var(--gray-600);display:flex;flex-direction:column;gap:6px">
                <div>Priority: <?= status_badge($ticket['priority']) ?></div>
                <div>Created: <?= date('d M Y H:i', strtotime($ticket['created_at'])) ?></div>
                <div>Updated: <?= date('d M Y H:i', strtotime($ticket['updated_at'])) ?></div>
                <div>Messages: <?= count($messages) ?></div>
                <?php if (!empty($ticket['escalated_at'])): ?>
                <div>Escalated: <?= date('d M Y H:i', strtotime($ticket['escalated_at'])) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
