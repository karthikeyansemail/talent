<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'support');
$pageTitle = 'Chat Sessions';

$db = db();

$filterSearch = trim($_GET['search'] ?? '');
$filterStatus = $_GET['status'] ?? '';
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
if ($filterStatus) {
    $where[] = 'status = ?';
    $params[] = $filterStatus;
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

$currentSession  = null;
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
        <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <option value="open"   <?= $filterStatus === 'open'   ? 'selected' : '' ?>>Open</option>
            <option value="closed" <?= $filterStatus === 'closed' ? 'selected' : '' ?>>Closed</option>
        </select>
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
        <a href="?session=<?= $s['id'] ?><?= $filterSearch ? '&search=' . urlencode($filterSearch) : '' ?><?= $filterStatus ? '&status=' . urlencode($filterStatus) : '' ?>"
           class="chat-session-row <?= $selectedId === (int)$s['id'] ? 'active' : '' ?>">
            <div style="display:flex;align-items:center;gap:6px">
                <div class="td-primary"><?= h($s['name']) ?></div>
                <?php if ($s['status'] === 'closed'): ?>
                <span style="font-size:10px;background:#f1f5f9;color:#64748b;border-radius:4px;padding:1px 5px">closed</span>
                <?php else: ?>
                <span style="font-size:10px;background:#dcfce7;color:#166534;border-radius:4px;padding:1px 5px">open</span>
                <?php endif; ?>
            </div>
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
            <a href="?page=<?= $p ?>&search=<?= urlencode($filterSearch) ?>&status=<?= urlencode($filterStatus) ?>"
               class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Messages panel -->
    <div class="card" style="flex:2;display:flex;flex-direction:column">
        <div class="card-header" style="gap:12px">
            <span>
                <?php if ($currentSession): ?>
                    <?= h($currentSession['name']) ?> &lt;<?= h($currentSession['email']) ?>&gt;
                <?php else: ?>
                    Select a session
                <?php endif; ?>
            </span>
            <?php if ($currentSession): ?>
            <span class="td-secondary" style="margin-left:auto">
                <?= date('d M Y H:i', strtotime($currentSession['created_at'])) ?> · <?= h($currentSession['ip'] ?? '—') ?>
            </span>
            <?php if ($currentSession['status'] === 'open'): ?>
            <form method="POST" action="<?= BASE ?>/api/chat-close.php" style="display:inline" id="close-session-form">
                <input type="hidden" name="session_id" value="<?= $selectedId ?>">
                <button type="submit" class="btn btn-sm btn-secondary" data-confirm="Close this chat session?">Close Session</button>
            </form>
            <?php else: ?>
            <span style="font-size:12px;background:#f1f5f9;color:#64748b;border-radius:6px;padding:3px 8px">Session closed</span>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Messages display -->
        <div id="chat-messages" style="padding:16px;display:flex;flex-direction:column;gap:10px;min-height:280px;max-height:480px;overflow-y:auto">
        <?php if ($currentSession && $currentMessages): ?>
            <?php foreach ($currentMessages as $m): ?>
            <?php $isVisitor = $m['sender_type'] === 'visitor'; ?>
            <div class="<?= $isVisitor ? 'chat-msg-visitor' : 'chat-msg-agent' ?>" data-id="<?= $m['id'] ?>">
                <div class="chat-msg-meta">
                    <strong><?= h($m['sender_name'] ?: ($isVisitor ? $currentSession['name'] : 'Support')) ?></strong>
                    <span><?= time_ago($m['created_at']) ?></span>
                </div>
                <div class="chat-msg-body"><?= nl2br(h($m['body'])) ?></div>
            </div>
            <?php endforeach; ?>
        <?php elseif ($currentSession): ?>
            <p class="text-muted">No messages in this session.</p>
        <?php else: ?>
            <p class="text-muted" style="text-align:center;padding:40px 0">Select a session from the left to view messages.</p>
        <?php endif; ?>
        </div>

        <!-- Reply form -->
        <?php if ($currentSession && $currentSession['status'] === 'open'): ?>
        <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;gap:10px;align-items:flex-end">
            <textarea id="reply-input" class="form-control" rows="2" placeholder="Type your reply…" style="flex:1;resize:none"></textarea>
            <button id="reply-btn" class="btn btn-primary" style="flex-shrink:0">Send</button>
        </div>
        <?php elseif ($currentSession): ?>
        <div style="padding:12px 16px;border-top:1px solid var(--border);color:var(--gray-400);font-size:13px;text-align:center">
            Session is closed — no new messages can be sent.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($currentSession && $currentSession['status'] === 'open'): ?>
<script>
(function() {
    var sessionId = <?= $selectedId ?>;
    var lastId    = <?= $currentMessages ? (int)end($currentMessages)['id'] : 0 ?>;
    var messages  = document.getElementById('chat-messages');
    var input     = document.getElementById('reply-input');
    var btn       = document.getElementById('reply-btn');

    function scrollBottom() {
        messages.scrollTop = messages.scrollHeight;
    }
    scrollBottom();

    function appendMsg(senderType, senderName, body, msgId) {
        var isAgent = senderType === 'agent';
        var wrap = document.createElement('div');
        wrap.className = isAgent ? 'chat-msg-agent' : 'chat-msg-visitor';
        if (msgId) wrap.setAttribute('data-id', msgId);
        wrap.innerHTML =
            '<div class="chat-msg-meta"><strong>' + escHtml(senderName) + '</strong><span>just now</span></div>' +
            '<div class="chat-msg-body">' + escHtml(body).replace(/\n/g, '<br>') + '</div>';
        messages.appendChild(wrap);
        scrollBottom();
        if (msgId && msgId > lastId) lastId = msgId;
    }

    function escHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function sendReply() {
        var msg = input.value.trim();
        if (!msg) return;
        btn.disabled = true;
        input.value = '';
        fetch('<?= BASE ?>/api/chat-reply.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: sessionId, body: msg })
        })
        .then(function(r){ return r.json(); })
        .then(function(res) {
            btn.disabled = false;
            if (res.ok) {
                appendMsg('agent', '<?= addslashes(htmlspecialchars(admin()['name'] ?? 'Support')) ?>', msg, res.message_id);
                lastId = res.message_id;
            } else {
                alert(res.error || 'Failed to send message.');
                input.value = msg;
            }
        })
        .catch(function() {
            btn.disabled = false;
            input.value = msg;
            alert('Network error. Please try again.');
        });
    }

    btn.addEventListener('click', sendReply);
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendReply(); }
    });

    // Poll for new visitor messages every 5s
    setInterval(function() {
        fetch('<?= BASE ?>/api/chat-poll.php?session_id=' + sessionId + '&since=' + lastId + '&_admin=1')
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (res.messages && res.messages.length) {
                res.messages.forEach(function(m) {
                    // Only append visitor messages (agent messages were added optimistically)
                    if (m.sender_type === 'visitor') {
                        appendMsg(m.sender_type, m.sender_name || 'Visitor', m.body, m.id);
                    } else if (m.id > lastId) {
                        lastId = m.id;
                    }
                });
            }
            if (res.session_status === 'closed') {
                clearInterval(window._chatPollTimer);
                location.reload();
            }
        })
        .catch(function(){});
    }, 5000);
})();
</script>
<?php endif; ?>

<?php if (!$selectedId): ?>
<script>
// No session selected — auto-refresh page every 15s to show new incoming sessions
(function() {
    var countdown = 15;
    setInterval(function() {
        countdown--;
        if (countdown <= 0) location.reload();
    }, 1000);
})();
</script>
<?php elseif ($currentSession && $currentSession['status'] === 'closed'): ?>
<script>
// Session closed — still refresh session list every 20s
setInterval(function() { location.reload(); }, 20000);
</script>
<?php endif; ?>

<style>
.chat-msg-visitor,
.chat-msg-agent {
    padding: 10px 12px;
    border-radius: 10px;
    max-width: 75%;
    word-break: break-word;
}
.chat-msg-visitor {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    align-self: flex-start;
}
.chat-msg-agent {
    background: var(--primary-light, #f0fdf4);
    border: 1px solid #bbf7d0;
    align-self: flex-end;
    margin-left: auto;
}
.chat-msg-meta {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: var(--gray-500);
    margin-bottom: 4px;
    gap: 12px;
}
.chat-msg-body {
    font-size: 13px;
    line-height: 1.5;
    color: var(--gray-900);
}
#chat-messages {
    display: flex;
    flex-direction: column;
}
</style>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
