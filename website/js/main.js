/* ============================================================
   Nalam Pulse — Main JS
   nalampulse.com | Plain vanilla JS, no frameworks
   ============================================================ */

(function () {
    'use strict';

    /* ===== NAVBAR SCROLL ===== */
    var navbar = document.getElementById('navbar');
    window.addEventListener('scroll', function () {
        if (window.scrollY > 20) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }, { passive: true });

    /* ===== MOBILE NAV TOGGLE ===== */
    var navToggle = document.getElementById('nav-toggle');
    var navLinks = document.getElementById('nav-links');

    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function () {
            navLinks.classList.toggle('open');
        });

        // close on link click
        navLinks.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                navLinks.classList.remove('open');
            });
        });
    }

    /* ===== SMOOTH SCROLL FOR ANCHOR LINKS ===== */
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var href = a.getAttribute('href');
            if (href === '#') return;
            var target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                var navH = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-h')) || 68;
                var top = target.getBoundingClientRect().top + window.scrollY - navH;
                window.scrollTo({ top: top, behavior: 'smooth' });
            }
        });
    });

    /* ===== SCROLL ANIMATIONS ===== */
    var animEls = document.querySelectorAll('[data-animate]');
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        animEls.forEach(function (el) {
            observer.observe(el);
        });
    } else {
        // fallback: show all
        animEls.forEach(function (el) { el.classList.add('visible'); });
    }

    /* ===== CURRENCY DETECTION & PRICING TOGGLE ===== */
    var isINR = false;
    var toggleBtn = document.getElementById('currency-toggle');
    var labelUSD = document.getElementById('label-usd');
    var labelINR = document.getElementById('label-inr');
    var currencySource = document.getElementById('currency-source');
    var usdPrices = document.querySelectorAll('.usd-price');
    var inrPrices = document.querySelectorAll('.inr-price');

    function setCurrency(toINR, sourceText) {
        isINR = toINR;

        if (toINR) {
            toggleBtn.classList.add('inr-active');
            labelINR.classList.add('active');
            labelUSD.classList.remove('active');
            usdPrices.forEach(function (el) { el.style.display = 'none'; });
            inrPrices.forEach(function (el) { el.style.display = ''; });
        } else {
            toggleBtn.classList.remove('inr-active');
            labelUSD.classList.add('active');
            labelINR.classList.remove('active');
            usdPrices.forEach(function (el) { el.style.display = ''; });
            inrPrices.forEach(function (el) { el.style.display = 'none'; });
        }

        if (currencySource && sourceText) {
            currencySource.textContent = sourceText;
        }
    }

    // IP-based auto detection
    fetch('https://ipapi.co/json/', { method: 'GET' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.country_code === 'IN') {
                setCurrency(true, 'Prices shown in INR based on your location.');
            } else {
                setCurrency(false, '');
            }
        })
        .catch(function () {
            setCurrency(false, ''); // default USD on error
        });

    // Manual toggle
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            setCurrency(!isINR, isINR ? '' : 'Showing INR pricing.');
        });
    }

    // Default state
    labelUSD.classList.add('active');

    /* ===== CHAT WIDGET ===== */
    var chatTrigger   = document.getElementById('chat-trigger');
    var chatPanel     = document.getElementById('chat-panel');
    var chatClose     = document.getElementById('chat-close');
    var chatForm      = document.getElementById('chat-form');
    var chatSubmit    = document.getElementById('chat-submit');
    var chatError     = document.getElementById('chat-error');
    var chatIntro     = document.getElementById('chat-intro');
    var chatLive      = document.getElementById('chat-live');
    var chatMessages  = document.getElementById('chat-messages');
    var chatInput     = document.getElementById('chat-input');
    var chatSendBtn   = document.getElementById('chat-send');
    var chatIconOpen  = document.querySelector('.chat-icon-open');
    var chatIconClose = document.querySelector('.chat-icon-close');
    var chatTriggerLabel = document.querySelector('.chat-trigger-label');

    var chatOpen  = false;
    var pollTimer = null;

    // Session state
    var sessionId = null;
    var token     = null;
    var lastMsgId = 0;

    var PORTAL = (typeof PORTAL_API !== 'undefined') ? PORTAL_API : '/portal/api';

    function loadSession() {
        try {
            var s = JSON.parse(sessionStorage.getItem('np_chat_session') || 'null');
            if (s && s.sessionId && s.token) {
                sessionId = s.sessionId; token = s.token; lastMsgId = s.lastMsgId || 0;
            }
        } catch(e) {}
    }
    function saveSession() {
        try { sessionStorage.setItem('np_chat_session', JSON.stringify({ sessionId: sessionId, token: token, lastMsgId: lastMsgId })); } catch(e) {}
    }

    function openChat() {
        chatOpen = true;
        chatPanel.classList.add('open');
        chatPanel.setAttribute('aria-hidden', 'false');
        if (chatIconOpen) chatIconOpen.style.display = 'none';
        if (chatIconClose) chatIconClose.style.display = '';
        if (chatTriggerLabel) chatTriggerLabel.textContent = 'Close';
        loadSession();
        if (sessionId && token) {
            showLiveChat();
            fetchHistory();
        }
    }

    function closeChat() {
        chatOpen = false;
        chatPanel.classList.remove('open');
        chatPanel.setAttribute('aria-hidden', 'true');
        if (chatIconOpen) chatIconOpen.style.display = '';
        if (chatIconClose) chatIconClose.style.display = 'none';
        if (chatTriggerLabel) chatTriggerLabel.textContent = 'Chat with us';
        stopPolling();
    }

    function showLiveChat() {
        chatIntro.style.display = 'none';
        chatLive.style.display  = 'flex';
        chatPanel.classList.add('live');
    }

    function appendBubble(senderType, senderName, body, msgId) {
        var isVisitor = senderType === 'visitor';
        var wrap = document.createElement('div');
        wrap.className = 'chat-bubble-wrap ' + (isVisitor ? 'from-visitor' : 'from-agent');
        if (!isVisitor) {
            var nameEl = document.createElement('div');
            nameEl.className = 'chat-bubble-sender';
            nameEl.textContent = senderName || 'Support';
            wrap.appendChild(nameEl);
        }
        var bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        bubble.textContent = body;
        wrap.appendChild(bubble);
        chatMessages.appendChild(wrap);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        if (msgId && msgId > lastMsgId) { lastMsgId = msgId; saveSession(); }
    }

    function fetchHistory() {
        fetch(PORTAL + '/chat-poll.php?session_id=' + sessionId + '&token=' + encodeURIComponent(token) + '&since=0')
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (res.messages) res.messages.forEach(function(m) { appendBubble(m.sender_type, m.sender_name, m.body, m.id); });
            if (res.session_status !== 'closed') startPolling();
        }).catch(function(){});
    }

    function poll() {
        if (!sessionId || !token) return;
        fetch(PORTAL + '/chat-poll.php?session_id=' + sessionId + '&token=' + encodeURIComponent(token) + '&since=' + lastMsgId)
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (res.messages) res.messages.forEach(function(m) {
                if (m.sender_type === 'agent') appendBubble(m.sender_type, m.sender_name, m.body, m.id);
                else if (m.id > lastMsgId) { lastMsgId = m.id; saveSession(); }
            });
            if (res.session_status === 'closed') {
                stopPolling();
                var closed = document.createElement('div');
                closed.style.cssText = 'text-align:center;padding:10px;font-size:12px;color:#9ca3af';
                closed.textContent = 'Session ended. Email us at hello@nalampulse.com';
                chatMessages.appendChild(closed);
                if (chatSendBtn) chatSendBtn.disabled = true;
                if (chatInput)  chatInput.disabled    = true;
            }
        }).catch(function(){});
    }

    function startPolling() { stopPolling(); pollTimer = setInterval(poll, 4000); }
    function stopPolling()  { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

    function sendMessage() {
        var msg = chatInput ? chatInput.value.trim() : '';
        if (!msg || !sessionId) return;
        chatInput.value = '';
        chatInput.style.height = '';
        appendBubble('visitor', 'You', msg, 0);
        fetch(PORTAL + '/chat-send.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: sessionId, token: token, body: msg })
        })
        .then(function(r){ return r.json(); })
        .then(function(res) { if (res.message_id && res.message_id > lastMsgId) { lastMsgId = res.message_id; saveSession(); } })
        .catch(function(){});
    }

    // expose for footer link
    window.openChat = openChat;

    if (chatTrigger) { chatTrigger.addEventListener('click', function () { chatOpen ? closeChat() : openChat(); }); }
    if (chatClose)   { chatClose.addEventListener('click', function () { closeChat(); }); }

    // Open chat from "Contact Sales" pricing button
    document.querySelectorAll('.chat-open-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) { e.preventDefault(); openChat(); });
    });

    // Open chat from nav contact link
    var navChatLink = document.querySelector('.nav-chat-link');
    if (navChatLink) {
        navChatLink.addEventListener('click', function (e) {
            e.preventDefault(); openChat();
            if (navLinks) navLinks.classList.remove('open');
        });
    }

    // Phase 1: Start chat — triggered by button click or Enter key
    function startChat() {
        var name  = document.getElementById('chat-name').value.trim();
        var email = document.getElementById('chat-email').value.trim();
        if (chatError) chatError.style.display = 'none';
        if (!name)  { showError('Name is required.'); return; }
        if (!email) { showError('Email is required.'); return; }
        if (chatSubmit) { chatSubmit.disabled = true; chatSubmit.textContent = 'Connecting\u2026'; }
        fetch(PORTAL + '/chat-init.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, email: email })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (chatSubmit) { chatSubmit.disabled = false; chatSubmit.textContent = 'Start Chat'; }
            if (res.error) { showError(res.error); return; }
            sessionId = res.session_id;
            token     = res.token;
            lastMsgId = 0;
            saveSession();
            chatMessages.innerHTML = '';
            showLiveChat();
            fetchHistory();
        })
        .catch(function() {
            if (chatSubmit) { chatSubmit.disabled = false; chatSubmit.textContent = 'Start Chat'; }
            showError('Could not connect. Please try again.');
        });
    }

    if (chatSubmit) { chatSubmit.addEventListener('click', startChat); }
    if (chatForm) {
        chatForm.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); startChat(); }
        });
    }

    function showError(msg) {
        if (chatError) { chatError.textContent = msg; chatError.style.display = 'block'; }
    }

    // Phase 2: Send message
    if (chatSendBtn) { chatSendBtn.addEventListener('click', sendMessage); }
    if (chatInput) {
        chatInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });
        chatInput.addEventListener('input', function() {
            this.style.height = '';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });
    }

    /* ===== BAR ANIMATION ON SCROLL ===== */
    var bars = document.querySelectorAll('.fvc-fill');
    if ('IntersectionObserver' in window) {
        var barObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.width = entry.target.style.width; // trigger CSS transition
                    barObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        bars.forEach(function (bar) {
            var targetWidth = bar.style.width;
            bar.style.width = '0';
            requestAnimationFrame(function () {
                setTimeout(function () { bar.style.width = targetWidth; }, 300);
            });
            barObserver.observe(bar);
        });
    }

    /* ===== LOGOS TRACK PAUSE ON HOVER ===== */
    var logosTrack = document.querySelector('.logos-track');
    if (logosTrack) {
        logosTrack.addEventListener('mouseenter', function () {
            logosTrack.style.animationPlayState = 'paused';
        });
        logosTrack.addEventListener('mouseleave', function () {
            logosTrack.style.animationPlayState = 'running';
        });
    }

})();
