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
     ORDER BY COALESCE((SELECT MAX(created_at) FROM chat_messages WHERE session_id=s.id), s.created_at) DESC
     LIMIT {$perPage} OFFSET {$offset}"
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

$agentName = admin()['name'] ?? 'Support';

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
    <div style="display:flex;align-items:center;gap:12px">
        <div class="filter-count" id="session-count"><?= $totalRows ?> session<?= $totalRows !== 1 ? 's' : '' ?></div>
        <button id="sound-toggle" class="btn btn-sm btn-secondary" title="Toggle notification sounds" onclick="toggleSound(this)">🔔 Sound On</button>
    </div>
</div>

<!-- Three-column layout: sessions | chat | AI suggestions -->
<div style="display:flex;gap:16px;align-items:flex-start">

    <!-- ── Session list ─────────────────────────────────────────────────── -->
    <div class="card" style="flex:0 0 280px;min-width:0">
        <div class="card-header">
            <span>Sessions</span>
            <span id="new-session-badge" style="display:none;background:var(--danger);color:#fff;font-size:10px;font-weight:700;border-radius:10px;padding:2px 7px;margin-left:6px">NEW</span>
        </div>
        <div id="session-list" style="padding:0">
        <?php foreach ($sessions as $s): ?>
        <?php $isActive = $selectedId === (int)$s['id']; ?>
        <a href="?session=<?= $s['id'] ?><?= $filterSearch ? '&search=' . urlencode($filterSearch) : '' ?><?= $filterStatus ? '&status=' . urlencode($filterStatus) : '' ?>"
           class="chat-session-row <?= $isActive ? 'active' : '' ?>" data-sid="<?= $s['id'] ?>">
            <div style="display:flex;align-items:center;gap:6px">
                <div class="td-primary"><?= h($s['name']) ?></div>
                <span class="sess-status-badge" style="font-size:10px;border-radius:4px;padding:1px 5px;background:<?= $s['status']==='closed'?'#f1f5f9':'#dcfce7' ?>;color:<?= $s['status']==='closed'?'#64748b':'#166534' ?>">
                    <?= $s['status'] ?>
                </span>
            </div>
            <div class="td-secondary"><?= h($s['email']) ?></div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--gray-400);margin-top:2px">
                <span><?= time_ago($s['created_at']) ?></span>
                <span class="sess-msg-count"><?= $s['msg_count'] ?> msg<?= $s['msg_count'] !== '1' ? 's' : '' ?></span>
            </div>
        </a>
        <?php endforeach; ?>
        <?php if (empty($sessions)): ?>
        <div class="empty-row" style="padding:20px" id="empty-sessions">No chat sessions yet.</div>
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

    <!-- ── Messages panel ──────────────────────────────────────────────── -->
    <div class="card" style="flex:2;min-width:0;display:flex;flex-direction:column">
        <div class="card-header" style="gap:12px;flex-wrap:wrap">
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
            <form method="POST" action="<?= BASE ?>/api/chat-close.php" style="display:inline">
                <input type="hidden" name="session_id" value="<?= $selectedId ?>">
                <button type="submit" class="btn btn-sm btn-secondary" data-confirm="Close this chat session?">Close Session</button>
            </form>
            <?php else: ?>
            <span style="font-size:12px;background:#f1f5f9;color:#64748b;border-radius:6px;padding:3px 8px">Closed</span>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Messages -->
        <div id="chat-messages" style="padding:16px;display:flex;flex-direction:column;gap:10px;min-height:300px;max-height:460px;overflow-y:auto">
        <?php if ($currentSession && $currentMessages): ?>
            <?php foreach ($currentMessages as $m): ?>
            <?php $isVisitor = $m['sender_type'] === 'visitor'; ?>
            <div class="<?= $isVisitor ? 'chat-msg-visitor' : 'chat-msg-agent' ?>" data-id="<?= $m['id'] ?>">
                <div class="chat-msg-meta">
                    <strong><?= h($m['sender_name'] ?: ($isVisitor ? $currentSession['name'] : 'Support')) ?></strong>
                    <span><?= time_ago($m['created_at']) ?></span>
                </div>
                <div class="chat-msg-body"><?= nl2br(h($m['body'] ?? '')) ?></div>
                <?php if (!empty($m['attachment_url'])): ?>
                <div class="chat-attachment">
                    <?php if ($m['attachment_type'] === 'image'): ?>
                    <a href="<?= h($m['attachment_url']) ?>" target="_blank">
                        <img src="<?= h($m['attachment_url']) ?>" alt="Image" class="chat-attach-img">
                    </a>
                    <?php elseif ($m['attachment_type'] === 'video'): ?>
                    <video src="<?= h($m['attachment_url']) ?>" controls class="chat-attach-video"></video>
                    <?php else: ?>
                    <a href="<?= h($m['attachment_url']) ?>" target="_blank" class="chat-attach-file">📎 Download file</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php elseif ($currentSession): ?>
            <p class="text-muted">No messages in this session.</p>
        <?php else: ?>
            <p class="text-muted" style="text-align:center;padding:40px 0">← Select a session to view messages.</p>
        <?php endif; ?>
        </div>

        <!-- Reply form -->
        <?php if ($currentSession && $currentSession['status'] === 'open'): ?>
        <div style="padding:10px 14px;border-top:1px solid var(--border)">
            <div style="display:flex;gap:8px;align-items:flex-end">
                <textarea id="reply-input" class="form-control" rows="2" placeholder="Type your reply… (Enter to send, Shift+Enter for new line)" style="flex:1;resize:none"></textarea>
                <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0">
                    <button id="reply-btn" class="btn btn-primary btn-sm">Send</button>
                    <label class="btn btn-secondary btn-sm" style="cursor:pointer;margin:0" title="Attach image/video/file">
                        📎 <input type="file" id="attach-input" accept="image/*,video/*,.pdf" style="display:none">
                    </label>
                </div>
            </div>
            <div id="attach-preview" style="display:none;margin-top:8px;font-size:12px;color:var(--gray-600);display:flex;align-items:center;gap:8px">
                <span id="attach-name"></span>
                <button type="button" onclick="clearAttach()" style="background:none;border:none;cursor:pointer;color:var(--danger);font-size:14px">✕</button>
            </div>
        </div>
        <?php elseif ($currentSession): ?>
        <div style="padding:12px 16px;border-top:1px solid var(--border);color:var(--gray-400);font-size:13px;text-align:center">
            Session is closed — no new messages can be sent.
        </div>
        <?php endif; ?>
    </div>

    <!-- ── AI Suggestions panel ─────────────────────────────────────────── -->
    <?php if ($currentSession && $currentSession['status'] === 'open'): ?>
    <div class="card" style="flex:0 0 270px;min-width:0">
        <div class="card-header" style="gap:8px">
            <span style="font-size:13px">✨ AI Suggestions</span>
            <button id="btn-suggest" class="btn btn-sm btn-primary" style="margin-left:auto;font-size:11px" onclick="loadSuggestions()">Suggest</button>
        </div>
        <div id="suggestions-area" style="padding:12px;min-height:80px">
            <p style="font-size:12px;color:var(--gray-400);text-align:center;padding:16px 0">
                Click <strong>Suggest</strong> to get AI-powered reply ideas based on the conversation.
            </p>
        </div>
        <!-- Knowledge base shortcuts -->
        <div style="border-top:1px solid var(--border);padding:10px 12px">
            <div style="font-size:11px;font-weight:600;color:var(--gray-500);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Quick Inserts</div>
            <div style="display:flex;flex-direction:column;gap:4px" id="quick-inserts">
                <?php foreach ([
                    'Trial'     => 'You can start a free 14-day trial at app.nalampulse.com — no credit card required.',
                    'Pricing'   => 'Our Cloud plan starts at $49/month. Self-Hosted is $999 one-time. Would you like a full pricing breakdown?',
                    'Demo'      => 'I\'d be happy to schedule a live demo. Could you share your availability and timezone?',
                    'Docs'      => 'You can find detailed documentation at docs.nalampulse.com. Let me know if you need help finding something specific.',
                    'Escalate'  => 'I\'m escalating this to our technical team. You\'ll hear back within 24 hours. I\'ll keep you updated.',
                ] as $label => $text): ?>
                <button class="quick-insert-btn" onclick="insertReply(<?= json_encode($text) ?>)"><?= $label ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php if ($currentSession): ?>
<script>
(function() {
    'use strict';

    var sessionId  = <?= $selectedId ?>;
    var lastId     = <?= $currentMessages ? (int)end($currentMessages)['id'] : 0 ?>;
    var agentName  = <?= json_encode($agentName) ?>;
    var messages   = document.getElementById('chat-messages');
    var input      = document.getElementById('reply-input');
    var btn        = document.getElementById('reply-btn');
    var attachInput = document.getElementById('attach-input');
    var pendingAttach = null; // { url, type, filename }

    // ── Sound system ──────────────────────────────────────────────────────
    var soundEnabled = true;
    var audioCtx     = null;

    window.toggleSound = function(el) {
        soundEnabled = !soundEnabled;
        el.textContent = soundEnabled ? '🔔 Sound On' : '🔕 Sound Off';
    };

    function getCtx() {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        return audioCtx;
    }

    function playTones(tones) {
        if (!soundEnabled) return;
        try {
            var ctx = getCtx();
            var t   = ctx.currentTime;
            tones.forEach(function(tone) {
                var osc  = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain); gain.connect(ctx.destination);
                osc.type            = 'sine';
                osc.frequency.value = tone[0];
                gain.gain.setValueAtTime(tone[2] || 0.25, t + tone[1]);
                gain.gain.exponentialRampToValueAtTime(0.001, t + tone[1] + tone[3]);
                osc.start(t + tone[1]);
                osc.stop(t + tone[1] + tone[3] + 0.05);
            });
        } catch(e) {}
    }

    // Gentle two-tone ping — visitor message arrives
    function playMessageSound() {
        playTones([[880, 0, 0.25, 0.25], [1100, 0.18, 0.2, 0.22]]);
    }

    // ── Scroll to bottom ─────────────────────────────────────────────────
    function scrollBottom() { messages.scrollTop = messages.scrollHeight; }
    scrollBottom();

    // ── HTML escape ──────────────────────────────────────────────────────
    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Render attachment ─────────────────────────────────────────────────
    function attachHtml(url, type, filename) {
        if (!url) return '';
        if (type === 'image') {
            return '<div class="chat-attachment"><a href="' + esc(url) + '" target="_blank"><img src="' + esc(url) + '" class="chat-attach-img" alt="image"></a></div>';
        }
        if (type === 'video') {
            return '<div class="chat-attachment"><video src="' + esc(url) + '" controls class="chat-attach-video"></video></div>';
        }
        return '<div class="chat-attachment"><a href="' + esc(url) + '" target="_blank" class="chat-attach-file">📎 ' + esc(filename || 'File') + '</a></div>';
    }

    // ── Append message ────────────────────────────────────────────────────
    function appendMsg(senderType, senderName, body, msgId, attachUrl, attachType, attachFilename) {
        var isAgent = senderType === 'agent';
        var wrap    = document.createElement('div');
        wrap.className = isAgent ? 'chat-msg-agent' : 'chat-msg-visitor';
        if (msgId) wrap.setAttribute('data-id', msgId);
        var bodyHtml = body ? esc(body).replace(/\n/g, '<br>') : '';
        wrap.innerHTML =
            '<div class="chat-msg-meta"><strong>' + esc(senderName) + '</strong><span>just now</span></div>' +
            (bodyHtml ? '<div class="chat-msg-body">' + bodyHtml + '</div>' : '') +
            attachHtml(attachUrl, attachType, attachFilename);
        messages.appendChild(wrap);
        scrollBottom();
        if (msgId && msgId > lastId) lastId = msgId;
    }

    // ── File attachment ───────────────────────────────────────────────────
    window.clearAttach = function() {
        pendingAttach = null;
        if (attachInput) attachInput.value = '';
        var prev = document.getElementById('attach-preview');
        if (prev) prev.style.display = 'none';
    };

    if (attachInput) {
        attachInput.addEventListener('change', function() {
            var file = attachInput.files[0];
            if (!file) return;
            var fd = new FormData();
            fd.append('file', file);
            fd.append('session_id', sessionId);
            fd.append('_portal', '1');

            fetch('<?= BASE ?>/api/chat-upload.php', { method: 'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(res) {
                if (res.ok) {
                    pendingAttach = { url: res.url, type: res.attachment_type, filename: res.filename };
                    var prev = document.getElementById('attach-preview');
                    var nm   = document.getElementById('attach-name');
                    if (prev && nm) { nm.textContent = '📎 ' + res.filename; prev.style.display = 'flex'; }
                } else {
                    alert(res.error || 'Upload failed.');
                }
            })
            .catch(function(){ alert('Upload error.'); });
        });
    }

    // ── Send reply ────────────────────────────────────────────────────────
    function sendReply() {
        var msg = input ? input.value.trim() : '';
        if (!msg && !pendingAttach) return;
        if (btn) btn.disabled = true;
        var msgText   = msg;
        var attach    = pendingAttach;
        if (input) input.value = '';
        clearAttach();

        fetch('<?= BASE ?>/api/chat-reply.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id:      sessionId,
                body:            msgText,
                attachment_url:  attach ? attach.url  : null,
                attachment_type: attach ? attach.type : null,
            })
        })
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (btn) btn.disabled = false;
            if (res.ok) {
                appendMsg('agent', agentName, msgText, res.message_id, attach ? attach.url : null, attach ? attach.type : null, attach ? attach.filename : null);
            } else {
                alert(res.error || 'Failed to send.');
                if (input) input.value = msgText;
            }
        })
        .catch(function() {
            if (btn) btn.disabled = false;
            if (input) input.value = msgText;
            alert('Network error.');
        });
    }

    if (btn)   btn.addEventListener('click', sendReply);
    if (input) input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendReply(); }
    });

    // ── AI suggestions ────────────────────────────────────────────────────
    window.loadSuggestions = function() {
        var area = document.getElementById('suggestions-area');
        var sugBtn = document.getElementById('btn-suggest');
        if (!area) return;
        area.innerHTML = '<p style="font-size:12px;color:var(--gray-400);text-align:center;padding:16px 0">Thinking…</p>';
        if (sugBtn) sugBtn.disabled = true;

        fetch('<?= BASE ?>/api/chat-suggest.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: sessionId })
        })
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (sugBtn) sugBtn.disabled = false;
            if (res.error) { area.innerHTML = '<p style="font-size:12px;color:var(--danger);padding:10px">' + esc(res.error) + '</p>'; return; }
            var html = '';
            (res.suggestions || []).forEach(function(s) {
                html += '<button class="suggestion-btn" onclick="insertReply(' + JSON.stringify(s) + ')">' + esc(s) + '</button>';
            });
            area.innerHTML = html || '<p style="font-size:12px;color:var(--gray-400);padding:10px">No suggestions.</p>';
        })
        .catch(function() {
            if (sugBtn) sugBtn.disabled = false;
            area.innerHTML = '<p style="font-size:12px;color:var(--danger);padding:10px">Could not load suggestions.</p>';
        });
    };

    window.insertReply = function(text) {
        if (input) {
            input.value = text;
            input.focus();
        }
    };

    // ── Poll for new visitor messages ─────────────────────────────────────
    var autoSuggestTimer = null;

    setInterval(function() {
        fetch('<?= BASE ?>/api/chat-poll.php?session_id=' + sessionId + '&since=' + lastId + '&_admin=1')
        .then(function(r){ return r.json(); })
        .then(function(res) {
            var newVisitorMsg = false;
            if (res.messages && res.messages.length) {
                res.messages.forEach(function(m) {
                    if (m.sender_type === 'visitor') {
                        appendMsg(m.sender_type, m.sender_name || 'Visitor', m.body, m.id,
                            m.attachment_url, m.attachment_type, null);
                        playMessageSound();
                        newVisitorMsg = true;
                    } else if (m.id > lastId) {
                        lastId = m.id;
                    }
                });
            }
            // Auto-suggest after visitor message (debounced 1.5s)
            if (newVisitorMsg) {
                clearTimeout(autoSuggestTimer);
                autoSuggestTimer = setTimeout(loadSuggestions, 1500);
            }
            if (res.session_status === 'closed') location.reload();
        })
        .catch(function(){});
    }, 4000);

})();
</script>
<?php endif; ?>

<!-- Session list AJAX refresh (always active) -->
<script>
(function() {
    var knownIds   = [<?= implode(',', array_column($sessions, 'id')) ?>];
    var BASE       = '<?= BASE ?>';
    var search     = <?= json_encode($filterSearch) ?>;
    var status     = <?= json_encode($filterStatus) ?>;
    var selectedId = <?= $selectedId ?>;
    var soundEn    = function() { return window.soundEnabled !== false; };

    function playNewSessionSound() {
        if (!soundEn()) return;
        try {
            var ctx  = new (window.AudioContext || window.webkitAudioContext)();
            var t    = ctx.currentTime;
            [[523,0],[659,0.2],[784,0.4]].forEach(function(f) {
                var osc  = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain); gain.connect(ctx.destination);
                osc.frequency.value = f[0]; osc.type = 'sine';
                gain.gain.setValueAtTime(0.3, t + f[1]);
                gain.gain.exponentialRampToValueAtTime(0.001, t + f[1] + 0.35);
                osc.start(t + f[1]); osc.stop(t + f[1] + 0.4);
            });
        } catch(e){}
    }

    function timeAgoShort(dateStr) {
        var diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
        if (diff < 60)   return 'just now';
        if (diff < 3600) return Math.floor(diff/60) + 'm ago';
        if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
        return Math.floor(diff/86400) + 'd ago';
    }

    function refreshSessions() {
        var url = BASE + '/api/chat-sessions.php?search=' + encodeURIComponent(search) + '&status=' + encodeURIComponent(status);
        fetch(url)
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (!data.sessions) return;
            var newIds  = data.sessions.map(function(s){ return s.id; });
            var hasNew  = newIds.some(function(id){ return knownIds.indexOf(id) === -1; });

            if (hasNew) {
                playNewSessionSound();
                var badge = document.getElementById('new-session-badge');
                if (badge) badge.style.display = 'inline';
                setTimeout(function(){ if (badge) badge.style.display = 'none'; }, 5000);
            }

            // Rebuild session list DOM
            var list = document.getElementById('session-list');
            if (!list) return;

            var html = '';
            data.sessions.forEach(function(s) {
                var isActive = s.id === selectedId;
                var href = '?session=' + s.id
                    + (search ? '&search=' + encodeURIComponent(search) : '')
                    + (status ? '&status=' + encodeURIComponent(status) : '');
                var bg  = s.status === 'closed' ? '#f1f5f9' : '#dcfce7';
                var col = s.status === 'closed' ? '#64748b' : '#166634';
                var mc  = s.msg_count;
                html += '<a href="' + href + '" class="chat-session-row' + (isActive ? ' active' : '') + '" data-sid="' + s.id + '">'
                    + '<div style="display:flex;align-items:center;gap:6px">'
                    + '<div class="td-primary">' + escHtml(s.name) + '</div>'
                    + '<span style="font-size:10px;border-radius:4px;padding:1px 5px;background:' + bg + ';color:' + col + '">' + s.status + '</span>'
                    + '</div>'
                    + '<div class="td-secondary">' + escHtml(s.email) + '</div>'
                    + '<div style="display:flex;justify-content:space-between;font-size:11px;color:var(--gray-400);margin-top:2px">'
                    + '<span>' + timeAgoShort(s.last_msg_at || s.created_at) + '</span>'
                    + '<span>' + mc + ' msg' + (mc != 1 ? 's' : '') + '</span>'
                    + '</div></a>';
            });
            if (!data.sessions.length) {
                html = '<div class="empty-row" style="padding:20px">No chat sessions yet.</div>';
            }
            list.innerHTML = html;

            // Update count
            var cnt = document.getElementById('session-count');
            if (cnt) cnt.textContent = data.sessions.length + ' session' + (data.sessions.length !== 1 ? 's' : '');

            knownIds = newIds;
        })
        .catch(function(){});
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    setInterval(refreshSessions, 8000);
})();
</script>

<style>
/* ── Message bubbles ────────────────────────────────────────────────────── */
.chat-msg-visitor, .chat-msg-agent {
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
    background: #f0fdf4;
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
.chat-msg-body { font-size: 13px; line-height: 1.5; color: var(--gray-900); }
#chat-messages { display: flex; flex-direction: column; }

/* ── Attachments ────────────────────────────────────────────────────────── */
.chat-attachment { margin-top: 6px; }
.chat-attach-img {
    max-width: 240px;
    max-height: 200px;
    border-radius: 8px;
    display: block;
    cursor: pointer;
    border: 1px solid var(--border);
}
.chat-attach-video {
    max-width: 280px;
    border-radius: 8px;
    display: block;
}
.chat-attach-file {
    font-size: 12px;
    color: var(--primary);
    text-decoration: none;
    padding: 4px 8px;
    background: var(--gray-50);
    border: 1px solid var(--border);
    border-radius: 6px;
    display: inline-block;
}

/* ── AI suggestion buttons ──────────────────────────────────────────────── */
.suggestion-btn {
    display: block;
    width: 100%;
    text-align: left;
    background: var(--gray-50);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 12px;
    color: var(--gray-800);
    cursor: pointer;
    margin-bottom: 6px;
    line-height: 1.45;
    transition: background .15s, border-color .15s;
}
.suggestion-btn:hover { background: #eff6ff; border-color: var(--primary); color: var(--primary); }

/* ── Quick insert buttons ───────────────────────────────────────────────── */
.quick-insert-btn {
    display: block;
    width: 100%;
    text-align: left;
    background: none;
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 5px 9px;
    font-size: 11px;
    color: var(--gray-600);
    cursor: pointer;
    transition: background .12s;
}
.quick-insert-btn:hover { background: var(--gray-50); color: var(--primary); }
</style>

<?php include __DIR__ . '/includes/layout-end.php'; ?>
