<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'dev');

$db = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare(
    'SELECT e.*, i.domain as instance_domain, i.name as instance_name, i.environment as instance_env,
            u.name as resolved_by_name
     FROM error_logs e
     JOIN instances i ON i.id = e.instance_id
     LEFT JOIN admin_users u ON u.id = e.resolved_by
     WHERE e.id = ?'
);
$stmt->execute([$id]);
$error = $stmt->fetch();

if (!$error) {
    flash('error', 'Error not found.');
    header('Location: ' . BASE . '/errors.php');
    exit;
}

$pageTitle = 'Error #' . $id;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_resolved') {
        $newStatus = $error['is_resolved'] ? 0 : 1;
        $db->prepare('UPDATE error_logs SET is_resolved=?, resolved_by=?, resolved_at=? WHERE id=?')
           ->execute([
               $newStatus,
               $newStatus ? $_SESSION['admin_id'] : null,
               $newStatus ? date('Y-m-d H:i:s') : null,
               $id
           ]);
        flash('success', $newStatus ? 'Marked as resolved.' : 'Reopened.');
        header('Location: ' . BASE . '/error-view.php?id=' . $id);
        exit;
    }

    if ($action === 'create_dev_ticket') {
        // Check if dev ticket already exists for this error
        $existing = $db->prepare('SELECT id FROM dev_tickets WHERE error_log_id = ?');
        $existing->execute([$id]);
        if ($existing->fetch()) {
            flash('error', 'A dev ticket already exists for this error.');
        } else {
            $title = basename($error['exception_class'] ?: 'Error') . ' in ' . basename($error['file'] ?? 'unknown');
            $desc  = "**Error:** " . $error['message'] . "\n\n"
                   . "**File:** " . $error['file'] . ':' . $error['line'] . "\n"
                   . "**Instance:** " . $error['instance_domain'] . "\n"
                   . "**Occurrences:** " . $error['occurrence_count'] . "\n"
                   . "**First seen:** " . $error['first_seen_at'] . "\n"
                   . "**Last seen:** " . $error['last_seen_at'];
            $db->prepare(
                'INSERT INTO dev_tickets (instance_id, error_log_id, title, description, priority, created_by) VALUES (?,?,?,?,?,?)'
            )->execute([$error['instance_id'], $id, $title, $desc, 'high', $_SESSION['admin_id']]);
            flash('success', 'Dev ticket created.');
        }
        header('Location: ' . BASE . '/error-view.php?id=' . $id);
        exit;
    }
}

// Check if dev ticket exists
$devTicket = $db->prepare('SELECT id, status FROM dev_tickets WHERE error_log_id = ?');
$devTicket->execute([$id]);
$devTicket = $devTicket->fetch();

include __DIR__ . '/includes/layout-start.php';
?>

<div style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between">
    <a href="<?= BASE ?>/errors.php" style="color:var(--primary);font-size:13px;text-decoration:none">← Back to Error Logs</a>
    <div style="display:flex;gap:8px">
        <?php if (!$devTicket): ?>
        <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="create_dev_ticket">
            <button type="submit" class="btn btn-sm btn-secondary">Create Dev Ticket</button>
        </form>
        <?php else: ?>
        <a href="<?= BASE ?>/dev-ticket-view.php?id=<?= $devTicket['id'] ?>" class="btn btn-sm btn-secondary">
            View Dev Ticket (<?= h($devTicket['status']) ?>)
        </a>
        <?php endif; ?>
        <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="toggle_resolved">
            <button type="submit" class="btn btn-sm <?= $error['is_resolved'] ? 'btn-secondary' : 'btn-primary' ?>">
                <?= $error['is_resolved'] ? 'Reopen' : 'Mark Resolved' ?>
            </button>
        </form>
    </div>
</div>

<div class="two-col" style="gap:20px;align-items:start">
    <!-- Left: Message + Stack Trace -->
    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header">
                <span style="display:flex;align-items:center;gap:8px">
                    <?= level_badge($error['level']) ?>
                    <?= $error['is_resolved'] ? status_badge('resolved') : status_badge('open') ?>
                    <span style="font-weight:600"><?= h($error['exception_class'] ?: 'Error') ?></span>
                </span>
            </div>
            <div class="card-body" style="padding:16px">
                <p style="font-size:14px;color:var(--gray-800);margin:0 0 16px;word-break:break-word"><?= h($error['message']) ?></p>
                <?php if ($error['stack_trace']): ?>
                <div class="stack-trace"><?= h($error['stack_trace']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right: Info Cards -->
    <div>
        <!-- Error Info -->
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span>Error Info</span></div>
            <div class="card-body" style="padding:16px">
                <table class="info-table">
                    <tr><td class="info-label">File</td><td style="font-family:monospace;font-size:12px;word-break:break-all"><?= h($error['file'] ?? '-') ?></td></tr>
                    <tr><td class="info-label">Line</td><td><?= $error['line'] ?: '-' ?></td></tr>
                    <tr><td class="info-label">Occurrences</td><td><strong><?= number_format($error['occurrence_count']) ?></strong></td></tr>
                    <tr><td class="info-label">First Seen</td><td><?= $error['first_seen_at'] ? date('d M Y H:i', strtotime($error['first_seen_at'])) : '-' ?></td></tr>
                    <tr><td class="info-label">Last Seen</td><td><?= $error['last_seen_at'] ? date('d M Y H:i', strtotime($error['last_seen_at'])) : '-' ?></td></tr>
                    <?php if ($error['is_resolved']): ?>
                    <tr><td class="info-label">Resolved By</td><td><?= h($error['resolved_by_name'] ?? '-') ?></td></tr>
                    <tr><td class="info-label">Resolved At</td><td><?= $error['resolved_at'] ? date('d M Y H:i', strtotime($error['resolved_at'])) : '-' ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Instance -->
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span>Instance</span></div>
            <div class="card-body" style="padding:16px">
                <table class="info-table">
                    <tr><td class="info-label">Domain</td><td><strong><?= h($error['instance_domain']) ?></strong></td></tr>
                    <tr><td class="info-label">Name</td><td><?= h($error['instance_name']) ?></td></tr>
                    <tr><td class="info-label">Environment</td><td><?= status_badge($error['instance_env'] ?? 'production') ?></td></tr>
                    <tr><td class="info-label">App Version</td><td><?= h($error['app_version'] ?? '-') ?></td></tr>
                    <tr><td class="info-label">PHP Version</td><td><?= h($error['php_version'] ?? '-') ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Request Context -->
        <?php if ($error['url'] || $error['user_info'] || $error['request_data']): ?>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span>Request Context</span></div>
            <div class="card-body" style="padding:16px">
                <?php if ($error['url']): ?>
                <div style="margin-bottom:10px">
                    <span class="info-label" style="display:block;margin-bottom:4px">URL</span>
                    <code style="font-size:12px;word-break:break-all"><?= h($error['method'] ?? '') ?> <?= h($error['url']) ?></code>
                </div>
                <?php endif; ?>
                <?php if ($error['user_info']): ?>
                <div style="margin-bottom:10px">
                    <span class="info-label" style="display:block;margin-bottom:4px">User</span>
                    <div class="json-view"><?= h(json_encode(json_decode($error['user_info'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($error['request_data']): ?>
                <div>
                    <span class="info-label" style="display:block;margin-bottom:4px">Request Data</span>
                    <div class="json-view"><?= h(json_encode(json_decode($error['request_data'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
