<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();
$pageTitle = 'Chat Sessions';

$db = db();

$filterSearch = trim($_GET['search'] ?? '');
$selectedId   = (int)($_GET['session'] ?? 0);
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 25;

$where  = ['1=1'];
$params = [];
if ($filterSearch) {
    $where[] = '(name LIKE ? OR email LIKE ?)';
    $s = '%' . $filterSearch . '%';
    $params = [$s, $s];
}
$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM chat_sessions WHERE {$whereStr}");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$offset     = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT s.*, (SELECT COUNT(*) FROM chat_messages WHERE session_id=s.id) as msg_count
     FROM chat_sessions s WHERE {$whereStr}
     ORDER BY s.created_at DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

$currentSession = null;
$currentMessages = [];
if ($selectedId) {
    $ss = $db->prepare('SELECT * FROM chat_sessions WHERE id=?');
    $ss->execute([$selectedId]);
    $currentSession = $ss->fetch();
    if ($currentSession) {
        $msgs = $db->prepare('SELECT * FROM chat_messages WHERE session_id=? ORDER BY created_at ASC');
        $msgs->execute([$selectedId]);
        $currentMessages = $msgs->fetchAll();
    }
}

include __DIR__ . '/includes/layout-start.php';
?>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <input type="text" name="search" class="form-control" placeholder="Name or email…" value="<?= h($filterSearch) ?>">
        <?php if ($selectedId): ?><input type="hidden" name="session" value="<?= $selectedId ?>"><?php endif; ?>
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="<?= BASE ?>/chat.php" class="btn btn-secondary">Clear</a>
    </form>
    <div class="filter-count"><?= $totalRows ?> session<?= $totalRows !== 1 ? 's' : '' ?></div>
</div>

<div class="two-col" style="align-items:flex-start">
    <!-- Session list -->
    <div class="card" style="flex:1;max-width:340px">
        <div class="card-header"><span>Sessions</span></div>
        <div style="padding:0">
        <?php foreach ($sessions as $s): ?>
        <a href="?session=<?= $s['id'] ?><?= $filterSearch ? '&search=' . urlencode($filterSearch) : '' ?>"
           class="chat-session-row <?= $selectedId === (int)$s['id'] ? 'active' : '' ?>">
            <div class="td-primary"><?= h($s['name']) ?></div>
            <div class="td-secondary"><?= h($s['email']) ?></div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--gray-400);margin-top:2px">
                <span><?= time_ago($s['created_at']) ?></span>
                <span><?= $s['msg_count'] ?> msg<?= $s['msg_count'] !== '1' ? 's' : '' ?></span>
            </div>
        </a>
        <?php endforeach; ?>
        <?php if (empty($sessions)): ?>
        <div class="empty-row" style="padding:20px">No chat sessions yet.</div>
        <?php endif; ?>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="pagination" style="padding:12px">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?= $p ?>&search=<?= urlencode($filterSearch) ?>"
               class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Messages panel -->
    <div class="card" style="flex:2">
        <div class="card-header">
            <span><?= $currentSession ? h($currentSession['name']) . ' &lt;' . h($currentSession['email']) . '&gt;' : 'Select a session' ?></span>
            <?php if ($currentSession): ?>
            <span class="td-secondary"><?= date('d M Y H:i', strtotime($currentSession['created_at'])) ?> · <?= h($currentSession['ip'] ?? '—') ?></span>
            <?php endif; ?>
        </div>
        <div style="padding:16px;display:flex;flex-direction:column;gap:10px;min-height:200px">
        <?php if ($currentSession && $currentMessages): ?>
            <?php foreach ($currentMessages as $m): ?>
            <div class="message message-customer">
                <div class="message-meta"><strong><?= h($currentSession['name']) ?></strong><span><?= time_ago($m['created_at']) ?></span></div>
                <div class="message-body"><?= nl2br(h($m['body'])) ?></div>
            </div>
            <?php endforeach; ?>
        <?php elseif ($currentSession): ?>
            <p class="text-muted">No messages in this session.</p>
        <?php else: ?>
            <p class="text-muted" style="text-align:center;padding:40px 0">Select a session from the left to view messages.</p>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
