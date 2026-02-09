// Auto-dismiss flash messages
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 300);
        }, 5000);
    });

    // Close alert on click
    document.querySelectorAll('.alert-close').forEach(function(btn) {
        btn.addEventListener('click', function() {
            this.closest('.alert').remove();
        });
    });

    // Initialize tag inputs
    initTagInputs();
});

// Modal
function openModal(id) {
    document.getElementById(id).classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Click outside modal to close
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
});

// Tag Input
function initTagInputs() {
    document.querySelectorAll('.tag-input-wrapper').forEach(function(wrapper) {
        var input = wrapper.querySelector('input[type="text"]');
        var hiddenInput = wrapper.querySelector('input[type="hidden"]');
        if (!input || !hiddenInput) return;

        var tags = hiddenInput.value ? hiddenInput.value.split(',').filter(Boolean) : [];
        renderTags(wrapper, tags, hiddenInput);

        input.addEventListener('keydown', function(e) {
            if ((e.key === 'Enter' || e.key === ',') && this.value.trim()) {
                e.preventDefault();
                var val = this.value.trim().replace(/,/g, '');
                if (val && tags.indexOf(val) === -1) {
                    tags.push(val);
                    renderTags(wrapper, tags, hiddenInput);
                }
                this.value = '';
            }
            if (e.key === 'Backspace' && !this.value && tags.length) {
                tags.pop();
                renderTags(wrapper, tags, hiddenInput);
            }
        });

        wrapper.addEventListener('click', function() { input.focus(); });
    });
}

function renderTags(wrapper, tags, hiddenInput) {
    wrapper.querySelectorAll('.tag').forEach(function(t) { t.remove(); });
    var input = wrapper.querySelector('input[type="text"]');
    tags.forEach(function(tag) {
        var el = document.createElement('span');
        el.className = 'tag';
        el.innerHTML = tag + ' <span class="tag-remove" onclick="removeTag(this)">&times;</span>';
        wrapper.insertBefore(el, input);
    });
    hiddenInput.value = tags.join(',');
}

function removeTag(el) {
    var wrapper = el.closest('.tag-input-wrapper');
    var hiddenInput = wrapper.querySelector('input[type="hidden"]');
    el.closest('.tag').remove();
    var tags = [];
    wrapper.querySelectorAll('.tag').forEach(function(t) {
        tags.push(t.textContent.trim().replace('×', '').trim());
    });
    hiddenInput.value = tags.join(',');
}

// Confirm delete
function confirmDelete(formId, message) {
    if (confirm(message || 'Are you sure you want to delete this?')) {
        document.getElementById(formId).submit();
    }
}

// CSRF token for AJAX
function getCSRFToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// AJAX helper
function ajaxPost(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCSRFToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify(data),
    }).then(function(r) { return r.json(); });
}
