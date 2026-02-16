// Auto-dismiss flash messages
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        // Skip alerts inside hidden containers (used as dynamic error templates)
        if (alert.closest('.hidden')) return;
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

    // Initialize custom dropdowns
    initCustomDropdowns();

    // Initialize tabs
    initTabs();

    // Initialize AI auto-fill uploads
    initJobParserUpload();
    initCandidateParserUpload();
    initBulkResumeUpload();
    initProjectParserUpload();

    // Initialize sprint spreadsheet upload
    initSprintSheetUpload();

    // Initialize scoring rules weight sliders
    initWeightSliders();

    // Initialize star rating picker
    initStarRatingPicker();

    // Initialize candidate search typeahead (job show page)
    initCandidateSearchTypeahead();
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

// ============================================================
// AI Auto-fill: Job Description Parser
// ============================================================

function initJobParserUpload() {
    var area = document.getElementById('jdUploadArea');
    var fileInput = document.getElementById('jdFileInput');
    if (!area || !fileInput) return;

    var contentEl = document.getElementById('jdUploadContent');
    var loadingEl = document.getElementById('jdUploadLoading');
    var successEl = document.getElementById('jdUploadSuccess');
    var errorWrap = document.getElementById('jdUploadError');
    var errorText = document.getElementById('jdErrorText');

    area.addEventListener('click', function() {
        if (!area.classList.contains('loading') && !area.classList.contains('success')) {
            fileInput.click();
        }
    });

    area.addEventListener('dragover', function(e) { e.preventDefault(); area.classList.add('dragover'); });
    area.addEventListener('dragleave', function() { area.classList.remove('dragover'); });
    area.addEventListener('drop', function(e) {
        e.preventDefault();
        area.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            handleJobFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length) handleJobFile(this.files[0]);
    });

    function handleJobFile(file) {
        var ext = file.name.split('.').pop().toLowerCase();
        if (ext !== 'pdf' && ext !== 'docx') {
            showError('Please upload a PDF or DOCX file.');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            showError('File is too large. Maximum size is 10MB.');
            return;
        }

        showLoading();

        var formData = new FormData();
        formData.append('document', file);

        fetch(area.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCSRFToken(),
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
        .then(function(res) {
            if (!res.ok) {
                showError(res.data.error || 'Failed to parse document.');
                return;
            }
            fillJobForm(res.data);
            showSuccess();
        })
        .catch(function() {
            showError('Network error. Please try again.');
        });
    }

    function showLoading() {
        contentEl.classList.add('hidden');
        loadingEl.classList.remove('hidden');
        successEl.classList.add('hidden');
        errorWrap.classList.add('hidden');
        area.classList.add('loading');
        area.classList.remove('success');
    }

    function showSuccess() {
        contentEl.classList.add('hidden');
        loadingEl.classList.add('hidden');
        successEl.classList.remove('hidden');
        errorWrap.classList.add('hidden');
        area.classList.remove('loading');
        area.classList.add('success');
    }

    function showError(msg) {
        contentEl.classList.remove('hidden');
        loadingEl.classList.add('hidden');
        successEl.classList.add('hidden');
        errorWrap.classList.remove('hidden');
        errorText.textContent = msg;
        area.classList.remove('loading', 'success');
    }
}

function fillJobForm(data) {
    if (data.title) setField('[name="title"]', data.title);
    if (data.description) setField('[name="description"]', data.description);
    if (data.requirements) setField('[name="requirements"]', data.requirements);
    if (data.min_experience !== undefined) setField('[name="min_experience"]', data.min_experience);
    if (data.max_experience !== undefined) setField('[name="max_experience"]', data.max_experience);
    if (data.location) setField('[name="location"]', data.location);
    if (data.salary_min) setField('[name="salary_min"]', data.salary_min);
    if (data.salary_max) setField('[name="salary_max"]', data.salary_max);

    if (data.employment_type) setSelectValue('[name="employment_type"]', data.employment_type);
    if (data.required_skills && data.required_skills.length) setTagInput('[name="required_skills"]', data.required_skills);
    if (data.nice_to_have_skills && data.nice_to_have_skills.length) setTagInput('[name="nice_to_have_skills"]', data.nice_to_have_skills);
}

// ============================================================
// AI Auto-fill: Resume Profile Parser
// ============================================================

function initCandidateParserUpload() {
    var area = document.getElementById('resumeUploadArea');
    var fileInput = document.getElementById('resumeFileInput');
    if (!area || !fileInput) return;

    var contentEl = document.getElementById('resumeUploadContent');
    var loadingEl = document.getElementById('resumeUploadLoading');
    var successEl = document.getElementById('resumeUploadSuccess');
    var errorWrap = document.getElementById('resumeUploadError');
    var errorText = document.getElementById('resumeErrorText');

    area.addEventListener('click', function() {
        if (!area.classList.contains('loading') && !area.classList.contains('success')) {
            fileInput.click();
        }
    });

    area.addEventListener('dragover', function(e) { e.preventDefault(); area.classList.add('dragover'); });
    area.addEventListener('dragleave', function() { area.classList.remove('dragover'); });
    area.addEventListener('drop', function(e) {
        e.preventDefault();
        area.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            handleResumeFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length) handleResumeFile(this.files[0]);
    });

    function handleResumeFile(file) {
        var ext = file.name.split('.').pop().toLowerCase();
        if (ext !== 'pdf' && ext !== 'docx') {
            showError('Please upload a PDF or DOCX file.');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            showError('File is too large. Maximum size is 10MB.');
            return;
        }

        showLoading();

        var formData = new FormData();
        formData.append('resume', file);

        fetch(area.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCSRFToken(),
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
        .then(function(res) {
            if (!res.ok) {
                showError(res.data.error || 'Failed to parse resume.');
                return;
            }
            fillCandidateForm(res.data);
            showSuccess();
        })
        .catch(function() {
            showError('Network error. Please try again.');
        });
    }

    function showLoading() {
        contentEl.classList.add('hidden');
        loadingEl.classList.remove('hidden');
        successEl.classList.add('hidden');
        errorWrap.classList.add('hidden');
        area.classList.add('loading');
        area.classList.remove('success');
    }

    function showSuccess() {
        contentEl.classList.add('hidden');
        loadingEl.classList.add('hidden');
        successEl.classList.remove('hidden');
        errorWrap.classList.add('hidden');
        area.classList.remove('loading');
        area.classList.add('success');
    }

    function showError(msg) {
        contentEl.classList.remove('hidden');
        loadingEl.classList.add('hidden');
        successEl.classList.add('hidden');
        errorWrap.classList.remove('hidden');
        errorText.textContent = msg;
        area.classList.remove('loading', 'success');
    }
}

function fillCandidateForm(data) {
    if (data.first_name) setField('[name="first_name"]', data.first_name);
    if (data.last_name) setField('[name="last_name"]', data.last_name);
    if (data.email) setField('[name="email"]', data.email);
    if (data.phone) setField('[name="phone"]', data.phone);
    if (data.current_company) setField('[name="current_company"]', data.current_company);
    if (data.current_title) setField('[name="current_title"]', data.current_title);
    if (data.experience_years !== null && data.experience_years !== undefined) {
        setField('[name="experience_years"]', data.experience_years);
    }

    // Build notes from AI summary + skills
    var notesParts = [];
    if (data.summary) notesParts.push(data.summary);
    if (data.skills && data.skills.length) {
        notesParts.push('Skills: ' + data.skills.join(', '));
    }
    if (notesParts.length) {
        setField('[name="notes"]', notesParts.join('\n\n'));
    }

    // Store temp file info in hidden fields for resume creation on form submit
    if (data._temp_file_path) {
        setOrCreateHidden('_temp_file_path', data._temp_file_path);
        setOrCreateHidden('_temp_file_name', data._temp_file_name);
        setOrCreateHidden('_temp_file_type', data._temp_file_type);
        setOrCreateHidden('_extracted_text', data._extracted_text);
    }
}

// ============================================================
// Bulk Resume Upload
// ============================================================

function initBulkResumeUpload() {
    var area = document.getElementById('bulkUploadArea');
    var fileInput = document.getElementById('bulkFileInput');
    var form = document.getElementById('bulkUploadForm');
    if (!area || !fileInput || !form) return;

    var fileListEl = document.getElementById('bulkFileList');
    var fileCountEl = document.getElementById('bulkFileCount');
    var fileCountText = document.getElementById('bulkFileCountText');
    var submitBtn = document.getElementById('bulkSubmitBtn');
    var selectedFiles = [];

    area.addEventListener('click', function() { fileInput.click(); });

    area.addEventListener('dragover', function(e) { e.preventDefault(); area.classList.add('dragover'); });
    area.addEventListener('dragleave', function() { area.classList.remove('dragover'); });
    area.addEventListener('drop', function(e) {
        e.preventDefault();
        area.classList.remove('dragover');
        addFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', function() {
        addFiles(this.files);
        this.value = '';
    });

    function addFiles(fileList) {
        for (var i = 0; i < fileList.length; i++) {
            var file = fileList[i];
            var ext = file.name.split('.').pop().toLowerCase();
            if (ext !== 'pdf' && ext !== 'docx') continue;
            if (file.size > 10 * 1024 * 1024) continue;
            if (selectedFiles.length >= 20) break;
            // Avoid duplicates by name
            var exists = selectedFiles.some(function(f) { return f.name === file.name; });
            if (!exists) selectedFiles.push(file);
        }
        renderFileList();
    }

    function removeFile(index) {
        selectedFiles.splice(index, 1);
        renderFileList();
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function renderFileList() {
        if (selectedFiles.length === 0) {
            fileListEl.style.display = 'none';
            fileCountEl.style.display = 'none';
            submitBtn.disabled = true;
            return;
        }

        fileListEl.style.display = 'block';
        fileCountEl.style.display = 'block';
        submitBtn.disabled = false;
        fileCountText.textContent = selectedFiles.length + ' file' + (selectedFiles.length > 1 ? 's' : '') + ' selected';

        var html = '';
        selectedFiles.forEach(function(file, idx) {
            var ext = file.name.split('.').pop().toUpperCase();
            html += '<div class="bulk-file-item">' +
                '<span class="bulk-file-icon">' + ext + '</span>' +
                '<span class="bulk-file-name">' + escapeHtml(file.name) + '</span>' +
                '<span class="bulk-file-size">' + formatSize(file.size) + '</span>' +
                '<button type="button" class="bulk-file-remove" data-index="' + idx + '" title="Remove">&times;</button>' +
                '</div>';
        });
        fileListEl.innerHTML = html;

        fileListEl.querySelectorAll('.bulk-file-remove').forEach(function(btn) {
            btn.addEventListener('click', function() {
                removeFile(parseInt(this.dataset.index));
            });
        });
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    form.addEventListener('submit', function(e) {
        if (selectedFiles.length === 0) {
            e.preventDefault();
            return;
        }

        // Build FormData with selected files
        var formData = new FormData(form);
        // Remove the original file input entries (empty)
        formData.delete('resumes[]');
        selectedFiles.forEach(function(file) {
            formData.append('resumes[]', file);
        });

        // Submit via fetch to handle the multipart form properly
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:8px"></span> Processing resumes...';

        fetch(form.action, {
            method: 'POST',
            headers: { 'Accept': 'text/html' },
            body: formData,
        }).then(function(response) {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                return response.text().then(function(html) {
                    document.open();
                    document.write(html);
                    document.close();
                });
            }
        }).catch(function() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Upload & Create Candidates';
            alert('Network error. Please try again.');
        });
    });
}

// ============================================================
// Candidate Search Typeahead (Job Show → Add Application Modal)
// ============================================================

function initCandidateSearchTypeahead() {
    var searchInput = document.getElementById('candidateSearchInput');
    if (!searchInput) return;

    var resultsEl = document.getElementById('candidateSearchResults');
    var selectedCard = document.getElementById('selectedCandidateCard');
    var selectedNameEl = document.getElementById('selectedCandidateName');
    var selectedMetaEl = document.getElementById('selectedCandidateMeta');
    var candidateIdInput = document.getElementById('selectedCandidateId');
    var resumeIdInput = document.getElementById('selectedResumeId');
    var resumeGroup = document.getElementById('resumeSelectGroup');
    var resumeSelect = document.getElementById('resumeSelect');
    var searchGroup = document.getElementById('candidateSearchGroup');
    var submitBtn = document.getElementById('addApplicationSubmit');
    var clearBtn = document.getElementById('clearCandidateBtn');

    var debounceTimer = null;

    searchInput.addEventListener('input', function() {
        var q = this.value.trim();
        clearTimeout(debounceTimer);
        if (q.length < 2) {
            resultsEl.innerHTML = '';
            resultsEl.style.display = 'none';
            return;
        }
        debounceTimer = setTimeout(function() { searchCandidates(q); }, 300);
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            resultsEl.innerHTML = '';
            resultsEl.style.display = 'none';
        }
    });

    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.candidate-search-wrap')) {
            resultsEl.style.display = 'none';
        }
    });

    function searchCandidates(q) {
        fetch(searchInput.dataset.url + '?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCSRFToken() }
        })
        .then(function(r) { return r.json(); })
        .then(function(candidates) {
            if (!candidates.length) {
                resultsEl.innerHTML = '<div class="candidate-search-empty">No candidates found</div>';
                resultsEl.style.display = 'block';
                return;
            }
            var html = '';
            candidates.forEach(function(c) {
                var meta = [];
                if (c.email) meta.push(c.email);
                if (c.title) meta.push(c.title);
                if (c.company) meta.push(c.company);
                if (c.experience) meta.push(c.experience + ' yrs');
                html += '<div class="candidate-search-item" data-candidate=\'' + JSON.stringify(c).replace(/'/g, '&#39;') + '\'>'
                    + '<div class="candidate-search-name">' + escapeHtml(c.name) + '</div>'
                    + '<div class="candidate-search-meta">' + escapeHtml(meta.join(' · ')) + '</div>'
                    + '</div>';
            });
            resultsEl.innerHTML = html;
            resultsEl.style.display = 'block';

            resultsEl.querySelectorAll('.candidate-search-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    selectCandidate(JSON.parse(this.dataset.candidate));
                });
            });
        })
        .catch(function() {
            resultsEl.innerHTML = '<div class="candidate-search-empty">Search failed</div>';
            resultsEl.style.display = 'block';
        });
    }

    function selectCandidate(c) {
        resultsEl.style.display = 'none';
        searchGroup.style.display = 'none';
        candidateIdInput.value = c.id;

        // Build display info
        var meta = [];
        if (c.email) meta.push(c.email);
        if (c.title) meta.push(c.title);
        if (c.company) meta.push(c.company);
        if (c.experience) meta.push(c.experience + ' yrs exp.');

        var resumeCount = (c.resumes && c.resumes.length) ? c.resumes.length : 0;
        meta.push(resumeCount + ' resume' + (resumeCount !== 1 ? 's' : ''));

        selectedNameEl.textContent = c.name;
        selectedMetaEl.textContent = meta.join(' · ');
        selectedCard.style.display = 'flex';

        // Auto-select resume: hide dropdown if 0 or 1, show only if multiple
        if (resumeCount === 1) {
            // Auto-select the only resume — no dropdown needed
            resumeIdInput.value = c.resumes[0].id;
            resumeGroup.style.display = 'none';
        } else if (resumeCount > 1) {
            // Multiple resumes — show picker
            resumeSelect.innerHTML = '';
            c.resumes.forEach(function(r) {
                var opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.name;
                resumeSelect.appendChild(opt);
            });
            resumeSelect.selectedIndex = c.resumes.length - 1;
            resumeIdInput.value = resumeSelect.value;
            resumeSelect.onchange = function() { resumeIdInput.value = this.value; };
            resumeGroup.style.display = 'block';
        } else {
            // No resumes
            resumeIdInput.value = '';
            resumeGroup.style.display = 'none';
        }

        submitBtn.disabled = false;
    }

    clearBtn.addEventListener('click', function() {
        searchGroup.style.display = 'block';
        selectedCard.style.display = 'none';
        candidateIdInput.value = '';
        resumeIdInput.value = '';
        resumeGroup.style.display = 'none';
        submitBtn.disabled = true;
        searchInput.value = '';
        searchInput.focus();
    });

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

// ============================================================
// AI Auto-fill: Project Requirements Parser
// ============================================================

function initProjectParserUpload() {
    var area = document.getElementById('projectUploadArea');
    var fileInput = document.getElementById('projectFileInput');
    if (!area || !fileInput) return;

    var contentEl = document.getElementById('projectUploadContent');
    var loadingEl = document.getElementById('projectUploadLoading');
    var successEl = document.getElementById('projectUploadSuccess');
    var errorWrap = document.getElementById('projectUploadError');
    var errorText = document.getElementById('projectErrorText');

    area.addEventListener('click', function() {
        if (!area.classList.contains('loading') && !area.classList.contains('success')) {
            fileInput.click();
        }
    });

    area.addEventListener('dragover', function(e) { e.preventDefault(); area.classList.add('dragover'); });
    area.addEventListener('dragleave', function() { area.classList.remove('dragover'); });
    area.addEventListener('drop', function(e) {
        e.preventDefault();
        area.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            handleProjectFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length) handleProjectFile(this.files[0]);
    });

    function handleProjectFile(file) {
        var ext = file.name.split('.').pop().toLowerCase();
        if (ext !== 'pdf' && ext !== 'docx') {
            showError('Please upload a PDF or DOCX file.');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            showError('File is too large. Maximum size is 10MB.');
            return;
        }

        showLoading();

        var formData = new FormData();
        formData.append('document', file);

        fetch(area.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCSRFToken(),
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
        .then(function(res) {
            if (!res.ok) {
                showError(res.data.error || 'Failed to parse document.');
                return;
            }
            fillProjectForm(res.data);
            showSuccess();
        })
        .catch(function() {
            showError('Network error. Please try again.');
        });
    }

    function showLoading() {
        contentEl.classList.add('hidden');
        loadingEl.classList.remove('hidden');
        successEl.classList.add('hidden');
        errorWrap.classList.add('hidden');
        area.classList.add('loading');
        area.classList.remove('success');
    }

    function showSuccess() {
        contentEl.classList.add('hidden');
        loadingEl.classList.add('hidden');
        successEl.classList.remove('hidden');
        errorWrap.classList.add('hidden');
        area.classList.remove('loading');
        area.classList.add('success');
    }

    function showError(msg) {
        contentEl.classList.remove('hidden');
        loadingEl.classList.add('hidden');
        successEl.classList.add('hidden');
        errorWrap.classList.remove('hidden');
        errorText.textContent = msg;
        area.classList.remove('loading', 'success');
    }
}

function fillProjectForm(data) {
    if (data.name) setField('[name="name"]', data.name);
    if (data.description) setField('[name="description"]', data.description);
    if (data.domain_context) setField('[name="domain_context"]', data.domain_context);
    if (data.start_date) setField('[name="start_date"]', data.start_date);
    if (data.end_date) setField('[name="end_date"]', data.end_date);

    if (data.complexity_level) setSelectValue('[name="complexity_level"]', data.complexity_level);
    if (data.required_skills && data.required_skills.length) setTagInput('[name="required_skills"]', data.required_skills);
    if (data.required_technologies && data.required_technologies.length) setTagInput('[name="required_technologies"]', data.required_technologies);
}

// ============================================================
// AI Auto-fill: Shared Helpers
// ============================================================

function setField(selector, value) {
    var el = document.querySelector(selector);
    if (el) el.value = value;
}

function setSelectValue(selector, value) {
    var sel = document.querySelector(selector);
    if (!sel) return;
    // Set the native select
    for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === value) {
            sel.selectedIndex = i;
            break;
        }
    }
    // Update custom dropdown if present
    var wrapper = sel.closest('.custom-dropdown');
    if (wrapper) {
        var trigger = wrapper.querySelector('.dropdown-trigger-text');
        var options = wrapper.querySelectorAll('.dropdown-option');
        if (trigger) {
            options.forEach(function(opt) {
                if (opt.dataset.value === value) {
                    trigger.textContent = opt.textContent;
                    trigger.classList.remove('placeholder');
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });
        }
    }
}

function setTagInput(hiddenSelector, values) {
    var hidden = document.querySelector(hiddenSelector);
    if (!hidden) return;
    hidden.value = values.join(',');
    var wrapper = hidden.closest('.tag-input-wrapper');
    if (wrapper) {
        // Remove existing tags
        wrapper.querySelectorAll('.tag').forEach(function(t) { t.remove(); });
        // Re-render
        var input = wrapper.querySelector('input[type="text"]');
        values.forEach(function(tag) {
            var el = document.createElement('span');
            el.className = 'tag';
            el.innerHTML = tag + ' <span class="tag-remove" onclick="removeTag(this)">&times;</span>';
            wrapper.insertBefore(el, input);
        });
    }
}

function setOrCreateHidden(name, value) {
    var existing = document.querySelector('input[name="' + name + '"]');
    if (existing) {
        existing.value = value;
        return;
    }
    var form = document.querySelector('form[method="POST"]');
    if (form) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    }
}

// ============================================================
// Tab Component
// ============================================================

function initTabs() {
    document.querySelectorAll('[data-tabs]').forEach(function(tabsContainer) {
        var tabs = tabsContainer.querySelectorAll('.tab');
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var targetId = this.dataset.tab;
                if (!targetId) return;

                // Deactivate all tabs in this container
                tabs.forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');

                // Hide all tab contents in parent scope
                var parent = tabsContainer.parentElement;
                parent.querySelectorAll('.tab-content').forEach(function(content) {
                    content.classList.remove('active');
                });

                // Show target tab content
                var target = document.getElementById(targetId);
                if (target) target.classList.add('active');
            });
        });
    });
}

// ============================================================
// Custom Dropdown Component
// ============================================================

var CHEVRON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';

function initCustomDropdowns() {
    document.querySelectorAll('select.form-control').forEach(function(select) {
        if (select.dataset.customized) return;
        createCustomDropdown(select);
    });
}

function createCustomDropdown(select) {
    select.dataset.customized = 'true';

    // Build wrapper
    var wrapper = document.createElement('div');
    wrapper.className = 'custom-dropdown';

    // Copy inline styles from select that matter (like width:auto, min-height)
    var inlineStyle = select.getAttribute('style');
    if (inlineStyle) {
        // Extract width-related styles and apply to wrapper
        var widthMatch = inlineStyle.match(/width\s*:\s*([^;]+)/);
        if (widthMatch) {
            wrapper.style.width = widthMatch[1].trim();
            wrapper.style.display = 'inline-block';
        }
    }

    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select);

    // Gather options
    var options = [];
    var selectedIndex = -1;
    for (var i = 0; i < select.options.length; i++) {
        var opt = select.options[i];
        options.push({ value: opt.value, text: opt.textContent, disabled: opt.disabled });
        if (opt.selected) selectedIndex = i;
    }
    if (selectedIndex === -1 && options.length > 0) selectedIndex = 0;

    var showSearch = options.length > 7;

    // Build trigger
    var trigger = document.createElement('div');
    trigger.className = 'dropdown-trigger';
    trigger.setAttribute('tabindex', '0');
    trigger.setAttribute('role', 'combobox');
    trigger.setAttribute('aria-expanded', 'false');

    // Preserve inline style dimensions on trigger
    if (inlineStyle) {
        var minHeightMatch = inlineStyle.match(/min-height\s*:\s*([^;]+)/);
        if (minHeightMatch) trigger.style.minHeight = minHeightMatch[1].trim();
        var paddingMatch = inlineStyle.match(/padding\s*:\s*([^;]+)/);
        if (paddingMatch) trigger.style.padding = paddingMatch[1].trim();
    }

    var triggerText = document.createElement('span');
    triggerText.className = 'dropdown-trigger-text';
    if (selectedIndex >= 0 && options[selectedIndex]) {
        triggerText.textContent = options[selectedIndex].text;
        if (!options[selectedIndex].value) {
            triggerText.classList.add('placeholder');
        }
    }

    var triggerIcon = document.createElement('span');
    triggerIcon.className = 'dropdown-trigger-icon';
    triggerIcon.innerHTML = CHEVRON_SVG;

    trigger.appendChild(triggerText);
    trigger.appendChild(triggerIcon);
    wrapper.appendChild(trigger);

    // Build menu
    var menu = document.createElement('div');
    menu.className = 'dropdown-menu';
    menu.setAttribute('role', 'listbox');

    var searchInput = null;
    if (showSearch) {
        var searchWrap = document.createElement('div');
        searchWrap.className = 'dropdown-search';
        searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Search...';
        searchInput.setAttribute('autocomplete', 'off');
        searchWrap.appendChild(searchInput);
        menu.appendChild(searchWrap);
    }

    var noResults = document.createElement('div');
    noResults.className = 'dropdown-no-results hidden';
    noResults.textContent = 'No results found';
    menu.appendChild(noResults);

    var optionEls = [];
    options.forEach(function(opt, idx) {
        var el = document.createElement('div');
        el.className = 'dropdown-option';
        if (idx === selectedIndex) el.classList.add('selected');
        el.dataset.value = opt.value;
        el.dataset.index = idx;
        el.textContent = opt.text;
        el.setAttribute('role', 'option');
        menu.appendChild(el);
        optionEls.push(el);
    });

    wrapper.appendChild(menu);

    // State
    var isOpen = false;
    var highlightedIdx = selectedIndex >= 0 ? selectedIndex : 0;

    function open() {
        if (isOpen) return;
        isOpen = true;
        trigger.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
        menu.classList.add('open');

        // Check if dropdown would go below viewport
        var rect = wrapper.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom;
        if (spaceBelow < 290 && rect.top > 290) {
            wrapper.classList.add('dropup');
        } else {
            wrapper.classList.remove('dropup');
        }

        if (searchInput) {
            searchInput.value = '';
            filterOptions('');
            setTimeout(function() { searchInput.focus(); }, 10);
        }
        scrollToHighlighted();
    }

    function close() {
        if (!isOpen) return;
        isOpen = false;
        trigger.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
        menu.classList.remove('open');
        wrapper.classList.remove('dropup');
    }

    function selectOption(idx) {
        if (idx < 0 || idx >= options.length) return;
        selectedIndex = idx;
        highlightedIdx = idx;

        // Update visual
        optionEls.forEach(function(el, i) {
            el.classList.toggle('selected', i === idx);
        });
        triggerText.textContent = options[idx].text;
        triggerText.classList.toggle('placeholder', !options[idx].value);

        // Update real select
        select.value = options[idx].value;

        // Fire change event (for onchange="this.form.submit()" etc.)
        var event = new Event('change', { bubbles: true });
        select.dispatchEvent(event);

        // Also check for inline onchange
        if (select.getAttribute('onchange')) {
            // The onchange attribute uses 'this' referring to the select
            // The dispatched event should handle it, but as a fallback:
            try {
                var fn = new Function('event', 'var self = this; ' + select.getAttribute('onchange'));
                fn.call(select, event);
            } catch(e) { /* ignore */ }
        }

        close();
        trigger.focus();
    }

    function setHighlight(idx) {
        if (idx < 0 || idx >= optionEls.length) return;
        // Skip hidden options
        var visibleOptions = optionEls.filter(function(el) { return !el.classList.contains('hidden'); });
        if (visibleOptions.length === 0) return;

        optionEls.forEach(function(el) { el.classList.remove('highlighted'); });
        var target = optionEls[idx];
        if (target && !target.classList.contains('hidden')) {
            target.classList.add('highlighted');
            highlightedIdx = idx;
            scrollToHighlighted();
        }
    }

    function scrollToHighlighted() {
        var el = optionEls[highlightedIdx];
        if (el && menu.scrollHeight > menu.clientHeight) {
            el.scrollIntoView({ block: 'nearest' });
        }
    }

    function getNextVisibleIndex(from, direction) {
        var step = direction > 0 ? 1 : -1;
        var idx = from + step;
        while (idx >= 0 && idx < optionEls.length) {
            if (!optionEls[idx].classList.contains('hidden')) return idx;
            idx += step;
        }
        return from;
    }

    function filterOptions(query) {
        var q = query.toLowerCase().trim();
        var visibleCount = 0;
        optionEls.forEach(function(el, i) {
            var match = !q || options[i].text.toLowerCase().indexOf(q) !== -1;
            el.classList.toggle('hidden', !match);
            if (match) visibleCount++;
        });
        noResults.classList.toggle('hidden', visibleCount > 0);
        // Reset highlight to first visible
        for (var i = 0; i < optionEls.length; i++) {
            if (!optionEls[i].classList.contains('hidden')) {
                setHighlight(i);
                break;
            }
        }
    }

    // Event: toggle on trigger click
    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        if (isOpen) close(); else open();
    });

    // Event: option click
    menu.addEventListener('click', function(e) {
        var optEl = e.target.closest('.dropdown-option');
        if (optEl) {
            selectOption(parseInt(optEl.dataset.index));
        }
    });

    // Event: search input
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterOptions(this.value);
        });
        searchInput.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setHighlight(getNextVisibleIndex(highlightedIdx, 1));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setHighlight(getNextVisibleIndex(highlightedIdx, -1));
            } else if (e.key === 'Enter') {
                e.preventDefault();
                selectOption(highlightedIdx);
            } else if (e.key === 'Escape') {
                close();
                trigger.focus();
            }
        });
    }

    // Event: keyboard on trigger
    trigger.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            if (isOpen) {
                selectOption(highlightedIdx);
            } else {
                open();
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!isOpen) { open(); return; }
            setHighlight(getNextVisibleIndex(highlightedIdx, 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (!isOpen) { open(); return; }
            setHighlight(getNextVisibleIndex(highlightedIdx, -1));
        } else if (e.key === 'Escape') {
            close();
        } else if (e.key === 'Tab') {
            close();
        }
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            close();
        }
    });
}

// Sprint Spreadsheet Upload (multi-file drag & drop)
function initSprintSheetUpload() {
    var dropZone = document.getElementById('sprintDropZone');
    var fileInput = document.getElementById('sprintFileInput');
    var fileList = document.getElementById('sprintFileList');
    var uploadBtn = document.getElementById('sprintUploadBtn');
    var form = document.getElementById('sprintUploadForm');

    if (!dropZone || !fileInput) return;

    var selectedFiles = [];

    // Click to browse
    dropZone.addEventListener('click', function() {
        fileInput.click();
    });

    // Drag events
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });
    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
    });
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        addFiles(e.dataTransfer.files);
    });

    // File input change
    fileInput.addEventListener('change', function() {
        addFiles(fileInput.files);
        fileInput.value = '';
    });

    function addFiles(files) {
        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            var ext = f.name.split('.').pop().toLowerCase();
            if (['csv', 'xlsx', 'txt'].indexOf(ext) === -1) {
                continue;
            }
            if (f.size > 5 * 1024 * 1024) {
                continue;
            }
            // Avoid duplicates by name
            var exists = selectedFiles.some(function(sf) { return sf.name === f.name; });
            if (!exists) {
                selectedFiles.push(f);
            }
        }
        renderFileList();
    }

    function renderFileList() {
        if (selectedFiles.length === 0) {
            fileList.style.display = 'none';
            uploadBtn.style.display = 'none';
            return;
        }

        fileList.style.display = 'block';
        uploadBtn.style.display = 'inline-flex';
        fileList.innerHTML = '';

        selectedFiles.forEach(function(f, idx) {
            var item = document.createElement('div');
            item.className = 'sprint-file-item';
            item.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>' +
                '<span class="sprint-file-name">' + f.name + '</span>' +
                '<span class="sprint-file-size">' + formatFileSize(f.size) + '</span>' +
                '<button type="button" class="sprint-file-remove" data-idx="' + idx + '">&times;</button>';
            fileList.appendChild(item);
        });

        // Remove buttons
        fileList.querySelectorAll('.sprint-file-remove').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var idx = parseInt(this.getAttribute('data-idx'));
                selectedFiles.splice(idx, 1);
                renderFileList();
            });
        });
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    // Override form submit to attach collected files
    form.addEventListener('submit', function(e) {
        if (selectedFiles.length === 0) {
            e.preventDefault();
            return;
        }

        e.preventDefault();

        var formData = new FormData();
        var csrfToken = form.querySelector('input[name="_token"]').value;
        formData.append('_token', csrfToken);

        selectedFiles.forEach(function(f) {
            formData.append('files[]', f);
        });

        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<span class="spinner-sm"></span> Uploading...';

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(resp) {
            // Laravel will redirect on success, so we follow the redirect
            if (resp.redirected) {
                window.location.href = resp.url;
            } else {
                window.location.reload();
            }
        }).catch(function() {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Upload & Parse';
        });
    });
}

// ========================================================================
// Scoring Rules - Weight Sliders & Proportion Bar
// ========================================================================
function initWeightSliders() {
    var sliders = document.querySelectorAll('.weight-slider');
    var bar = document.getElementById('weightProportionBar');
    if (!sliders.length) return;

    var colors = [
        '#6366f1', '#8b5cf6', '#3b82f6', '#0ea5e9',
        '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#14b8a6'
    ];

    function updateProportionBar() {
        if (!bar) return;
        var total = 0;
        sliders.forEach(function(s) { if (!s.disabled) total += parseInt(s.value) || 0; });
        var html = '';
        var i = 0;
        sliders.forEach(function(s) {
            var val = s.disabled ? 0 : (parseInt(s.value) || 0);
            var pct = total > 0 ? (val / total * 100) : 0;
            var key = s.getAttribute('data-key') || '';
            if (pct > 0) {
                html += '<div class="weight-bar-segment" style="width:' + pct.toFixed(1) + '%;background:' + colors[i % colors.length] + '" title="' + key + ': ' + pct.toFixed(1) + '%">';
                if (pct > 6) html += key.replace(/_/g, ' ').replace(/score$/, '').trim();
                html += '</div>';
            }
            i++;
        });
        bar.innerHTML = html;
    }

    sliders.forEach(function(slider) {
        var display = document.querySelector('.weight-value[data-key="' + slider.getAttribute('data-key') + '"]');
        slider.addEventListener('input', function() {
            if (display) display.textContent = slider.value + '%';
            updateProportionBar();
        });
    });

    updateProportionBar();
}

// ========================================================================
// Star Rating Picker (Feedback Modal)
// ========================================================================
function initStarRatingPicker() {
    var picker = document.getElementById('feedbackStarPicker');
    var input = document.getElementById('feedbackRatingInput');
    var label = document.getElementById('starPickerLabel');
    if (!picker || !input) return;

    var stars = picker.querySelectorAll('.star-btn');
    var labels = ['', 'Poor', 'Below Average', 'Average', 'Good', 'Excellent'];
    var currentValue = 0;

    stars.forEach(function(star) {
        star.addEventListener('mouseenter', function() {
            var val = parseInt(this.getAttribute('data-value'));
            stars.forEach(function(s) {
                var sv = parseInt(s.getAttribute('data-value'));
                s.classList.toggle('hover', sv <= val);
            });
        });

        star.addEventListener('mouseleave', function() {
            stars.forEach(function(s) { s.classList.remove('hover'); });
        });

        star.addEventListener('click', function() {
            currentValue = parseInt(this.getAttribute('data-value'));
            input.value = currentValue;
            stars.forEach(function(s) {
                var sv = parseInt(s.getAttribute('data-value'));
                s.classList.toggle('active', sv <= currentValue);
            });
            if (label) label.textContent = labels[currentValue] || '';
        });
    });
}
