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

    // Initialize bulk apply upload (job show page)
    initBulkApplyUpload();

    // Initialize candidate search typeahead (job show page)
    initCandidateSearchTypeahead();

    // Initialize expandable rows
    initExpandableRows();

    // Initialize AI analysis AJAX buttons
    initAiAnalysisButtons();

    // Initialize interview summary AI generation + manual summary
    initInterviewSummaryAi();

    // Initialize interview completed filter tabs (client-side)
    initInterviewFilterTabs();

    // Initialize interview scheduling modal (triggered from stage changes)
    initInterviewSchedulingModal();
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
// Inline Stage Update
// ============================================================

function updateStageInline(select) {
    var url = select.dataset.url;
    var original = select.dataset.original;
    var newStage = select.value;
    if (newStage === original) return;

    select.classList.add('saving');

    fetch(url, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCSRFToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ stage: newStage }),
    })
    .then(function(r) {
        if (!r.ok) throw new Error('Failed');
        return r.json();
    })
    .then(function(data) {
        // Update class for color
        select.className = 'stage-select stage-' + newStage;
        select.dataset.original = newStage;

        // If the new stage is an interview stage, open the scheduling modal
        var interviewStages = ['hr_screening', 'technical_round_1', 'technical_round_2'];
        if (interviewStages.indexOf(newStage) !== -1 && document.getElementById('scheduleInterviewModal')) {
            var urlParts = url.split('/');
            var appIdx = urlParts.indexOf('applications');
            var appId = appIdx !== -1 ? urlParts[appIdx + 1] : '';
            var row = select.closest('tr');
            var candidateName = '';
            if (row) {
                var link = row.querySelector('td a');
                if (link) candidateName = link.textContent.trim();
            }
            openScheduleInterviewModal(newStage, appId, candidateName);
        }
    })
    .catch(function() {
        // Revert on error
        select.value = original;
        select.className = 'stage-select stage-' + original;
        alert('Failed to update stage. Please try again.');
    })
    .finally(function() {
        select.classList.remove('saving');
    });
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
    if (data.key_responsibilities) setField('[name="key_responsibilities"]', data.key_responsibilities);
    if (data.requirements) setField('[name="requirements"]', data.requirements);
    if (data.expectations) setField('[name="expectations"]', data.expectations);
    if (data.min_experience !== undefined) setField('[name="min_experience"]', data.min_experience);
    if (data.max_experience !== undefined) setField('[name="max_experience"]', data.max_experience);
    if (data.location) setField('[name="location"]', data.location);
    if (data.salary_min) setField('[name="salary_min"]', data.salary_min);
    if (data.salary_max) setField('[name="salary_max"]', data.salary_max);
    if (data.skill_experience_details) setField('[name="skill_experience_details"]', data.skill_experience_details);

    if (data.employment_type) setSelectValue('[name="employment_type"]', data.employment_type);
    if (data.required_skills && data.required_skills.length) setTagInput('[name="required_skills"]', data.required_skills);
    if (data.nice_to_have_skills && data.nice_to_have_skills.length) setTagInput('[name="nice_to_have_skills"]', data.nice_to_have_skills);

    // Store temp JD file info for permanent storage on form submit
    if (data._temp_file_path) {
        setOrCreateHidden('_temp_file_path', data._temp_file_path, 'jobForm');
        setOrCreateHidden('_temp_file_name', data._temp_file_name, 'jobForm');
        setOrCreateHidden('_temp_file_type', data._temp_file_type, 'jobForm');
        setOrCreateHidden('_jd_extracted_text', data._extracted_text || '', 'jobForm');
    }
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

    // Set skills into tag input
    if (data.skills && data.skills.length) {
        setTagInput('[name="skills"]', data.skills);
    }

    // Build notes from AI summary
    var notesParts = [];
    if (data.summary) notesParts.push(data.summary);
    if (notesParts.length) {
        setField('[name="notes"]', notesParts.join('\n\n'));
    }

    // Store temp file info in hidden fields for resume creation on form submit
    if (data._temp_file_path) {
        setOrCreateHidden('_temp_file_path', data._temp_file_path, 'candidateForm');
        setOrCreateHidden('_temp_file_name', data._temp_file_name, 'candidateForm');
        setOrCreateHidden('_temp_file_type', data._temp_file_type, 'candidateForm');
        setOrCreateHidden('_extracted_text', data._extracted_text, 'candidateForm');
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

    // Persist the charter temp key so the file is saved when the form is submitted
    if (data.charter_temp_key) {
        setField('#charterTempKey',      data.charter_temp_key);
        setField('#charterOriginalName', data.charter_original_name || '');
        setField('#charterFileType',     data.charter_file_type     || 'pdf');
        setField('#charterFileSize',     data.charter_file_size     || 0);
    }
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

function setOrCreateHidden(name, value, formId) {
    var form = formId ? document.getElementById(formId) : null;
    // Check within the target form first, then globally
    var existing = form
        ? form.querySelector('input[name="' + name + '"]')
        : document.querySelector('input[name="' + name + '"]');
    if (existing) {
        existing.value = value;
        return;
    }
    if (!form) {
        // Fallback: find the main content form (skip logout/nav forms)
        form = document.querySelector('.card-body form[method="POST"]')
            || document.querySelector('form[method="POST"]');
    }
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

    // URL hash support: open the tab matching the URL hash on page load
    // e.g. /employees/1#tab-signals will open the Work Pulse tab
    var hash = window.location.hash.replace('#', '');
    if (hash) {
        var btn = document.querySelector('[data-tab="' + hash + '"]');
        if (btn) btn.click();
    }
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

// ========================================================================
// Expandable Rows (Job Show - Candidates Tab)
// ========================================================================
function initExpandableRows() {
    // Arrow button clicks
    document.querySelectorAll('.expand-toggle').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleExpandRow(this);
        });
    });

    // Clickable row — entire row toggles expand
    document.querySelectorAll('tr[data-expand-target]').forEach(function(row) {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            // Don't toggle if clicking a link or button inside the row
            if (e.target.closest('a, button, input, select')) return;
            var btn = this.querySelector('.expand-toggle');
            if (btn) toggleExpandRow(btn);
        });
    });
}

function toggleExpandRow(btn) {
    var targetId = btn.getAttribute('data-target');
    var targetRow = document.getElementById(targetId);
    if (!targetRow) return;

    var isExpanded = targetRow.style.display !== 'none';
    targetRow.style.display = isExpanded ? 'none' : 'table-row';
    btn.classList.toggle('expanded', !isExpanded);
}

// ========================================================================
// Candidate Comparison (Job Show - Candidates Tab)
// ========================================================================
function updateCompareSelection() {
    var checked = document.querySelectorAll('.compare-checkbox:checked');
    var toolbar = document.getElementById('compareToolbar');
    var countEl = document.getElementById('compareCount');
    var compareBtn = document.getElementById('compareBtn');

    if (checked.length > 0) {
        toolbar.style.display = 'flex';
        countEl.textContent = checked.length + ' candidate' + (checked.length > 1 ? 's' : '') + ' selected';
        compareBtn.disabled = checked.length < 2;
    } else {
        toolbar.style.display = 'none';
    }
}

function clearComparison() {
    document.querySelectorAll('.compare-checkbox:checked').forEach(function(cb) {
        cb.checked = false;
    });
    document.getElementById('compareToolbar').style.display = 'none';
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function openComparisonModal() {
    var checked = document.querySelectorAll('.compare-checkbox:checked');
    if (checked.length < 2) return;

    var candidates = [];
    checked.forEach(function(cb) {
        candidates.push({
            name: cb.getAttribute('data-candidate-name'),
            score: parseFloat(cb.getAttribute('data-score')) || 0,
            recommendation: cb.getAttribute('data-recommendation') || '',
            strengths: JSON.parse(cb.getAttribute('data-strengths') || '[]'),
            concerns: JSON.parse(cb.getAttribute('data-concerns') || '[]'),
            explanation: cb.getAttribute('data-explanation') || '',
            skillMatch: cb.getAttribute('data-skill-match') || '',
            experience: cb.getAttribute('data-experience') || '',
            relevance: cb.getAttribute('data-relevance') || '',
            authenticity: cb.getAttribute('data-authenticity') || ''
        });
    });

    var colCount = candidates.length;
    var html = '<div class="comparison-grid" style="grid-template-columns: repeat(' + colCount + ', 1fr)">';

    candidates.forEach(function(c) {
        var scoreClass = c.score >= 70 ? 'high' : (c.score >= 40 ? 'medium' : 'low');
        html += '<div class="comparison-column">';
        html += '<div class="comparison-header">';
        html += '<div class="candidate-name">' + escapeHtml(c.name) + '</div>';
        html += '<div class="candidate-score ' + scoreClass + '">' + c.score.toFixed(1) + '</div>';
        if (c.recommendation) {
            html += '<div class="candidate-recommendation">' + escapeHtml(c.recommendation.replace(/_/g, ' ')) + '</div>';
        }
        html += '</div>';

        // Signal bars
        var signals = [
            { label: 'Skill Match', value: c.skillMatch },
            { label: 'Experience', value: c.experience },
            { label: 'Relevance', value: c.relevance },
            { label: 'Authenticity', value: c.authenticity }
        ];
        html += '<div class="comparison-section"><h5>Signals</h5>';
        signals.forEach(function(s) {
            if (s.value) {
                var v = parseFloat(s.value);
                html += '<div class="comparison-bar">';
                html += '<span class="bar-label">' + s.label + '</span>';
                html += '<div class="bar-track"><div class="bar-fill" style="width:' + v + '%"></div></div>';
                html += '<span class="bar-value">' + Math.round(v) + '%</span>';
                html += '</div>';
            }
        });
        html += '</div>';

        // Strengths
        if (c.strengths.length) {
            html += '<div class="comparison-section"><h5>Strengths</h5><ul class="comparison-list strengths">';
            c.strengths.forEach(function(s) { html += '<li>' + escapeHtml(s) + '</li>'; });
            html += '</ul></div>';
        }

        // Concerns
        if (c.concerns.length) {
            html += '<div class="comparison-section"><h5>Concerns</h5><ul class="comparison-list concerns">';
            c.concerns.forEach(function(s) { html += '<li>' + escapeHtml(s) + '</li>'; });
            html += '</ul></div>';
        }

        html += '</div>';
    });

    html += '</div>';

    document.getElementById('comparisonContent').innerHTML = html;
    openModal('comparisonModal');
}

// ========================================================================
// AI Analysis: AJAX Trigger, Progress Simulation & Polling
// ========================================================================

var AI_PHASES = {
    'application': [
        { pct: 15, text: 'Extracting resume content...', delay: 0 },
        { pct: 35, text: 'Analyzing skills & experience...', delay: 3000 },
        { pct: 55, text: 'Evaluating job fit signals...', delay: 6000 },
        { pct: 75, text: 'Computing weighted scores...', delay: 10000 },
        { pct: 90, text: 'Generating insights...', delay: 15000 },
    ],
    'job-candidate': [
        { pct: 20, text: 'Analyzing...', delay: 0 },
        { pct: 50, text: 'Scoring...', delay: 4000 },
        { pct: 80, text: 'Finalizing...', delay: 8000 },
    ],
    'project': [
        { pct: 15, text: 'Gathering employee profiles...', delay: 0 },
        { pct: 35, text: 'Analyzing skill requirements...', delay: 5000 },
        { pct: 55, text: 'Computing match scores...', delay: 12000 },
        { pct: 75, text: 'Ranking candidates...', delay: 20000 },
        { pct: 90, text: 'Generating recommendations...', delay: 30000 },
    ],
};

function initAiAnalysisButtons() {
    document.querySelectorAll('.ai-analyze-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            startAiAnalysis(this);
        });
    });
}

function analyzeAllCandidates(btn) {
    var url    = btn.dataset.url;
    var csrf   = btn.dataset.csrf;
    var btnText = document.getElementById('analyzeAllBtnText');

    btn.disabled = true;
    if (btnText) btnText.textContent = 'Queueing...';

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.status === 'queued') {
            var count = data.count || 0;
            if (btnText) btnText.textContent = 'Analysing ' + count + '...';

            // Trigger startAiAnalysis on every unanalysed row button with a small stagger
            var pending = document.querySelectorAll('.ai-analyze-btn[data-analyzed="false"]');
            pending.forEach(function(rowBtn, i) {
                setTimeout(function() { startAiAnalysis(rowBtn); }, i * 300);
            });
        } else {
            if (btnText) btnText.textContent = 'Analyse All';
            btn.disabled = false;
            alert(data.error || 'Failed to queue analysis.');
        }
    })
    .catch(function() {
        if (btnText) btnText.textContent = 'Analyse All';
        btn.disabled = false;
        alert('Failed to start bulk analysis. Please try again.');
    });
}

function startAiAnalysis(btn) {
    if (btn.disabled) return;

    var url = btn.dataset.url;
    var statusUrl = btn.dataset.statusUrl;
    var targetSelector = btn.dataset.target;
    var context = btn.dataset.context;
    var since = new Date().toISOString();

    // Disable button and show spinner
    btn.disabled = true;
    var btnText = btn.querySelector('.ai-btn-text');
    var originalText = btnText.textContent;
    btnText.textContent = 'Processing...';
    btn.classList.add('analyzing');

    // Show progress UI
    var progressEl = showAiProgress(btn, context, targetSelector);

    // AJAX trigger
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCSRFToken(),
            'Accept': 'application/json',
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.status === 'queued') {
            simulateAiProgress(progressEl, statusUrl, since, btn, targetSelector, context, originalText);
        } else {
            resetAiButton(btn, originalText);
            hideAiProgress(progressEl);
        }
    })
    .catch(function() {
        resetAiButton(btn, originalText);
        hideAiProgress(progressEl);
        showAiFlashError('Failed to start analysis. Please try again.');
    });
}

function showAiProgress(btn, context, targetSelector) {
    if (context === 'job-candidate') {
        // For table rows: show inline progress next to button
        var actionsCell = btn.closest('.table-actions');
        var existing = actionsCell.querySelector('.ai-inline-progress');
        if (existing) existing.remove();

        var progress = document.createElement('div');
        progress.className = 'ai-inline-progress';
        progress.innerHTML = '<div class="mini-bar"><div class="mini-fill" style="width:0%"></div></div><span class="mini-text">Starting...</span>';
        actionsCell.appendChild(progress);
        return progress;
    } else {
        // For full-page: show progress bar inside the target container
        var target = document.querySelector(targetSelector);
        if (!target) return null;

        // Save existing content and replace with progress
        target.dataset.previousContent = target.innerHTML;
        target.innerHTML =
            '<div class="ai-progress">' +
                '<div class="ai-progress-spinner"><div class="spinner"></div></div>' +
                '<div class="ai-progress-percent">0%</div>' +
                '<div class="ai-progress-bar"><div class="ai-progress-fill" style="width:0%"></div></div>' +
                '<div class="ai-progress-phase">Preparing analysis...</div>' +
            '</div>';
        return target.querySelector('.ai-progress');
    }
}

function simulateAiProgress(progressEl, statusUrl, since, btn, targetSelector, context, originalText) {
    var phases = AI_PHASES[context] || AI_PHASES['application'];
    var phaseTimers = [];
    var pollTimer = null;
    var completed = false;
    var maxPollTime = context === 'project' ? 300000 : 180000; // 5min for projects, 3min for resume
    var pollStart = Date.now();
    var compact = context === 'job-candidate' ? '&compact=1' : '';

    // Schedule phase animations
    phases.forEach(function(phase) {
        var t = setTimeout(function() {
            if (completed) return;
            updateAiProgress(progressEl, phase.pct, phase.text, context);
        }, phase.delay);
        phaseTimers.push(t);
    });

    // Start polling
    pollTimer = setInterval(function() {
        if (completed) return;

        // Timeout check
        if (Date.now() - pollStart > maxPollTime) {
            completed = true;
            clearInterval(pollTimer);
            phaseTimers.forEach(clearTimeout);
            resetAiButton(btn, originalText);
            restoreAiProgress(progressEl, context, targetSelector);
            showAiFlashError('Analysis is taking longer than expected. Please refresh the page to check results.');
            return;
        }

        fetch(statusUrl + '?since=' + encodeURIComponent(since) + compact, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCSRFToken() }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (completed) return;

            if (data.status === 'completed') {
                completed = true;
                clearInterval(pollTimer);
                phaseTimers.forEach(clearTimeout);

                // Animate to 100%
                updateAiProgress(progressEl, 100, 'Complete!', context);

                setTimeout(function() {
                    // Inject results
                    onAiAnalysisComplete(data, btn, targetSelector, context, originalText);
                }, 600);
            }
        })
        .catch(function() {
            // Silently ignore poll errors; will retry on next interval
        });
    }, 3000);
}

function updateAiProgress(progressEl, pct, text, context) {
    if (!progressEl) return;

    if (context === 'job-candidate') {
        var fill = progressEl.querySelector('.mini-fill');
        var label = progressEl.querySelector('.mini-text');
        if (fill) fill.style.width = pct + '%';
        if (label) label.textContent = text;
    } else {
        var fill = progressEl.querySelector('.ai-progress-fill');
        var phase = progressEl.querySelector('.ai-progress-phase');
        var percent = progressEl.querySelector('.ai-progress-percent');
        if (fill) fill.style.width = pct + '%';
        if (phase) phase.textContent = text;
        if (percent) percent.textContent = Math.round(pct) + '%';
    }
}

function onAiAnalysisComplete(data, btn, targetSelector, context, originalText) {
    if (context === 'job-candidate') {
        var rowId = btn.dataset.rowId;

        // Update score cell
        var scoreCell = document.getElementById('aiScore-' + rowId);
        if (scoreCell && data.ai_score !== undefined) {
            var score = parseFloat(data.ai_score);
            var cls = score >= 70 ? 'high' : (score >= 40 ? 'medium' : 'low');
            scoreCell.innerHTML = '<span class="score ' + cls + '" style="font-size:16px">' + score.toFixed(1) + '</span>';
            scoreCell.classList.add('ai-complete-flash');
        }

        // Inject analysis HTML into expandable row
        var resultPanel = document.getElementById('aiResult-' + rowId);
        if (resultPanel && data.html) {
            resultPanel.innerHTML = data.html;
        }

        // Remove inline progress
        var actionsCell = btn.closest('.table-actions');
        var inlineProgress = actionsCell.querySelector('.ai-inline-progress');
        if (inlineProgress) inlineProgress.remove();

        // Show expand button if not already present
        var expandBtn = actionsCell.querySelector('.expand-toggle');
        if (!expandBtn) {
            expandBtn = document.createElement('button');
            expandBtn.type = 'button';
            expandBtn.className = 'btn btn-sm btn-secondary expand-toggle';
            expandBtn.setAttribute('data-target', 'expand-' + rowId);
            expandBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg> Expand';
            actionsCell.insertBefore(expandBtn, actionsCell.firstChild);

            // Bind expand toggle
            expandBtn.addEventListener('click', function() {
                var targetRow = document.getElementById(this.getAttribute('data-target'));
                if (!targetRow) return;
                var isExpanded = targetRow.style.display !== 'none';
                targetRow.style.display = isExpanded ? 'none' : 'table-row';
                this.classList.toggle('expanded', !isExpanded);
                var txt = this.lastChild;
                if (txt && txt.nodeType === 3) {
                    txt.textContent = isExpanded ? ' Expand' : ' Collapse';
                }
            });
        }

        // Auto-expand to show results
        var expandRow = document.getElementById('expand-' + rowId);
        if (expandRow) {
            expandRow.style.display = 'table-row';
            expandRow.classList.add('ai-complete-flash');
            if (expandBtn) {
                expandBtn.classList.add('expanded');
                var txt = expandBtn.lastChild;
                if (txt && txt.nodeType === 3) txt.textContent = ' Collapse';
            }
        }

        // Add compare checkbox if not present
        var checkboxCell = btn.closest('tr').querySelector('td:first-child');
        if (checkboxCell && !checkboxCell.querySelector('.compare-checkbox') && data.ai_score) {
            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'compare-checkbox';
            cb.setAttribute('onchange', 'updateCompareSelection()');
            cb.setAttribute('data-app-id', rowId);
            cb.setAttribute('data-candidate-name', btn.closest('tr').querySelector('td:nth-child(2) a').textContent);
            cb.setAttribute('data-score', data.ai_score || 0);
            checkboxCell.appendChild(cb);
        }

        resetAiButton(btn, originalText);
    } else {
        // Full-page contexts (application show, project show)
        var target = document.querySelector(targetSelector);
        if (target && data.html) {
            target.innerHTML = data.html;
            target.classList.add('ai-complete-flash');
        }

        resetAiButton(btn, originalText);

        // Update score in hero meta if on application page
        if (context === 'application' && data.ai_score) {
            var metaItems = document.querySelectorAll('.profile-meta .meta-item');
            var scoreFound = false;
            metaItems.forEach(function(item) {
                if (item.textContent.indexOf('AI Score') !== -1) {
                    item.querySelector('strong').textContent = parseFloat(data.ai_score).toFixed(1);
                    scoreFound = true;
                }
            });
            if (!scoreFound) {
                var profileMeta = document.querySelector('.profile-meta');
                if (profileMeta) {
                    var span = document.createElement('span');
                    span.className = 'meta-item';
                    span.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> AI Score: <strong>' + parseFloat(data.ai_score).toFixed(1) + '</strong>';
                    profileMeta.appendChild(span);
                }
            }
        }
    }
}

function restoreAiProgress(progressEl, context, targetSelector) {
    if (context === 'job-candidate') {
        if (progressEl) progressEl.remove();
    } else {
        var target = document.querySelector(targetSelector);
        if (target && target.dataset.previousContent) {
            target.innerHTML = target.dataset.previousContent;
            delete target.dataset.previousContent;
        }
    }
}

function resetAiButton(btn, originalText) {
    btn.disabled = false;
    btn.classList.remove('analyzing');
    var btnText = btn.querySelector('.ai-btn-text');
    if (btnText) btnText.textContent = originalText;
}

function hideAiProgress(progressEl) {
    if (progressEl) progressEl.remove();
}

function showAiFlashError(msg) {
    var content = document.querySelector('.content') || document.querySelector('main') || document.body;
    var alert = document.createElement('div');
    alert.className = 'alert alert-danger';
    alert.innerHTML = '<span>' + msg + '</span><button class="alert-close" onclick="this.closest(\'.alert\').remove()">&times;</button>';
    content.insertBefore(alert, content.firstChild);

    // Auto-dismiss after 8 seconds
    setTimeout(function() {
        if (alert.parentNode) {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(function() { if (alert.parentNode) alert.remove(); }, 300);
        }
    }, 8000);
}

// ============================================================
// Bulk Apply Upload (Job Show → Bulk Add Candidates Modal)
// ============================================================

function initBulkApplyUpload() {
    var area = document.getElementById('bulkApplyUploadArea');
    var fileInput = document.getElementById('bulkApplyFileInput');
    var form = document.getElementById('bulkApplyForm');
    if (!area || !fileInput || !form) return;

    var fileListEl = document.getElementById('bulkApplyFileList');
    var fileCountEl = document.getElementById('bulkApplyFileCount');
    var fileCountText = document.getElementById('bulkApplyFileCountText');
    var submitBtn = document.getElementById('bulkApplySubmitBtn');
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

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
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

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (selectedFiles.length === 0) return;

        var formData = new FormData();
        var csrfToken = form.querySelector('input[name="_token"]');
        if (csrfToken) formData.append('_token', csrfToken.value);
        selectedFiles.forEach(function(file) {
            formData.append('resumes[]', file);
        });

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:8px"></span> Processing resumes...';

        fetch(form.action, {
            method: 'POST',
            headers: { 'Accept': 'text/html' },
            body: formData,
        }).then(function(response) {
            if (response.redirected) {
                window.location.href = response.url;
            } else if (response.ok) {
                window.location.reload();
            } else {
                throw new Error('Upload failed. Please try again.');
            }
        }).catch(function(err) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Upload & Apply Candidates';
            showAiFlashError(err.message || 'Network error. Please try again.');
        });
    });
}

/* ===================================================================
   INTERVIEW SUMMARY — AI Generate + Progress Bar + Manual Summary
   =================================================================== */

var IR_SUMMARY_PHASES = [
    { pct: 10, text: 'Preparing transcript data...', delay: 0 },
    { pct: 25, text: 'Analyzing conversation flow...', delay: 3000 },
    { pct: 45, text: 'Evaluating candidate responses...', delay: 7000 },
    { pct: 60, text: 'Assessing technical depth...', delay: 12000 },
    { pct: 75, text: 'Identifying strengths & concerns...', delay: 18000 },
    { pct: 88, text: 'Generating hiring narrative...', delay: 25000 },
];

function initInterviewSummaryAi() {
    var section = document.getElementById('ir-ai-summary-section');
    if (!section) return;

    var generateBtn = document.getElementById('btn-generate-summary');
    if (generateBtn) {
        generateBtn.addEventListener('click', function() {
            startInterviewSummaryGeneration(section);
        });
    }

    initManualSummaryAutosave();
}

function startInterviewSummaryGeneration(section) {
    var generateUrl = section.dataset.generateUrl;
    var statusUrl = section.dataset.statusUrl;
    var csrf = section.dataset.csrf;

    var placeholder = document.getElementById('ir-ai-placeholder');
    var progressEl = document.getElementById('ir-ai-progress');
    var btn = document.getElementById('btn-generate-summary');

    if (btn) btn.disabled = true;

    // POST to trigger generation
    fetch(generateUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.status === 'queued') {
            // Hide placeholder, show progress bar
            if (placeholder) placeholder.style.display = 'none';
            if (progressEl) progressEl.style.display = 'block';

            // Start simulated progress + polling
            simulateInterviewSummaryProgress(statusUrl, csrf);
        } else {
            if (btn) btn.disabled = false;
            showAiFlashError(data.error || 'Failed to start AI summary generation.');
        }
    })
    .catch(function() {
        if (btn) btn.disabled = false;
        showAiFlashError('Network error. Please try again.');
    });
}

function simulateInterviewSummaryProgress(statusUrl, csrf) {
    var percentEl = document.getElementById('ir-ai-progress-percent');
    var fillEl = document.getElementById('ir-ai-progress-fill');
    var phaseEl = document.getElementById('ir-ai-progress-phase');
    var progressCard = document.getElementById('ir-ai-progress');

    var completed = false;
    var phaseTimers = [];
    var pollStart = Date.now();
    var maxPollTime = 180000; // 3 minutes

    // Schedule phase animations
    IR_SUMMARY_PHASES.forEach(function(phase) {
        var t = setTimeout(function() {
            if (completed) return;
            if (percentEl) percentEl.textContent = phase.pct + '%';
            if (fillEl) fillEl.style.width = phase.pct + '%';
            if (phaseEl) phaseEl.textContent = phase.text;
        }, phase.delay);
        phaseTimers.push(t);
    });

    // Poll every 3 seconds
    var pollTimer = setInterval(function() {
        if (completed) return;

        // Timeout
        if (Date.now() - pollStart > maxPollTime) {
            completed = true;
            clearInterval(pollTimer);
            phaseTimers.forEach(clearTimeout);
            showInterviewSummaryError('Analysis is taking longer than expected. Please refresh the page.');
            return;
        }

        fetch(statusUrl, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (completed) return;

            if (data.status === 'completed') {
                completed = true;
                clearInterval(pollTimer);
                phaseTimers.forEach(clearTimeout);

                // Animate to 100%
                if (percentEl) percentEl.textContent = '100%';
                if (fillEl) fillEl.style.width = '100%';
                if (phaseEl) phaseEl.textContent = 'Complete!';

                // Reload page to show full results
                setTimeout(function() {
                    window.location.reload();
                }, 800);
            } else if (data.status === 'failed') {
                completed = true;
                clearInterval(pollTimer);
                phaseTimers.forEach(clearTimeout);
                showInterviewSummaryError(data.message || 'AI summary generation failed. You can retry or write a manual summary.');
            }
        })
        .catch(function() {
            // Silently ignore poll errors, will retry
        });
    }, 3000);
}

function showInterviewSummaryError(message) {
    var progressCard = document.getElementById('ir-ai-progress');
    var placeholder = document.getElementById('ir-ai-placeholder');

    if (progressCard) progressCard.style.display = 'none';
    if (placeholder) {
        placeholder.style.display = 'block';
        placeholder.innerHTML =
            '<div class="card-body" style="text-align:center; padding:40px;">' +
                '<svg width="40" height="40" fill="none" stroke="var(--danger)" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 16px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>' +
                '<p style="font-weight:500; margin-bottom:8px; color:var(--danger);">AI Summary Generation Failed</p>' +
                '<p class="text-muted" style="font-size:13px; margin-bottom:16px;">' + message + '</p>' +
                '<button type="button" id="btn-generate-summary" class="btn btn-primary" style="font-size:15px; padding:10px 28px;">' +
                    'Retry AI Summary' +
                '</button>' +
            '</div>';

        // Re-bind the retry button
        var retryBtn = placeholder.querySelector('#btn-generate-summary');
        if (retryBtn) {
            retryBtn.addEventListener('click', function() {
                var section = document.getElementById('ir-ai-summary-section');
                if (section) startInterviewSummaryGeneration(section);
            });
        }
    }
}

/* --- Manual Summary Auto-save --- */

function initManualSummaryAutosave() {
    var textarea = document.getElementById('ir-manual-summary');
    if (!textarea) return;

    var saveBtn = document.getElementById('btn-save-manual-summary');
    var statusEl = document.getElementById('ir-manual-save-status');
    var saveTimer = null;

    function saveManualSummary() {
        var url = textarea.dataset.url;
        var csrf = textarea.dataset.csrf;
        var text = textarea.value.trim();

        if (statusEl) statusEl.textContent = 'Saving...';
        if (saveBtn) saveBtn.disabled = true;

        fetch(url, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ manual_summary: text })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'ok') {
                if (statusEl) statusEl.textContent = 'Saved';
                setTimeout(function() {
                    if (statusEl) statusEl.textContent = '';
                }, 2000);
            } else {
                if (statusEl) { statusEl.textContent = 'Save failed'; statusEl.style.color = 'var(--danger)'; }
            }
            if (saveBtn) saveBtn.disabled = false;
        })
        .catch(function() {
            if (statusEl) { statusEl.textContent = 'Save failed'; statusEl.style.color = 'var(--danger)'; }
            if (saveBtn) saveBtn.disabled = false;
        });
    }

    // Auto-save on typing with 2s debounce
    textarea.addEventListener('input', function() {
        if (saveTimer) clearTimeout(saveTimer);
        if (statusEl) { statusEl.textContent = ''; statusEl.style.color = ''; }
        saveTimer = setTimeout(saveManualSummary, 2000);
    });

    // Save button click
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            if (saveTimer) clearTimeout(saveTimer);
            saveManualSummary();
        });
    }
}

/* ===================================================================
   INTERVIEW INDEX — Client-side Completed Filter Tabs
   =================================================================== */

function initInterviewFilterTabs() {
    var tabsContainer = document.getElementById('ir-filter-tabs');
    if (!tabsContainer) return;

    var table = document.getElementById('ir-completed-table');
    if (!table) return;

    var rows = table.querySelectorAll('tbody tr[data-outcome]');
    var noMatch = document.getElementById('ir-no-match');

    // Compute counts once on load
    var counts = { all: rows.length, advanced: 0, waitlisted: 0, rejected: 0, pending: 0 };
    rows.forEach(function(row) {
        var outcome = row.dataset.outcome;
        if (counts[outcome] !== undefined) counts[outcome]++;
    });

    // Update count badges
    tabsContainer.querySelectorAll('.ir-filter-count').forEach(function(span) {
        var key = span.dataset.count;
        if (counts[key] !== undefined) span.textContent = counts[key];
    });

    // Tab click handler
    tabsContainer.querySelectorAll('.ir-outcome-filter').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var filter = this.dataset.filter;

            // Update active tab
            tabsContainer.querySelectorAll('.ir-outcome-filter').forEach(function(t) {
                t.classList.remove('ir-outcome-filter--active');
            });
            this.classList.add('ir-outcome-filter--active');

            // Filter rows
            var visibleCount = 0;
            rows.forEach(function(row) {
                var show = filter === 'all' || row.dataset.outcome === filter;
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            // Show/hide empty state
            if (noMatch) {
                noMatch.style.display = visibleCount === 0 ? 'block' : 'none';
            }
            table.style.display = visibleCount === 0 ? 'none' : '';
        });
    });
}

// ============================================================
// Interview Scheduling Modal (triggered from stage changes)
// ============================================================

var SIM_INTERVIEW_STAGES = ['hr_screening', 'technical_round_1', 'technical_round_2'];
var SIM_STAGE_LABELS = {
    'hr_screening': 'HR Screening',
    'technical_round_1': 'Technical Round 1',
    'technical_round_2': 'Technical Round 2',
};

function initInterviewSchedulingModal() {
    var modal = document.getElementById('scheduleInterviewModal');
    if (!modal) return;

    initSimEmployeeSearch();

    // Reset form when modal closes (via overlay click)
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(m) {
            if (m.attributeName === 'class' && !modal.classList.contains('active')) {
                resetScheduleInterviewModal();
            }
        });
    });
    observer.observe(modal, { attributes: true });

    // Intercept application show page stage form
    var appStageForm = document.getElementById('appStageForm');
    if (appStageForm) {
        appStageForm.addEventListener('submit', function(e) {
            var select = document.getElementById('appStageSelect');
            if (!select) return;
            var newStage = select.value;

            if (SIM_INTERVIEW_STAGES.indexOf(newStage) === -1) return; // let normal POST happen

            e.preventDefault();

            var url = appStageForm.dataset.updateUrl;
            var appId = appStageForm.dataset.applicationId;
            var candidateName = appStageForm.dataset.candidateName;
            var stageNotes = appStageForm.querySelector('[name="stage_notes"]');
            var rejReason = appStageForm.querySelector('[name="rejection_reason"]');

            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    stage: newStage,
                    stage_notes: stageNotes ? stageNotes.value : '',
                    rejection_reason: rejReason ? rejReason.value : '',
                }),
            })
            .then(function(r) {
                if (!r.ok) throw new Error('Failed');
                return r.json();
            })
            .then(function() {
                // Update the stage badge in the hero section
                var badge = document.querySelector('.profile-meta .stage-badge');
                if (badge) {
                    badge.textContent = newStage.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
                    badge.className = 'stage-badge stage-' + newStage;
                }
                openScheduleInterviewModal(newStage, appId, candidateName);
            })
            .catch(function() {
                // Fallback: submit form normally
                appStageForm.submit();
            });
        });
    }
}

function openScheduleInterviewModal(newStage, appId, candidateName) {
    if (SIM_INTERVIEW_STAGES.indexOf(newStage) === -1) return;

    document.getElementById('sim_application_id').value = appId;
    document.getElementById('sim_interview_type').value = newStage;
    document.getElementById('sim_candidate_name').textContent = candidateName || 'Candidate';
    document.getElementById('sim_stage_label').textContent = SIM_STAGE_LABELS[newStage] || newStage;

    resetScheduleInterviewModal();
    openModal('scheduleInterviewModal');
}

function resetScheduleInterviewModal() {
    var search = document.getElementById('sim_interviewer_search');
    var hidden = document.getElementById('sim_employee_id');
    var selected = document.getElementById('sim_selected_interviewer');
    var results = document.getElementById('sim_employee_results');
    var schedAt = document.getElementById('sim_scheduled_at');
    var notes = document.getElementById('sim_notes');

    if (search) { search.value = ''; search.style.display = ''; }
    if (hidden) hidden.value = '';
    if (selected) selected.style.display = 'none';
    if (results) { results.style.display = 'none'; results.innerHTML = ''; }
    if (schedAt) schedAt.value = '';
    if (notes) notes.value = '';
}

function initSimEmployeeSearch() {
    var searchInput = document.getElementById('sim_interviewer_search');
    var resultsDiv = document.getElementById('sim_employee_results');
    var hiddenInput = document.getElementById('sim_employee_id');
    var selectedDiv = document.getElementById('sim_selected_interviewer');
    var clearBtn = document.getElementById('sim_clear_interviewer');
    var modal = document.getElementById('scheduleInterviewModal');

    if (!searchInput || !modal) return;

    var searchUrl = modal.dataset.searchUrl;
    var debounce = null;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounce);
        var q = this.value.trim();
        if (q.length < 2) { resultsDiv.style.display = 'none'; return; }
        debounce = setTimeout(function() {
            fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.length) {
                    resultsDiv.innerHTML = '<div class="employee-result-item text-muted">No employees found</div>';
                } else {
                    resultsDiv.innerHTML = data.map(function(e) {
                        return '<div class="employee-result-item"' +
                            ' data-id="' + e.id + '"' +
                            ' data-name="' + escapeHtml(e.name) + '"' +
                            ' data-email="' + escapeHtml(e.email) + '"' +
                            ' data-has-account="' + (e.has_account ? '1' : '0') + '">' +
                            '<strong>' + escapeHtml(e.name) + '</strong>' +
                            ' <small class="text-muted">' + escapeHtml(e.email) + '</small>' +
                            (e.has_account ? '' : ' <span class="badge badge-warning" style="font-size:10px;">No account</span>') +
                            '</div>';
                    }).join('');
                }
                resultsDiv.style.display = 'block';
            });
        }, 300);
    });

    resultsDiv.addEventListener('click', function(e) {
        var item = e.target.closest('.employee-result-item');
        if (!item || !item.dataset.id) return;

        hiddenInput.value = item.dataset.id;
        document.getElementById('sim_sel_name').textContent = item.dataset.name;
        document.getElementById('sim_sel_email').textContent = item.dataset.email;

        var noteDiv = document.getElementById('sim_sel_account_note');
        if (noteDiv) noteDiv.style.display = item.dataset.hasAccount === '0' ? 'flex' : 'none';

        selectedDiv.style.display = 'flex';
        searchInput.style.display = 'none';
        resultsDiv.style.display = 'none';
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            hiddenInput.value = '';
            selectedDiv.style.display = 'none';
            searchInput.style.display = '';
            searchInput.value = '';
            searchInput.focus();
        });
    }
}
