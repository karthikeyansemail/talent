/**
 * Live AI Interview Assistant — Client-side Engine
 *
 * Audio strategy (dual Whisper):
 *  - Microphone (interviewer) → MediaRecorder → Whisper + Web Speech API (interim display)
 *  - System audio (candidate from meeting) → MediaRecorder → Whisper
 *
 * Web Speech API is unreliable during meetings (echo cancellation conflicts).
 * Both channels now use Whisper for authoritative transcription.
 * Web Speech API is kept only for real-time interim text preview.
 */
(function () {
    'use strict';

    // -----------------------------------------------------------------------
    // DOM & config
    // -----------------------------------------------------------------------
    const room = document.getElementById('interview-room');
    if (!room) return;

    const CSRF   = room.dataset.csrf;
    const URLS   = {
        transcript:     room.dataset.urlTranscript,
        questions:      room.dataset.urlQuestions,
        evaluate:       room.dataset.urlEvaluate,
        questionStatus: room.dataset.urlQuestionStatus,
        state:          room.dataset.urlState,
        notes:          room.dataset.urlNotes,
        end:            room.dataset.urlEnd,
        summary:        room.dataset.urlSummary,
        transcribe:     room.dataset.urlTranscribe,
    };
    const startedAt = room.dataset.startedAt ? new Date(room.dataset.startedAt) : new Date();

    const btnMic          = document.getElementById('btn-mic');
    const btnSystem       = document.getElementById('btn-system');
    const btnGenerate     = document.getElementById('btn-generate-questions');
    const btnEnd          = document.getElementById('btn-end-session');
    const timerEl         = document.getElementById('ir-timer');
    const transcriptEl    = document.getElementById('ir-transcript');
    const interimEl       = document.getElementById('ir-interim');
    const statusEl        = document.getElementById('ir-transcript-status');
    const questionsEl     = document.getElementById('ir-questions');
    const evaluationEl    = document.getElementById('ir-evaluation');
    const notesEl         = document.getElementById('ir-notes');

    // -----------------------------------------------------------------------
    // State
    // -----------------------------------------------------------------------
    let micStream       = null;
    let systemStream    = null;
    let recognition     = null;
    let isRecognizing   = false;

    // Separate recorders and mutexes for mic and system audio
    let micRecorder         = null;
    let micRecorderInterval = null;
    let isMicTranscribing   = false;

    let systemRecorder         = null;
    let systemRecorderInterval = null;
    let isSystemTranscribing   = false;

    let transcriptBuffer = [];
    let flushTimer       = null;
    let elapsedInterval  = null;
    let notesDebounce    = null;

    var MIC_CHUNK_DURATION    = 5000;  // 5s chunks for mic (shorter for responsiveness)
    var SYSTEM_CHUNK_DURATION = 7000;  // 7s chunks for system audio

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------
    function headers(json) {
        const h = { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' };
        if (json) h['Content-Type'] = 'application/json';
        return h;
    }

    function elapsedSeconds() {
        return Math.floor((Date.now() - startedAt.getTime()) / 1000);
    }

    function fmtTime(secs) {
        const h = String(Math.floor(secs / 3600)).padStart(2, '0');
        const m = String(Math.floor((secs % 3600) / 60)).padStart(2, '0');
        const s = String(secs % 60).padStart(2, '0');
        return h + ':' + m + ':' + s;
    }

    function scrollTranscript() {
        transcriptEl.scrollTop = transcriptEl.scrollHeight;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function pickMimeType() {
        var mimeType = 'audio/webm;codecs=opus';
        if (!MediaRecorder.isTypeSupported(mimeType)) mimeType = 'audio/webm';
        if (!MediaRecorder.isTypeSupported(mimeType)) mimeType = '';
        return mimeType;
    }

    // -----------------------------------------------------------------------
    // Timer
    // -----------------------------------------------------------------------
    function startTimer() {
        elapsedInterval = setInterval(function () {
            timerEl.textContent = fmtTime(elapsedSeconds());
        }, 1000);
        timerEl.textContent = fmtTime(elapsedSeconds());
    }
    startTimer();

    // -----------------------------------------------------------------------
    // Web Speech API — kept ONLY for real-time interim text display
    // Final results are ignored; Whisper handles authoritative transcription.
    // -----------------------------------------------------------------------
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    function initRecognition() {
        if (!SpeechRecognition) return;
        recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = 'en-US';
        recognition.maxAlternatives = 1;

        recognition.onstart = function () {
            isRecognizing = true;
            updateStatus();
        };

        recognition.onresult = function (event) {
            var interim = '';
            for (var i = event.resultIndex; i < event.results.length; i++) {
                var result = event.results[i];
                if (result.isFinal) {
                    // Don't add to transcript — Whisper will handle it
                    interimEl.style.display = 'none';
                } else {
                    interim += result[0].transcript;
                }
            }
            if (interim) {
                interimEl.textContent = '[You] ... ' + interim;
                interimEl.style.display = 'block';
            }
        };

        recognition.onerror = function (event) {
            console.warn('Speech recognition error:', event.error);
            if (['network', 'aborted', 'no-speech'].indexOf(event.error) >= 0 && micStream) {
                setTimeout(restartRecognition, 500);
            }
        };

        recognition.onend = function () {
            isRecognizing = false;
            if (micStream) {
                setTimeout(restartRecognition, 200);
            } else {
                updateStatus();
            }
        };
    }

    function startRecognition() {
        if (!recognition) initRecognition();
        if (!recognition) return;
        if (!isRecognizing) {
            try { recognition.start(); } catch (e) { /* already started */ }
        }
    }

    function stopRecognition() {
        if (recognition && isRecognizing) {
            try { recognition.stop(); } catch (e) { /* ok */ }
        }
    }

    function restartRecognition() {
        if (micStream) startRecognition();
    }

    function updateStatus() {
        var parts = [];
        if (micStream) parts.push('Mic: recording');
        if (systemStream) parts.push('System: recording');
        if (parts.length) {
            statusEl.textContent = parts.join(' | ');
            statusEl.style.color = 'var(--success)';
        } else {
            statusEl.textContent = 'Waiting for audio...';
            statusEl.style.color = '';
        }
    }

    // -----------------------------------------------------------------------
    // Transcript management
    // -----------------------------------------------------------------------
    function addTranscriptEntry(speaker, text, confidence) {
        var empty = transcriptEl.querySelector('.ir-transcript-empty');
        if (empty) empty.remove();

        var offset = elapsedSeconds();
        var entry = document.createElement('div');
        entry.className = 'ir-transcript-entry';
        entry.innerHTML =
            '<span class="ir-transcript-entry__time">' + fmtTime(offset) + '</span>' +
            '<span class="ir-transcript-entry__speaker ir-transcript-entry__speaker--' + (speaker === 'interviewer' ? 'you' : 'candidate') + '">' +
                (speaker === 'interviewer' ? 'You' : 'Candidate') +
            '</span>' +
            '<span class="ir-transcript-entry__text">' + escapeHtml(text) + '</span>';
        transcriptEl.appendChild(entry);
        scrollTranscript();

        transcriptBuffer.push({
            speaker: speaker,
            text: text,
            offset_seconds: offset,
            confidence: confidence || null,
        });

        if (transcriptBuffer.length >= 10) {
            flushTranscript();
        }
    }

    function flushTranscript() {
        if (!transcriptBuffer.length) return;
        var batch = transcriptBuffer.splice(0, 50);
        fetch(URLS.transcript, {
            method: 'POST',
            headers: headers(true),
            body: JSON.stringify({ segments: batch }),
        }).catch(function (err) {
            console.error('Failed to flush transcript:', err);
            transcriptBuffer = batch.concat(transcriptBuffer);
        });
    }

    flushTimer = setInterval(flushTranscript, 10000);

    // -----------------------------------------------------------------------
    // Generic audio recorder — used for both mic and system audio
    // -----------------------------------------------------------------------
    function createRecorder(stream, onChunk) {
        var mimeType = pickMimeType();
        try {
            var opts = mimeType ? { mimeType: mimeType } : {};
            var recorder = new MediaRecorder(stream, opts);
        } catch (e) {
            console.error('MediaRecorder creation failed:', e);
            return null;
        }

        recorder.ondataavailable = function (e) {
            if (e.data && e.data.size > 500) {
                onChunk(e.data);
            }
        };

        recorder.onerror = function (e) {
            console.error('MediaRecorder error:', e.error);
        };

        return recorder;
    }

    function startChunkedRecording(stream, chunkDuration, onChunk) {
        var recorder = createRecorder(stream, onChunk);
        if (!recorder) return { recorder: null, interval: null };

        recorder.start();

        var interval = setInterval(function () {
            if (recorder.state === 'recording') {
                recorder.stop();
                setTimeout(function () {
                    if (stream.active) {
                        var newRecorder = createRecorder(stream, onChunk);
                        if (newRecorder) {
                            recorder = newRecorder;
                            newRecorder.start();
                        }
                    }
                }, 100);
            }
        }, chunkDuration);

        return { recorder: recorder, interval: interval };
    }

    function sendToWhisper(audioBlob, speaker, busyFlag, setBusyFlag) {
        if (busyFlag) {
            console.log('Whisper busy (' + speaker + '), skipping chunk (' + audioBlob.size + ' bytes)');
            return;
        }
        setBusyFlag(true);

        var formData = new FormData();
        formData.append('file', audioBlob, 'audio.webm');

        fetch(URLS.transcribe, {
            method: 'POST',
            body: formData,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var text = (data.text || '').trim();
            if (text) {
                addTranscriptEntry(speaker, text, null);
                // Clear interim display when mic Whisper result arrives
                if (speaker === 'interviewer') {
                    interimEl.style.display = 'none';
                }
            }
        })
        .catch(function (err) {
            console.error('Whisper transcription error (' + speaker + '):', err);
        })
        .finally(function () {
            setBusyFlag(false);
        });
    }

    // -----------------------------------------------------------------------
    // Microphone capture → MediaRecorder → Whisper (interviewer)
    // Also starts Web Speech API for interim display
    // -----------------------------------------------------------------------
    function startMicRecorder() {
        if (!micStream || micStream.getAudioTracks().length === 0) return;

        var result = startChunkedRecording(micStream, MIC_CHUNK_DURATION, function (blob) {
            sendToWhisper(blob, 'interviewer', isMicTranscribing, function (v) { isMicTranscribing = v; });
        });
        micRecorder = result.recorder;
        micRecorderInterval = result.interval;
    }

    function stopMicRecorder() {
        clearInterval(micRecorderInterval);
        micRecorderInterval = null;
        if (micRecorder && micRecorder.state !== 'inactive') {
            try { micRecorder.stop(); } catch (e) { /* ok */ }
        }
        micRecorder = null;
    }

    btnMic.addEventListener('click', function () {
        if (micStream) {
            // Stop mic
            stopMicRecorder();
            micStream.getTracks().forEach(function (t) { t.stop(); });
            micStream = null;
            btnMic.classList.remove('ir-audio-btn--on');
            btnMic.classList.add('ir-audio-btn--off');
            btnMic.querySelector('span').textContent = 'Mic: OFF';
            stopRecognition();
            updateStatus();
        } else {
            navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: false,
                    noiseSuppression: false,
                    autoGainControl: true,
                }
            })
            .then(function (stream) {
                micStream = stream;
                btnMic.classList.remove('ir-audio-btn--off');
                btnMic.classList.add('ir-audio-btn--on');
                btnMic.querySelector('span').textContent = 'Mic: ON';

                // Start Whisper-based transcription (authoritative)
                startMicRecorder();

                // Start Web Speech API for interim display only
                startRecognition();
                updateStatus();
            })
            .catch(function (err) {
                console.error('Mic error:', err);
                alert('Could not access microphone. Please allow microphone permissions.');
            });
        }
    });

    // -----------------------------------------------------------------------
    // System audio capture → MediaRecorder → Whisper (candidate)
    // -----------------------------------------------------------------------
    function startSystemRecorder() {
        if (!systemStream || systemStream.getAudioTracks().length === 0) return;

        var result = startChunkedRecording(systemStream, SYSTEM_CHUNK_DURATION, function (blob) {
            sendToWhisper(blob, 'candidate', isSystemTranscribing, function (v) { isSystemTranscribing = v; });
        });
        systemRecorder = result.recorder;
        systemRecorderInterval = result.interval;
    }

    function stopSystemRecorder() {
        clearInterval(systemRecorderInterval);
        systemRecorderInterval = null;
        if (systemRecorder && systemRecorder.state !== 'inactive') {
            try { systemRecorder.stop(); } catch (e) { /* ok */ }
        }
        systemRecorder = null;
    }

    btnSystem.addEventListener('click', function () {
        if (systemStream) {
            stopSystemRecorder();
            systemStream.getTracks().forEach(function (t) { t.stop(); });
            systemStream = null;
            btnSystem.classList.remove('ir-audio-btn--on');
            btnSystem.classList.add('ir-audio-btn--off');
            btnSystem.querySelector('span').textContent = 'System Audio: OFF';
            updateStatus();
        } else {
            navigator.mediaDevices.getDisplayMedia({
                video: true,
                audio: true,
            })
            .then(function (stream) {
                stream.getVideoTracks().forEach(function (t) { t.stop(); });

                if (stream.getAudioTracks().length === 0) {
                    alert('No audio track captured. Make sure to check "Share system audio" or "Share audio" when prompted.');
                    stream.getTracks().forEach(function (t) { t.stop(); });
                    return;
                }

                systemStream = stream;
                btnSystem.classList.remove('ir-audio-btn--off');
                btnSystem.classList.add('ir-audio-btn--on');
                btnSystem.querySelector('span').textContent = 'System Audio: ON';

                stream.getAudioTracks().forEach(function (track) {
                    track.addEventListener('ended', function () {
                        stopSystemRecorder();
                        systemStream = null;
                        btnSystem.classList.remove('ir-audio-btn--on');
                        btnSystem.classList.add('ir-audio-btn--off');
                        btnSystem.querySelector('span').textContent = 'System Audio: OFF';
                        updateStatus();
                    });
                });

                startSystemRecorder();
                updateStatus();
            })
            .catch(function (err) {
                console.error('System audio error:', err);
                if (err.name !== 'NotAllowedError') {
                    alert('Could not capture system audio. Make sure to check "Share system audio" when prompted.');
                }
            });
        }
    });

    // -----------------------------------------------------------------------
    // AI Question Generation
    // -----------------------------------------------------------------------
    btnGenerate.addEventListener('click', function () {
        btnGenerate.disabled = true;
        btnGenerate.innerHTML = '<span class="spinner"></span> Generating...';

        flushTranscript();

        fetch(URLS.questions, {
            method: 'POST',
            headers: headers(true),
            body: JSON.stringify({ current_offset: elapsedSeconds() }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) {
                alert(data.error);
                return;
            }
            renderQuestions(data.questions || []);
        })
        .catch(function (err) {
            console.error('Generate questions error:', err);
            alert('Failed to generate questions. Please try again.');
        })
        .finally(function () {
            btnGenerate.disabled = false;
            btnGenerate.textContent = 'Generate';
        });
    });

    function renderQuestions(questions) {
        questions.forEach(function (q) {
            var card = document.createElement('div');
            card.className = 'ir-question-card';
            card.dataset.questionId = q.id;
            card.dataset.status = q.status;

            card.innerHTML =
                '<div class="ir-question-card__header">' +
                    '<span class="badge badge-outline" style="font-size:10px;">' + escapeHtml(q.question_type || 'follow_up') + '</span>' +
                    '<span class="badge" style="font-size:10px; background:var(--gray-100);">' + escapeHtml(q.difficulty || 'medium') + '</span>' +
                    (q.skill_area ? '<span class="badge" style="font-size:10px; background:var(--primary-light); color:var(--primary);">' + escapeHtml(q.skill_area) + '</span>' : '') +
                '</div>' +
                '<p class="ir-question-card__text">' + escapeHtml(q.question_text) + '</p>' +
                '<div class="ir-question-card__actions">' +
                    '<button class="btn btn-sm btn-outline btn-ask" title="Mark as asked">Ask</button>' +
                    '<button class="btn btn-sm btn-outline btn-skip" title="Skip this question">Skip</button>' +
                '</div>';

            if (questionsEl.firstChild) {
                questionsEl.insertBefore(card, questionsEl.firstChild);
            } else {
                questionsEl.innerHTML = '';
                questionsEl.appendChild(card);
            }

            card.querySelector('.btn-ask').addEventListener('click', function () {
                markQuestion(q.id, 'asked', card);
            });
            card.querySelector('.btn-skip').addEventListener('click', function () {
                markQuestion(q.id, 'skipped', card);
            });
        });
    }

    function markQuestion(qId, status, cardEl) {
        fetch(URLS.questionStatus + '/' + qId + '/status', {
            method: 'PUT',
            headers: headers(true),
            body: JSON.stringify({ status: status, current_offset: elapsedSeconds() }),
        })
        .then(function (r) { return r.json(); })
        .then(function () {
            cardEl.dataset.status = status;
            cardEl.className = 'ir-question-card ir-question-card--' + status;
            var actionsDiv = cardEl.querySelector('.ir-question-card__actions');

            if (status === 'asked') {
                actionsDiv.innerHTML =
                    '<button class="btn btn-sm btn-primary btn-evaluate">Evaluate Answer</button>' +
                    '<button class="btn btn-sm btn-outline btn-skip">Skip</button>';
                actionsDiv.querySelector('.btn-evaluate').addEventListener('click', function () {
                    evaluateAnswer(qId, cardEl);
                });
                actionsDiv.querySelector('.btn-skip').addEventListener('click', function () {
                    markQuestion(qId, 'skipped', cardEl);
                });
            } else if (status === 'skipped') {
                actionsDiv.innerHTML = '<span class="text-muted" style="font-size:12px;">Skipped</span>';
            }
        })
        .catch(function (err) {
            console.error('Update question status error:', err);
        });
    }

    // -----------------------------------------------------------------------
    // Answer Evaluation
    // -----------------------------------------------------------------------
    function evaluateAnswer(qId, cardEl) {
        var recentEntries = transcriptEl.querySelectorAll('.ir-transcript-entry');
        var answerParts = [];
        for (var i = recentEntries.length - 1; i >= Math.max(0, recentEntries.length - 10); i--) {
            var speakerEl = recentEntries[i].querySelector('.ir-transcript-entry__speaker');
            if (speakerEl && speakerEl.classList.contains('ir-transcript-entry__speaker--candidate')) {
                var textEl = recentEntries[i].querySelector('.ir-transcript-entry__text');
                if (textEl) answerParts.unshift(textEl.textContent);
            }
        }
        var answerText = answerParts.join(' ').trim();
        if (!answerText) {
            alert('No candidate speech detected yet. Please wait for the candidate to answer before evaluating.');
            return;
        }

        var evalBtn = cardEl.querySelector('.btn-evaluate');
        if (evalBtn) {
            evalBtn.disabled = true;
            evalBtn.innerHTML = '<span class="spinner"></span>';
        }

        fetch(URLS.evaluate, {
            method: 'POST',
            headers: headers(true),
            body: JSON.stringify({
                question_id: qId,
                answer_text: answerText,
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) {
                alert(data.error);
                return;
            }
            var evaluation = data.evaluation || {};
            renderEvaluation(evaluation, cardEl);

            cardEl.dataset.status = 'answered';
            cardEl.className = 'ir-question-card ir-question-card--answered';
            var actionsDiv = cardEl.querySelector('.ir-question-card__actions');
            actionsDiv.innerHTML = '<span style="font-size:12px; color:var(--success); font-weight:600;">Evaluated (' + (evaluation.score || '-') + '/100)</span>';
        })
        .catch(function (err) {
            console.error('Evaluate answer error:', err);
            alert('Evaluation failed. Please try again.');
            if (evalBtn) {
                evalBtn.disabled = false;
                evalBtn.textContent = 'Evaluate Answer';
            }
        });
    }

    function renderEvaluation(evaluation, cardEl) {
        var scoreColor = (evaluation.score || 0) >= 70 ? 'var(--success)' :
                         (evaluation.score || 0) >= 40 ? 'var(--warning)' : 'var(--danger)';
        var depthColors = {
            expert: 'var(--success)',
            deep: '#22c55e',
            working: 'var(--warning)',
            surface: 'var(--danger)',
        };
        var depthColor = depthColors[evaluation.depth] || 'var(--gray-500)';

        var html =
            '<div class="ir-eval-card">' +
                '<div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">' +
                    '<span class="ir-eval-card__score" style="color:' + scoreColor + ';">' + (evaluation.score || '-') + '</span>' +
                    '<span style="color:var(--gray-400);">/100</span>' +
                    (evaluation.depth ? '<span class="ir-eval-card__depth" style="background:' + depthColor + '; color:#fff;">' + escapeHtml(evaluation.depth) + '</span>' : '') +
                '</div>' +
                (evaluation.feedback ? '<p style="margin:0; font-size:13px; line-height:1.5;">' + escapeHtml(evaluation.feedback) + '</p>' : '') +
                (evaluation.strengths && evaluation.strengths.length ?
                    '<div style="margin-top:8px;"><strong style="font-size:12px; color:var(--success);">Strengths:</strong> ' +
                    evaluation.strengths.map(function(s) { return escapeHtml(s); }).join(', ') + '</div>' : '') +
                (evaluation.gaps && evaluation.gaps.length ?
                    '<div style="margin-top:4px;"><strong style="font-size:12px; color:var(--warning);">Gaps:</strong> ' +
                    evaluation.gaps.map(function(g) { return escapeHtml(g); }).join(', ') + '</div>' : '') +
            '</div>';

        evaluationEl.innerHTML = html + evaluationEl.innerHTML;
    }

    // -----------------------------------------------------------------------
    // Notes auto-save
    // -----------------------------------------------------------------------
    if (notesEl) {
        notesEl.addEventListener('input', function () {
            clearTimeout(notesDebounce);
            notesDebounce = setTimeout(function () {
                fetch(URLS.notes, {
                    method: 'PUT',
                    headers: headers(true),
                    body: JSON.stringify({ notes: notesEl.value }),
                }).catch(function (err) {
                    console.error('Notes save error:', err);
                });
            }, 2000);
        });
    }

    // -----------------------------------------------------------------------
    // End Session
    // -----------------------------------------------------------------------
    btnEnd.addEventListener('click', function () {
        if (!confirm('End this interview session? The AI summary will be generated automatically.')) return;

        flushTranscript();
        stopMicRecorder();
        stopSystemRecorder();
        if (micStream) { micStream.getTracks().forEach(function (t) { t.stop(); }); micStream = null; }
        if (systemStream) { systemStream.getTracks().forEach(function (t) { t.stop(); }); systemStream = null; }
        stopRecognition();
        clearInterval(elapsedInterval);
        clearInterval(flushTimer);

        btnEnd.disabled = true;
        btnEnd.innerHTML = '<span class="spinner"></span> Ending...';

        fetch(URLS.end, {
            method: 'POST',
            headers: headers(true),
            body: JSON.stringify({}),
        })
        .then(function (r) { return r.json(); })
        .then(function () {
            window.location.href = URLS.summary;
        })
        .catch(function (err) {
            console.error('End session error:', err);
            alert('Failed to end session. Please try again.');
            btnEnd.disabled = false;
            btnEnd.textContent = 'End Session';
        });
    });

    // -----------------------------------------------------------------------
    // Cleanup on page unload
    // -----------------------------------------------------------------------
    window.addEventListener('beforeunload', function () {
        flushTranscript();
    });

})();
