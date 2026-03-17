/**
 * Nalam Pulse Chat Widget
 * Embed with: <script src="https://portal.nalampulse.com/portal/js/chat-widget.js" data-portal="https://portal.nalampulse.com/portal"></script>
 *
 * Optional attributes on the script tag:
 *   data-portal  — base URL of the portal (auto-detected if on same origin)
 *   data-brand   — chat header title (default: "Chat with us")
 *   data-color   — primary color hex (default: #4f46e5)
 */
(function () {
  'use strict';

  // --- Config ---
  var script   = document.currentScript || document.querySelector('script[data-portal]');
  var PORTAL   = (script && script.getAttribute('data-portal')) || '';
  var BRAND    = (script && script.getAttribute('data-brand'))  || 'Chat with us';
  var COLOR    = (script && script.getAttribute('data-color'))  || '#4f46e5';

  // State
  var sessionId = null;
  var token     = null;
  var lastMsgId = 0;
  var pollTimer = null;
  var open      = false;

  // --- Sound ---
  var audioCtx = null;
  function playAgentMessageSound() {
    try {
      if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      var ctx = audioCtx; var t = ctx.currentTime;
      [[1100, 0, 0.18, 0.22], [880, 0.2, 0.14, 0.3]].forEach(function(f) {
        var osc = ctx.createOscillator(); var g = ctx.createGain();
        osc.connect(g); g.connect(ctx.destination);
        osc.frequency.value = f[0]; osc.type = 'sine';
        g.gain.setValueAtTime(f[2], t + f[1]);
        g.gain.exponentialRampToValueAtTime(0.001, t + f[1] + f[3]);
        osc.start(t + f[1]); osc.stop(t + f[1] + f[3] + 0.05);
      });
    } catch(e) {}
  }

  // --- Browser notifications ---
  var notifEnabled = false;
  if ('Notification' in window) {
    if (Notification.permission === 'granted') {
      notifEnabled = true;
    } else if (Notification.permission !== 'denied') {
      Notification.requestPermission().then(function(p) { notifEnabled = p === 'granted'; });
    }
  }

  function showAgentNotif(name, body) {
    if (!notifEnabled || document.hasFocus()) return;
    try {
      var n = new Notification(name || 'Support', {
        body: body && body.length > 100 ? body.substring(0, 97) + '…' : (body || 'New message'),
        tag: 'np-chat-agent',
        requireInteraction: false
      });
      n.onclick = function() {
        window.focus();
        if (!open) openWidget();
        n.close();
      };
      setTimeout(function() { n.close(); }, 8000);
    } catch(e) {}
  }

  // --- Persist session across page loads ---
  function loadSession() {
    try {
      var s = JSON.parse(sessionStorage.getItem('np_chat') || 'null');
      if (s && s.sessionId && s.token) {
        sessionId = s.sessionId;
        token     = s.token;
        lastMsgId = s.lastMsgId || 0;
      }
    } catch (e) {}
  }

  function saveSession() {
    try {
      sessionStorage.setItem('np_chat', JSON.stringify({ sessionId: sessionId, token: token, lastMsgId: lastMsgId }));
    } catch (e) {}
  }

  // --- DOM helpers ---
  function el(tag, attrs, children) {
    var e = document.createElement(tag);
    if (attrs) Object.keys(attrs).forEach(function (k) {
      if (k === 'style') { Object.assign(e.style, attrs[k]); }
      else if (k.startsWith('on')) { e[k] = attrs[k]; }
      else { e.setAttribute(k, attrs[k]); }
    });
    if (typeof children === 'string') { e.textContent = children; }
    else if (Array.isArray(children)) { children.forEach(function (c) { if (c) e.appendChild(c); }); }
    return e;
  }

  function css(rules) {
    var s = document.createElement('style');
    s.textContent = rules;
    document.head.appendChild(s);
  }

  // --- Styles ---
  css([
    '#np-chat-btn{position:fixed;bottom:24px;right:24px;width:56px;height:56px;border-radius:50%;background:' + COLOR + ';color:#fff;border:none;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.25);display:flex;align-items:center;justify-content:center;z-index:9999;transition:transform .15s;}',
    '#np-chat-btn:hover{transform:scale(1.08);}',
    '#np-chat-bubble{position:fixed;bottom:92px;right:24px;width:340px;max-width:calc(100vw - 32px);height:480px;max-height:calc(100vh - 120px);background:#fff;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,.18);display:flex;flex-direction:column;z-index:9999;overflow:hidden;transition:opacity .2s,transform .2s;}',
    '#np-chat-bubble.np-hidden{opacity:0;transform:translateY(12px);pointer-events:none;}',
    '#np-chat-head{background:' + COLOR + ';color:#fff;padding:14px 16px;display:flex;align-items:center;gap:10px;flex-shrink:0;}',
    '#np-chat-head-title{font-weight:700;font-size:15px;flex:1;}',
    '#np-chat-head-close{background:none;border:none;color:#fff;cursor:pointer;font-size:20px;line-height:1;padding:0;}',
    '#np-chat-messages{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:8px;}',
    '.np-msg{max-width:80%;padding:8px 12px;border-radius:12px;font-size:13px;line-height:1.5;word-break:break-word;}',
    '.np-msg-visitor{align-self:flex-end;background:' + COLOR + ';color:#fff;border-bottom-right-radius:3px;}',
    '.np-msg-agent{align-self:flex-start;background:#f1f5f9;color:#1e293b;border-bottom-left-radius:3px;}',
    '.np-msg-name{font-size:10px;opacity:.65;margin-bottom:2px;}',
    '#np-chat-form{padding:10px 12px;border-top:1px solid #e2e8f0;display:flex;gap:8px;flex-shrink:0;}',
    '#np-chat-input{flex:1;border:1px solid #cbd5e1;border-radius:8px;padding:8px 10px;font-size:13px;resize:none;height:38px;font-family:inherit;outline:none;}',
    '#np-chat-input:focus{border-color:' + COLOR + ';}',
    '#np-chat-send{background:' + COLOR + ';color:#fff;border:none;border-radius:8px;padding:0 14px;cursor:pointer;font-size:18px;display:flex;align-items:center;flex-shrink:0;}',
    '#np-chat-intro{padding:20px;display:flex;flex-direction:column;gap:12px;}',
    '#np-chat-intro h3{margin:0;font-size:15px;color:#1e293b;}',
    '#np-chat-intro p{margin:0;font-size:13px;color:#64748b;}',
    '.np-field{display:flex;flex-direction:column;gap:4px;}',
    '.np-field label{font-size:12px;font-weight:600;color:#475569;}',
    '.np-field input{border:1px solid #cbd5e1;border-radius:8px;padding:8px 10px;font-size:13px;font-family:inherit;outline:none;}',
    '.np-field input:focus{border-color:' + COLOR + ';}',
    '#np-chat-start{background:' + COLOR + ';color:#fff;border:none;border-radius:8px;padding:10px;font-size:14px;font-weight:600;cursor:pointer;margin-top:4px;}',
    '#np-chat-start:disabled{opacity:.6;cursor:not-allowed;}',
    '#np-chat-err{color:#dc2626;font-size:12px;display:none;}',
    '#np-chat-closed{padding:16px;text-align:center;font-size:13px;color:#64748b;}',
  ].join('\n'));

  // --- Build DOM ---
  var btnIcon = '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';

  var chatBtn = el('button', { id: 'np-chat-btn', innerHTML: btnIcon, title: 'Chat with us' });

  // Intro form (name + email)
  var introNameInput  = el('input', { type: 'text',  placeholder: 'Your name',  id: 'np-name-input',  autocomplete: 'name' });
  var introEmailInput = el('input', { type: 'email', placeholder: 'your@email.com', id: 'np-email-input', autocomplete: 'email' });
  var introErr        = el('div',   { id: 'np-chat-err' });
  var introStartBtn   = el('button', { id: 'np-chat-start' }, 'Start chatting');
  var introPane       = el('div', { id: 'np-chat-intro' }, [
    el('h3', {}, BRAND),
    el('p',  {}, 'Enter your details to start chatting with our team.'),
    el('div', { 'class': 'np-field' }, [ el('label', {}, 'Name'), introNameInput ]),
    el('div', { 'class': 'np-field' }, [ el('label', {}, 'Email'), introEmailInput ]),
    introErr,
    introStartBtn,
  ]);

  // Messages pane
  var messagesPane = el('div', { id: 'np-chat-messages' });

  // Input form
  var chatInput = el('textarea', { id: 'np-chat-input', placeholder: 'Type a message…', rows: '1' });
  var sendBtn   = el('button', { id: 'np-chat-send', innerHTML: '&#10148;', title: 'Send' });
  var chatForm  = el('div', { id: 'np-chat-form' }, [ chatInput, sendBtn ]);

  // Closed notice
  var closedNote = el('div', { id: 'np-chat-closed' }, 'This chat session has been closed.');

  var chatHead = el('div', { id: 'np-chat-head' }, [
    el('div', { id: 'np-chat-head-title' }, BRAND),
    el('button', { id: 'np-chat-head-close', title: 'Close' }, '×'),
  ]);

  var bubble = el('div', { id: 'np-chat-bubble', 'class': 'np-hidden' }, [ chatHead ]);

  document.body.appendChild(chatBtn);
  document.body.appendChild(bubble);

  // --- State helpers ---
  function showIntro() {
    clearChildren(bubble, chatHead);
    bubble.appendChild(introPane);
  }

  function showChat(sessionClosed) {
    clearChildren(bubble, chatHead);
    bubble.appendChild(messagesPane);
    if (sessionClosed) {
      bubble.appendChild(closedNote);
    } else {
      bubble.appendChild(chatForm);
    }
  }

  function clearChildren(parent, keepEl) {
    while (parent.lastChild && parent.lastChild !== keepEl) {
      parent.removeChild(parent.lastChild);
    }
  }

  // --- Message rendering ---
  function appendMessage(msg) {
    var isVisitor = msg.sender_type === 'visitor';
    var wrap = el('div', {});
    if (!isVisitor) {
      wrap.appendChild(el('div', { 'class': 'np-msg-name' }, msg.sender_name || 'Support'));
    }
    var bubble2 = el('div', { 'class': 'np-msg ' + (isVisitor ? 'np-msg-visitor' : 'np-msg-agent') });
    bubble2.textContent = msg.body;
    wrap.appendChild(bubble2);
    messagesPane.appendChild(wrap);
    messagesPane.scrollTop = messagesPane.scrollHeight;

    if (msg.id && msg.id > lastMsgId) {
      lastMsgId = msg.id;
      saveSession();
    }
  }

  // --- API calls ---
  function apiPost(path, data, cb) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', PORTAL + path);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onload = function () {
      try { cb(null, JSON.parse(xhr.responseText)); }
      catch (e) { cb(new Error('Parse error')); }
    };
    xhr.onerror = function () { cb(new Error('Network error')); };
    xhr.send(JSON.stringify(data));
  }

  function apiGet(path, cb) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', PORTAL + path);
    xhr.onload = function () {
      try { cb(null, JSON.parse(xhr.responseText)); }
      catch (e) { cb(new Error('Parse error')); }
    };
    xhr.onerror = function () { cb(new Error('Network error')); };
    xhr.send();
  }

  // --- Init session ---
  function initSession(name, email, cb) {
    apiPost('/api/chat-init.php', { name: name, email: email }, function (err, res) {
      if (err || res.error) return cb(res ? res.error : 'Network error');
      sessionId = res.session_id;
      token     = res.token;
      lastMsgId = 0;
      saveSession();
      cb(null);
    });
  }

  // --- Load history ---
  function loadHistory() {
    apiGet('/api/chat-poll.php?session_id=' + sessionId + '&token=' + encodeURIComponent(token) + '&since=0', function (err, res) {
      if (err || !res.messages) return;
      res.messages.forEach(appendMessage);
      var closed = res.session_status === 'closed';
      showChat(closed);
      if (!closed) startPolling();
    });
  }

  // --- Polling ---
  function poll() {
    if (!sessionId || !token) return;
    apiGet('/api/chat-poll.php?session_id=' + sessionId + '&token=' + encodeURIComponent(token) + '&since=' + lastMsgId, function (err, res) {
      if (err || !res) return;
      if (res.messages && res.messages.length) {
        var hadAgentMsg = false;
        res.messages.forEach(function(m) {
          appendMessage(m);
          if (m.sender_type === 'agent') {
            hadAgentMsg = true;
            showAgentNotif(m.sender_name, m.body);
          }
        });
        if (hadAgentMsg) playAgentMessageSound();
      }
      if (res.session_status === 'closed') {
        stopPolling();
        showChat(true);
      }
    });
  }

  function startPolling() {
    stopPolling();
    pollTimer = setInterval(poll, 4000);
  }

  function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
  }

  // --- Send ---
  function sendMessage() {
    var msg = chatInput.value.trim();
    if (!msg || !sessionId) return;
    chatInput.value = '';
    chatInput.style.height = '38px';
    // Optimistic render
    appendMessage({ id: 0, sender_type: 'visitor', sender_name: 'You', body: msg });
    apiPost('/api/chat-send.php', { session_id: sessionId, token: token, body: msg }, function (err, res) {
      if (!err && res && res.message_id && res.message_id > lastMsgId) {
        lastMsgId = res.message_id;
        saveSession();
      }
    });
  }

  // --- Toggle ---
  function openWidget() {
    open = true;
    bubble.classList.remove('np-hidden');
    loadSession();
    if (sessionId && token) {
      showChat(false); // will be replaced by loadHistory
      loadHistory();
    } else {
      showIntro();
    }
  }

  function closeWidget() {
    open = false;
    bubble.classList.add('np-hidden');
    stopPolling();
  }

  // --- Event listeners ---
  chatBtn.onclick = function () { open ? closeWidget() : openWidget(); };
  document.getElementById('np-chat-head-close').onclick = closeWidget;

  introStartBtn.onclick = function () {
    var name  = introNameInput.value.trim();
    var email = introEmailInput.value.trim();
    introErr.style.display = 'none';
    if (!name)  { introErr.textContent = 'Name is required.'; introErr.style.display = 'block'; return; }
    if (!email) { introErr.textContent = 'Email is required.'; introErr.style.display = 'block'; return; }

    introStartBtn.disabled = true;
    introStartBtn.textContent = 'Connecting…';
    initSession(name, email, function (err) {
      introStartBtn.disabled = false;
      introStartBtn.textContent = 'Start chatting';
      if (err) { introErr.textContent = err || 'Could not start chat.'; introErr.style.display = 'block'; return; }
      messagesPane.innerHTML = '';
      loadHistory();
    });
  };

  introNameInput.onkeydown = introEmailInput.onkeydown = function (e) {
    if (e.key === 'Enter') introStartBtn.click();
  };

  sendBtn.onclick = sendMessage;
  chatInput.onkeydown = function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  };

  // Auto-resize textarea
  chatInput.oninput = function () {
    this.style.height = '38px';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
  };

})();
