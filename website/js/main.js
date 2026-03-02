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
    var chatTrigger = document.getElementById('chat-trigger');
    var chatPanel = document.getElementById('chat-panel');
    var chatClose = document.getElementById('chat-close');
    var chatForm = document.getElementById('chat-form');
    var chatSubmit = document.getElementById('chat-submit');
    var chatSuccess = document.getElementById('chat-success');
    var chatError = document.getElementById('chat-error');
    var chatIconOpen = document.querySelector('.chat-icon-open');
    var chatIconClose = document.querySelector('.chat-icon-close');
    var chatTriggerLabel = document.querySelector('.chat-trigger-label');

    var chatOpen = false;

    function openChat() {
        chatOpen = true;
        chatPanel.classList.add('open');
        chatPanel.setAttribute('aria-hidden', 'false');
        if (chatIconOpen) chatIconOpen.style.display = 'none';
        if (chatIconClose) chatIconClose.style.display = '';
        if (chatTriggerLabel) chatTriggerLabel.textContent = 'Close';
    }

    function closeChat() {
        chatOpen = false;
        chatPanel.classList.remove('open');
        chatPanel.setAttribute('aria-hidden', 'true');
        if (chatIconOpen) chatIconOpen.style.display = '';
        if (chatIconClose) chatIconClose.style.display = 'none';
        if (chatTriggerLabel) chatTriggerLabel.textContent = 'Chat with us';
    }

    // expose for footer link
    window.openChat = openChat;

    if (chatTrigger) {
        chatTrigger.addEventListener('click', function () {
            chatOpen ? closeChat() : openChat();
        });
    }
    if (chatClose) {
        chatClose.addEventListener('click', function () { closeChat(); });
    }

    // Open chat from "Contact Sales" pricing button
    document.querySelectorAll('.chat-open-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openChat();
        });
    });

    // Open chat from nav contact link
    var navChatLink = document.querySelector('.nav-chat-link');
    if (navChatLink) {
        navChatLink.addEventListener('click', function (e) {
            e.preventDefault();
            openChat();
            navLinks.classList.remove('open');
        });
    }

    // Chat form submission
    if (chatForm) {
        chatForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var name = document.getElementById('chat-name').value.trim();
            var email = document.getElementById('chat-email').value.trim();
            var message = document.getElementById('chat-message').value.trim();

            if (!name || !email || !message) return;

            chatSubmit.disabled = true;
            chatSubmit.textContent = 'Sending...';

            fetch('api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: name, email: email, message: message })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    chatForm.style.display = 'none';
                    chatSuccess.style.display = 'block';
                    if (chatError) chatError.style.display = 'none';
                } else {
                    throw new Error(data.error || 'Failed');
                }
            })
            .catch(function () {
                if (chatError) chatError.style.display = 'block';
                chatSubmit.disabled = false;
                chatSubmit.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Send Message';
            });
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
