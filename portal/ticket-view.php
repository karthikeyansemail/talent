<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

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

        // Auto-set status to in_progress if still open
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

    header('Location: ' . BASE . '/ticket-view.php?id=' . $id);
    exit;
}

// Fetch messages
$messages = $db->prepare(
    'SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY created_at ASC'
);
$messages->execute([$id]);
$messages = $messages->fetchAll();

include __DIR__ . '/includes/layout-start.php';
?>

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
    <a href="<?= BASE ?>/tickets.php" class="btn btn-secondary btn-sm">← Back</a>
    <h2 style="margin:0;font-size:18px"><?= h($ticket['subject']) ?></h2>
    <?= status_badge($ticket['status']) ?>
    <?= status_badge($ticket['priority']) ?>
</div>

<div class="two-col" style="align-items:flex-start">
    <!-- Thread -->
    <div class="card" style="flex:2">
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

        <!-- Reply form -->
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

    <!-- Info panel -->
    <div style="display:flex;flex-direction:column;gap:12px">
        <div class="card">
            <div class="card-header"><span>Customer</span></div>
            <div style="padding:16px">
                <div class="td-primary"><?= h($ticket['cname']) ?></div>
                <div class="td-secondary"><?= h($ticket['cemail']) ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span>Ticket Status</span></div>
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
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
