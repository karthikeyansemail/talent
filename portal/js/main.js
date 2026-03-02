/**
 * Nalam Pulse Portal — main.js
 */

// Auto-dismiss alerts
document.querySelectorAll('.alert').forEach(function (el) {
    setTimeout(function () {
        el.style.transition = 'opacity 0.4s';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 400);
    }, 4000);
});

// Confirm on destructive actions
document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
        if (!confirm(el.dataset.confirm || 'Are you sure?')) {
            e.preventDefault();
        }
    });
});
