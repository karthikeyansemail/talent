<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'support');
$pageTitle = 'Tickets';

$db = db();

$filterStatus   = $_GET['status']   ?? '';
$filterPriority = $_GET['priority'] ?? '';
$filterSearch   = trim($_GET['search'] ?? '');
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 20;

$where  = ['1=1'];
$params = [];

if ($filterStatus) {
    $where[] = 't.status = ?';
    $params[] = $filterStatus;
}
if ($filterPriority) {
    $where[] = 't.priority = ?';
    $params[] = $filterPriority;
}
if ($filterSearch) {
    $where[] = '(c.name LIKE ? OR c.email LIKE ? OR t.subject LIKE ?)';
    $s = '%' . $filterSearch . '%';
    $params = array_merge($params, [$s, $s, $s]);
}

$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM tickets t JOIN customers c ON c.id=t.customer_id WHERE {$whereStr}");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$offset     = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT t.*, c.name as cname, c.email as cemail,
        (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id=t.id) as msg_count
     FROM tickets t JOIN customers c ON c.id=t.customer_id
     WHERE {$whereStr}
     ORDER BY t.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Load customers for modal
$customerList = $db->query('SELECT id, name, email FROM customers ORDER BY name')->fetchAll();

$ticketErrors    = [];
$ticketModalOpen = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_ticket') {
    $customerId  = (int)($_POST['customer_id'] ?? 0);
    $subject     = trim($_POST['subject'] ?? '');
    $priority    = $_POST['priority'] ?? 'normal';
    $ticketType  = $_POST['ticket_type'] ?? 'general';
    $firstMsg    = trim($_POST['first_message'] ?? '');

    if (!$customerId)  $ticketErrors[] = 'Customer is required.';
    if (!$subject)     $ticketErrors[] = 'Subject is required.';
    if (!in_array($priority, ['low','normal','high','urgent'], true)) $ticketErrors[] = 'Invalid priority.';
    if (!in_array($ticketType, ['general','billing','software_bug','tech_support'], true)) $ticketErrors[] = 'Invalid type.';

    if (empty($ticketErrors)) {
        $db->prepare(
            'INSERT INTO tickets (customer_id, subject, priority, ticket_type, status) VALUES (?,?,?,?,?)'
        )->execute([$customerId, $subject, $priority, $ticketType, 'open']);
        $ticketId = (int)$db->lastInsertId();
        if ($firstMsg) {
            $db->prepare(
                'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, body) VALUES (?,?,?,?)'
            )->execute([$ticketId, 'admin', $_SESSION['admin_id'], $firstMsg]);
        }
        flash('success', 'Ticket #' . $ticketId . ' created.');
        header('Location: ' . BASE . '/ticket-view.php?id=' . $ticketId);
        exit;
    }
    $ticketModalOpen = true;
}

include __DIR__ . '/includes/layout-start.php';
?>

<!-- Modal: New Ticket -->
<div class="modal-overlay hidden" id="ticket-modal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">New Ticket</h2>
            <button class="modal-close" onclick="closeTicketModal()">&times;</button>
        </div>
        <?php if (!empty($ticketErrors)): ?>
        <div class="alert alert-error" style="margin:16px 24px 0">
            <?php foreach ($ticketErrors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="create_ticket">
            <div class="modal-body">
                <div class="form-group">
                    <label>Customer *</label>
                    <select name="customer_id" class="form-control" required>
                        <option value="">Select customer…</option>
                        <?php foreach ($customerList as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($_POST['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= h($c['name']) ?> &lt;<?= h($c['email']) ?>&gt;
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject *</label>
                    <input type="text" name="subject" class="form-control" required autofocus value="<?= h($_POST['subject'] ?? '') ?>">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Type</label>
                        <select name="ticket_type" class="form-control">
                            <?php foreach (['general'=>'General','billing'=>'Billing','software_bug'=>'Software Bug','tech_support'=>'Tech Support'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($_POST['ticket_type'] ?? 'general') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" class="form-control">
                            <?php foreach (['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($_POST['priority'] ?? 'normal') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Initial Message <span style="color:var(--gray-400);font-size:12px">(optional)</span></label>
                    <textarea name="first_message" class="form-control" rows="3" placeholder="Describe the issue…"><?= h($_POST['first_message'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTicketModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Ticket</button>
            </div>
        </form>
    </div>
</div>
<script>
function openTicketModal()  { document.getElementById('ticket-modal').classList.remove('hidden'); }
function closeTicketModal() { document.getElementById('ticket-modal').classList.add('hidden'); }
document.getElementById('ticket-modal').addEventListener('click', function(e) { if (e.target === this) closeTicketModal(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeTicketModal(); });
<?php if ($ticketModalOpen): ?>document.addEventListener('DOMContentLoaded', openTicketModal);<?php endif; ?>
</script>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <input type="text" name="search" class="form-control" placeholder="Customer, subject…" value="<?= h($filterSearch) ?>">
        <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <?php foreach (['open','in_progress','resolved','closed'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="priority" class="form-control">
            <option value="">All Priorities</option>
            <?php foreach (['low','normal','high','urgent'] as $p): ?>
            <option value="<?= $p ?>" <?= $filterPriority === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="<?= BASE ?>/tickets.php" class="btn btn-secondary">Clear</a>
    </form>
    <div style="display:flex;align-items:center;gap:12px">
        <div class="filter-count"><?= $totalRows ?> ticket<?= $totalRows !== 1 ? 's' : '' ?></div>
        <button class="btn btn-primary" onclick="openTicketModal()">+ New Ticket</button>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr><th>#</th><th>Customer</th><th>Subject</th><th>Priority</th><th>Status</th><th>Messages</th><th>Created</th></tr>
        </thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
        <tr>
            <td class="td-secondary"><?= $t['id'] ?></td>
            <td>
                <div class="td-primary"><?= h($t['cname']) ?></div>
                <div class="td-secondary"><?= h($t['cemail']) ?></div>
            </td>
            <td>
                <a href="<?= BASE ?>/ticket-view.php?id=<?= $t['id'] ?>" class="link"><?= h($t['subject']) ?></a>
            </td>
            <td><?= status_badge($t['priority']) ?></td>
            <td><?= status_badge($t['status']) ?></td>
            <td class="td-secondary"><?= $t['msg_count'] ?></td>
            <td class="td-secondary"><?= time_ago($t['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($tickets)): ?>
        <tr><td colspan="7" class="empty-row">No tickets found.</td></tr>
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
