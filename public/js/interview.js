/**
 * Live AI Interview Assistant — Client-side Engine
 *
 * Supports multiple ASR providers:
 *  - whisper:       Local Whisper via FastAPI (default, no cloud cost)
 *  - azure_speech:  Azure Speech Services with ConversationTranscriber
 *                   (speaker diarization, phrase boosting, real-time streaming)
 *
 * Enhanced features (configurable via settings):
 *  - Speaker diarization: identify individual speakers (Azure only)
 *  - Screen capture: periodic screenshots when candidate shares screen
 *  - LLM correction: AI-powered transcript domain correction
 */
(function () {
    'use strict';

    // -----------------------------------------------------------------------
    // DOM & config
    // -----------------------------------------------------------------------
    var room = document.getElementById('interview-room');
    if (!room) return;

    var CSRF   = room.dataset.csrf;
    var URLS   = {
        transcript:     room.dataset.urlTranscript,
        questions:      room.dataset.urlQuestions,
        evaluate:       room.dataset.urlEvaluate,
        questionStatus: room.dataset.urlQuestionStatus,
        state:          room.dataset.urlState,
        notes:          room.dataset.urlNotes,
        end:            room.dataset.urlEnd,
        summary:        room.dataset.urlSummary,
        transcribe:     room.dataset.urlTranscribe,
        asrToken:       room.dataset.urlAsrToken || '',
        correctTranscript: room.dataset.urlCorrectTranscript || '',
    };
    var startedAt = room.dataset.startedAt ? new Date(room.dataset.startedAt) : new Date();

    // ASR provider config
    var ASR_PROVIDER       = room.dataset.asrProvider || 'whisper';
    var ASR_REGION         = room.dataset.asrRegion || '';
    var PHRASE_HINTS       = (room.dataset.phraseHints || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    var ENABLE_DIARIZATION = room.dataset.enableDiarization === '1';
    var ENABLE_SCREEN_CAP  = room.dataset.enableScreenCapture === '1';
    var ENABLE_LLM_CORR    = room.dataset.enableLlmCorrection === '1';

    var btnMic          = document.getElementById('btn-mic');
    var btnSystem       = document.getElementById('btn-system');
    var btnGenerate     = document.getElementById('btn-generate-questions');
    var btnEnd          = document.getElementById('btn-end-session');
    var timerEl         = document.getElementById('ir-timer');
    var transcriptEl    = document.getElementById('ir-transcript');
    var interimEl       = document.getElementById('ir-interim');
    var statusEl        = document.getElementById('ir-transcript-status');
    var questionsEl     = document.getElementById('ir-questions');
    var evaluationEl    = document.getElementById('ir-evaluation');
    var notesEl         = document.getElementById('ir-notes');
    var modeHintEl      = document.getElementById('mode-hint');

    // -----------------------------------------------------------------------
    // Interview mode: 'one-to-one' (mic=You, system=Candidate) or 'panel' (diarization)
    // -----------------------------------------------------------------------
    var interviewMode = 'one-to-one';

    (function initModeToggle() {
        var toggleEl = document.getElementById('interview-mode-toggle');
        if (!toggleEl) return;
        toggleEl.addEventListener('click', function (evt) {
            var btn = evt.target.closest('.ir-mode-btn');
            if (!btn || btn.classList.contains('ir-mode-btn--active')) return;
            // Don't allow switching while audio is active
            if (micStream || systemStream) {
                alert('Please stop all audio before switching interview mode.');
                return;
            }
            toggleEl.querySelectorAll('.ir-mode-btn').forEach(function (b) { b.classList.remove('ir-mode-btn--active'); });
            btn.classList.add('ir-mode-btn--active');
            interviewMode = btn.dataset.mode;
            if (modeHintEl) {
                modeHintEl.textContent = interviewMode === 'one-to-one'
                    ? 'Mic = You, System Audio = Candidate'
                    : 'All speakers diarized as Speaker 1, 2, 3...';
            }
            console.log('Interview mode: ' + interviewMode);
        });
    })();

    // -----------------------------------------------------------------------
    // State
    // -----------------------------------------------------------------------
    var micStream       = null;
    var systemStream    = null;
    var recognition     = null;
    var isRecognizing   = false;

    // Whisper mode state
    var micRecorder         = null;
    var micRecorderInterval = null;
    var isMicTranscribing   = false;
    var systemRecorder         = null;
    var systemRecorderInterval = null;
    var isSystemTranscribing   = false;

    // Azure Speech mode state
    var azureMicRecognizer     = null;
    var azureSystemTranscriber = null;
    var azureMicPushStream     = null;
    var azureSystemPushStream  = null;
    var micAudioContext         = null;
    var micAudioProcessor      = null;
    var systemAudioContext      = null;
    var systemAudioProcessor   = null;
    var azureToken             = null;
    var azureTokenExpiry       = 0;
    var speakerMap             = {}; // Guest-1 → "Speaker 1" etc.
    var speakerCounter         = 0;

    // Screen capture state
    var screenVideoTrack     = null;
    var screenCaptureTimer   = null;
    var captureCanvas        = null;
    var captureCtx           = null;
    var lastScreenshotHash   = '';

    // LLM correction state
    var correctionVocabulary = []; // Accumulated domain terms
    var correctionQueue      = [];
    var isCorrecting         = false;

    // Deduplication state — prevents same speech from appearing twice
    var recentTranscripts    = []; // { text, time, speaker }
    var DEDUP_WINDOW_MS      = 10000; // 10 second window

    // Mic-based speaker identification for system audio diarization.
    // When system audio is active, we stop the mic recognizer entirely and
    // let ConversationTranscriber handle ALL transcription (it diarizes
    // perfectly). To identify which diarized speaker is the interviewer,
    // we monitor mic audio energy levels. When energy is sustained above
    // threshold, the interviewer is speaking — we correlate with speakerId.
    // Requires multiple correlated detections (votes) before locking.
    var micEnergyActive        = false;   // true when SUSTAINED mic energy detected
    var micEnergyAnalyser      = null;    // AnalyserNode for mic energy detection
    var micEnergyInterval      = null;    // polling interval for energy check
    var interviewerSpeakerId   = null;    // the diarized speakerId identified as "You"
    var MIC_ENERGY_THRESHOLD   = 0.08;    // RMS threshold — voice ~0.05-0.3, typing ~0.01-0.03
    var micEnergyConsecutive   = 0;       // consecutive frames above threshold
    var MIC_SUSTAIN_FRAMES     = 4;       // need 4 consecutive frames (400ms) = sustained voice
    var speakerIdVotes         = {};      // { speakerId: count } — tracks correlations
    var SPEAKER_VOTE_THRESHOLD = 3;       // need 3+ votes before locking interviewerSpeakerId

    var transcriptBuffer = [];
    var flushTimer       = null;
    var elapsedInterval  = null;
    var notesDebounce    = null;

    var MIC_CHUNK_DURATION    = 5000;
    var SYSTEM_CHUNK_DURATION = 7000;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------
    function headers(json) {
        var h = { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' };
        if (json) h['Content-Type'] = 'application/json';
        return h;
    }

    function elapsedSeconds() {
        return Math.floor((Date.now() - startedAt.getTime()) / 1000);
    }

    function fmtTime(secs) {
        var h = String(Math.floor(secs / 3600)).padStart(2, '0');
        var m = String(Math.floor((secs % 3600) / 60)).padStart(2, '0');
        var s = String(secs % 60).padStart(2, '0');
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

    function looksLikeCode(text) {
        // Reject obvious non-code: meeting text, tables, general prose
        // Must contain at least some code-like patterns
        var codePatterns = [
            /[{}\[\]();]/,              // Braces, brackets, parens, semicolons
            /\b(function|def|class|var|let|const|import|return|if|for|while)\b/,
            /[=!<>]=|=>|->|::/,         // Operators
            /^\s*(#include|package|using|from)\b/m, // Language keywords at line start
            /\b(console\.log|print|System\.out|cout)\b/, // Common output
            /^\s*\/\//m,                // Single-line comments
            /^\s*#\s*\w/m,              // Python/Ruby comments or preprocessor
        ];
        var matchCount = 0;
        for (var i = 0; i < codePatterns.length; i++) {
            if (codePatterns[i].test(text)) matchCount++;
        }
        // Reject text that looks like a table/spreadsheet (lots of | separators)
        var pipeCount = (text.match(/\|/g) || []).length;
        if (pipeCount > 5 && matchCount < 2) return false;
        // Need at least 2 code-like patterns to accept
        return matchCount >= 2;
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
    // Web Speech API — interim text preview (used with Whisper mode only)
    // -----------------------------------------------------------------------
    var SpeechRecognitionApi = window.SpeechRecognition || window.webkitSpeechRecognition;

    function initRecognition() {
        if (!SpeechRecognitionApi) return;
        recognition = new SpeechRecognitionApi();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = 'en-US';
        recognition.maxAlternatives = 1;

        recognition.onstart = function () { isRecognizing = true; updateStatus(); };
        recognition.onresult = function (event) {
            var interim = '';
            for (var i = event.resultIndex; i < event.results.length; i++) {
                if (event.results[i].isFinal) {
                    var finalText = event.results[i][0].transcript.trim();
                    // In google_speech mode, Web Speech API is the primary transcriber
                    if (ASR_PROVIDER === 'google_speech' && finalText) {
                        addTranscriptEntry('interviewer', finalText, event.results[i][0].confidence, 'You');
                    }
                    interimEl.style.display = 'none';
                } else {
                    interim += event.results[i][0].transcript;
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
            if (micStream) setTimeout(restartRecognition, 200);
            else updateStatus();
        };
    }

    function startRecognition() {
        if (!recognition) initRecognition();
        if (!recognition) return;
        if (!isRecognizing) { try { recognition.start(); } catch (e) { /* ok */ } }
    }

    function stopRecognition() {
        if (recognition && isRecognizing) { try { recognition.stop(); } catch (e) { /* ok */ } }
    }

    function restartRecognition() { if (micStream) startRecognition(); }

    function updateStatus() {
        var parts = [];
        if (micStream && !azureSystemTranscriber) parts.push('Mic: recording');
        else if (micStream && azureSystemTranscriber) parts.push('Mic: speaker ID');
        if (systemStream) parts.push('System: recording');
        if (ASR_PROVIDER === 'azure_speech') {
            if (azureMicRecognizer) parts.push('Azure ASR (mic)');
            if (azureSystemTranscriber) parts.push('Azure ASR (diarization)');
        } else if (ASR_PROVIDER === 'google_speech') {
            if (micStream && isRecognizing) parts.push('Google Speech');
        }
        if (screenVideoTrack) parts.push('Screen capture');
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
    function normalizeForDedup(str) {
        return str.toLowerCase().replace(/[^a-z0-9\s]/g, '').replace(/\s+/g, ' ').trim();
    }

    function isSimilarText(a, b) {
        var na = normalizeForDedup(a);
        var nb = normalizeForDedup(b);
        if (na === nb) return true;
        if (na.length < 5 || nb.length < 5) return na === nb;
        // Substring containment
        var shorter = na.length <= nb.length ? na : nb;
        var longer  = na.length > nb.length ? na : nb;
        if (longer.indexOf(shorter) >= 0) return true;
        // Word overlap ratio — handles different transcription of same audio
        var wordsA = na.split(' ');
        var wordsB = nb.split(' ');
        var shared = 0;
        for (var w = 0; w < wordsA.length; w++) {
            if (wordsB.indexOf(wordsA[w]) >= 0) shared++;
        }
        var minLen = Math.min(wordsA.length, wordsB.length);
        return minLen > 2 && (shared / minLen) > 0.6;
    }

    /**
     * Simple deduplication — prevents the same text from appearing twice
     * within the dedup window (same speaker only).
     */
    function checkDuplicate(text, speaker) {
        var now = Date.now();
        recentTranscripts = recentTranscripts.filter(function (r) {
            return now - r.time < DEDUP_WINDOW_MS;
        });

        for (var i = 0; i < recentTranscripts.length; i++) {
            if (recentTranscripts[i].speaker === speaker && isSimilarText(recentTranscripts[i].text, text)) {
                return true;
            }
        }

        recentTranscripts.push({ text: text, time: now, speaker: speaker });
        return false;
    }

    function addTranscriptEntry(speaker, text, confidence, speakerLabel) {
        // Skip screen capture entries from dedup check
        var isScreenEntry = text.indexOf('[Screen:') === 0 || text.indexOf('[Screen Share') === 0;
        if (!isScreenEntry) {
            if (checkDuplicate(text, speaker)) {
                console.log('Dedup: dropping duplicate "' + text.substring(0, 50) + '..."');
                return;
            }
        }

        var empty = transcriptEl.querySelector('.ir-transcript-empty');
        if (empty) empty.remove();

        var offset = elapsedSeconds();
        var displayLabel = speakerLabel || (speaker === 'interviewer' ? 'You' : 'Candidate');
        var speakerClass = speaker === 'interviewer' ? 'you' : 'candidate';

        var entry = document.createElement('div');
        entry.className = 'ir-transcript-entry';
        entry.dataset.offset = offset;
        entry.innerHTML =
            '<span class="ir-transcript-entry__time">' + fmtTime(offset) + '</span>' +
            '<span class="ir-transcript-entry__speaker ir-transcript-entry__speaker--' + speakerClass + '">' +
                escapeHtml(displayLabel) +
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

        if (transcriptBuffer.length >= 10) flushTranscript();

        // Queue for LLM correction if enabled
        if (ENABLE_LLM_CORR && URLS.correctTranscript) {
            correctionQueue.push({ text: text, entryEl: entry, offset: offset });
            scheduleLlmCorrection();
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
    // Azure Speech SDK — Token management
    // -----------------------------------------------------------------------
    function getAzureToken() {
        if (azureToken && Date.now() < azureTokenExpiry) {
            return Promise.resolve(azureToken);
        }
        return fetch(URLS.asrToken, {
            method: 'POST',
            headers: headers(true),
            body: JSON.stringify({}),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) throw new Error(data.error);
            azureToken = data.token;
            ASR_REGION = data.region || ASR_REGION;
            azureTokenExpiry = Date.now() + 8 * 60 * 1000; // refresh every 8 min (token lasts 10)
            return azureToken;
        });
    }

    // Refresh token periodically for long interviews
    var tokenRefreshTimer = null;
    function startTokenRefresh() {
        if (tokenRefreshTimer) return;
        tokenRefreshTimer = setInterval(function () {
            getAzureToken().catch(function (err) {
                console.warn('Token refresh failed:', err);
            });
        }, 8 * 60 * 1000);
    }

    // -----------------------------------------------------------------------
    // Azure Speech — Feed MediaStream to PushAudioInputStream
    // -----------------------------------------------------------------------
    function createAudioPipeline(mediaStream) {
        var sdk = window.SpeechSDK;
        var pushStream = sdk.AudioInputStream.createPushStream(
            sdk.AudioStreamFormat.getWaveFormatPCM(16000, 16, 1)
        );

        var audioCtx;
        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 16000 });
        } catch (e) {
            // Fallback: some browsers don't support non-default sample rate
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }

        var source = audioCtx.createMediaStreamSource(mediaStream);
        var processor = audioCtx.createScriptProcessor(4096, 1, 1);

        var needsResample = audioCtx.sampleRate !== 16000;

        // Connect through a silent gain node — processor needs to be connected
        // to destination to fire onaudioprocess, but we don't want to play audio
        var silentGain = audioCtx.createGain();
        silentGain.gain.value = 0;
        source.connect(processor);
        processor.connect(silentGain);
        silentGain.connect(audioCtx.destination);

        processor.onaudioprocess = function (event) {
            var float32 = event.inputBuffer.getChannelData(0);

            // Resample if needed
            var samples = float32;
            if (needsResample) {
                var ratio = audioCtx.sampleRate / 16000;
                var newLen = Math.round(float32.length / ratio);
                samples = new Float32Array(newLen);
                for (var i = 0; i < newLen; i++) {
                    samples[i] = float32[Math.round(i * ratio)] || 0;
                }
            }

            // Convert Float32 to Int16 PCM
            var int16 = new Int16Array(samples.length);
            for (var j = 0; j < samples.length; j++) {
                var s = Math.max(-1, Math.min(1, samples[j]));
                int16[j] = s < 0 ? s * 0x8000 : s * 0x7FFF;
            }

            pushStream.write(int16.buffer);
        };

        return { pushStream: pushStream, audioContext: audioCtx, processor: processor, source: source };
    }

    function teardownAudioPipeline(pipeline) {
        if (!pipeline) return;
        try { pipeline.processor.disconnect(); } catch (e) { /* ok */ }
        try { pipeline.source.disconnect(); } catch (e) { /* ok */ }
        if (pipeline.micSource) { try { pipeline.micSource.disconnect(); } catch (e) { /* ok */ } }
        if (pipeline.micGain) { try { pipeline.micGain.disconnect(); } catch (e) { /* ok */ } }
        if (pipeline.mixer) { try { pipeline.mixer.disconnect(); } catch (e) { /* ok */ } }
        try { pipeline.pushStream.close(); } catch (e) { /* ok */ }
        try { pipeline.audioContext.close(); } catch (e) { /* ok */ }
    }

    // Creates an audio pipeline that mixes system audio + mic audio into a single
    // PushAudioInputStream for the ConversationTranscriber. This lets the diarizer
    // hear both the interviewer (mic) and meeting participants (system audio),
    // so it can naturally diarize all speakers including the interviewer.
    function createMixedAudioPipeline(systemMediaStream, micMediaStream) {
        var sdk = window.SpeechSDK;
        var format = sdk.AudioStreamFormat.getWaveFormatPCM(16000, 16, 1);
        var pushStream = sdk.AudioInputStream.createPushStream(format);

        var audioCtx;
        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 16000 });
        } catch (e) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }

        var systemSource = audioCtx.createMediaStreamSource(systemMediaStream);
        var micSource = audioCtx.createMediaStreamSource(micMediaStream);

        // Mixer node sums both audio sources
        var mixer = audioCtx.createGain();
        mixer.gain.value = 1.0;

        // Slight mic boost so diarizer distinguishes interviewer clearly
        var micGain = audioCtx.createGain();
        micGain.gain.value = 1.2;

        systemSource.connect(mixer);
        micSource.connect(micGain);
        micGain.connect(mixer);

        var processor = audioCtx.createScriptProcessor(4096, 1, 1);
        var needsResample = audioCtx.sampleRate !== 16000;

        var silentGain = audioCtx.createGain();
        silentGain.gain.value = 0;
        mixer.connect(processor);
        processor.connect(silentGain);
        silentGain.connect(audioCtx.destination);

        processor.onaudioprocess = function (event) {
            var float32 = event.inputBuffer.getChannelData(0);
            var samples = float32;
            if (needsResample) {
                var ratio = audioCtx.sampleRate / 16000;
                var newLen = Math.round(float32.length / ratio);
                samples = new Float32Array(newLen);
                for (var i = 0; i < newLen; i++) {
                    samples[i] = float32[Math.round(i * ratio)] || 0;
                }
            }
            var int16 = new Int16Array(samples.length);
            for (var j = 0; j < samples.length; j++) {
                var s = Math.max(-1, Math.min(1, samples[j]));
                int16[j] = s < 0 ? s * 0x8000 : s * 0x7FFF;
            }
            pushStream.write(int16.buffer);
        };

        return {
            pushStream: pushStream, audioContext: audioCtx, processor: processor,
            source: systemSource, micSource: micSource, micGain: micGain, mixer: mixer
        };
    }

    // -----------------------------------------------------------------------
    // Azure Speech — Mic Recognizer (single speaker = interviewer)
    // -----------------------------------------------------------------------
    function startAzureMicRecognizer() {
        if (!window.SpeechSDK) {
            console.error('Azure Speech SDK not loaded');
            return;
        }
        var sdk = window.SpeechSDK;

        getAzureToken().then(function (token) {
            startTokenRefresh();

            var pipeline = createAudioPipeline(micStream);
            azureMicPushStream = pipeline;

            var speechConfig = sdk.SpeechConfig.fromAuthorizationToken(token, ASR_REGION);
            speechConfig.speechRecognitionLanguage = 'en-US';

            var audioConfig = sdk.AudioConfig.fromStreamInput(pipeline.pushStream);
            azureMicRecognizer = new sdk.SpeechRecognizer(speechConfig, audioConfig);

            // Add phrase hints
            if (PHRASE_HINTS.length) {
                var phraseList = sdk.PhraseListGrammar.fromRecognizer(azureMicRecognizer);
                PHRASE_HINTS.forEach(function (phrase) { phraseList.addPhrase(phrase); });
            }

            azureMicRecognizer.recognizing = function (s, e) {
                if (e.result.text) {
                    interimEl.textContent = '[You] ... ' + e.result.text;
                    interimEl.style.display = 'block';
                }
            };

            azureMicRecognizer.recognized = function (s, e) {
                if (e.result.reason === sdk.ResultReason.RecognizedSpeech && e.result.text) {
                    addTranscriptEntry('interviewer', e.result.text, null, 'You');
                    interimEl.style.display = 'none';
                }
            };

            azureMicRecognizer.canceled = function (s, e) {
                if (e.reason === sdk.CancellationReason.Error) {
                    console.error('Azure mic recognizer error:', e.errorDetails);
                    // Fall back to Whisper for this session
                    if (e.errorCode === sdk.CancellationErrorCode.AuthenticationFailure) {
                        console.warn('Azure auth failed, falling back to Whisper');
                        stopAzureMicRecognizer();
                        ASR_PROVIDER = 'whisper';
                        startMicRecorder();
                        startRecognition();
                    }
                }
            };

            azureMicRecognizer.sessionStarted = function (s, e) {
                console.log('Azure mic session started, id:', e.sessionId);
            };

            azureMicRecognizer.speechStartDetected = function (s, e) {
                console.log('Azure mic: speech start detected');
            };

            azureMicRecognizer.speechEndDetected = function (s, e) {
                console.log('Azure mic: speech end detected');
            };

            azureMicRecognizer.startContinuousRecognitionAsync(
                function () { console.log('Azure mic recognizer started'); updateStatus(); },
                function (err) { console.error('Azure mic start error:', err); }
            );
        }).catch(function (err) {
            console.error('Azure token error, falling back to Whisper:', err);
            ASR_PROVIDER = 'whisper';
            startMicRecorder();
            startRecognition();
        });
    }

    function stopAzureMicRecognizer() {
        if (azureMicRecognizer) {
            try { azureMicRecognizer.stopContinuousRecognitionAsync(); } catch (e) { /* ok */ }
            try { azureMicRecognizer.close(); } catch (e) { /* ok */ }
            azureMicRecognizer = null;
        }
        teardownAudioPipeline(azureMicPushStream);
        azureMicPushStream = null;
    }

    // -----------------------------------------------------------------------
    // Azure Speech — System Audio ConversationTranscriber (diarization)
    // -----------------------------------------------------------------------
    function mapSpeakerId(rawId) {
        if (!rawId || rawId === 'Unknown') return 'Speaker';
        if (!speakerMap[rawId]) {
            speakerCounter++;
            speakerMap[rawId] = 'Speaker ' + speakerCounter;
        }
        return speakerMap[rawId];
    }

    // -----------------------------------------------------------------------
    // Mic Energy Monitor — detects when interviewer is speaking
    // Uses AnalyserNode on mic stream to measure audio energy (RMS).
    // When energy is high, the interviewer is talking. We correlate this
    // with the ConversationTranscriber's speakerId to identify "You".
    // -----------------------------------------------------------------------
    function startMicEnergyMonitor() {
        if (!micStream || micEnergyAnalyser) return;
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var source = ctx.createMediaStreamSource(micStream);
            micEnergyAnalyser = ctx.createAnalyser();
            micEnergyAnalyser.fftSize = 512;
            source.connect(micEnergyAnalyser);
            // Don't connect to destination — silent monitoring only

            var dataArray = new Float32Array(micEnergyAnalyser.fftSize);

            micEnergyInterval = setInterval(function () {
                if (!micEnergyAnalyser) return;
                micEnergyAnalyser.getFloatTimeDomainData(dataArray);
                // Calculate RMS energy
                var sum = 0;
                for (var i = 0; i < dataArray.length; i++) {
                    sum += dataArray[i] * dataArray[i];
                }
                var rms = Math.sqrt(sum / dataArray.length);

                // Require sustained energy — voice sustains for hundreds of ms,
                // typing/clicks/notifications are brief spikes (<200ms)
                if (rms > MIC_ENERGY_THRESHOLD) {
                    micEnergyConsecutive++;
                } else {
                    micEnergyConsecutive = 0;
                    micEnergyActive = false;
                }
                // Only flag as active after sustained voice (MIC_SUSTAIN_FRAMES consecutive)
                if (micEnergyConsecutive >= MIC_SUSTAIN_FRAMES) {
                    micEnergyActive = true;
                }
            }, 100); // check every 100ms

            // Store context for cleanup
            micEnergyAnalyser._ctx = ctx;
            micEnergyAnalyser._source = source;
            console.log('Mic energy monitor started (threshold: ' + MIC_ENERGY_THRESHOLD + ')');
        } catch (e) {
            console.warn('Failed to start mic energy monitor:', e);
        }
    }

    function stopMicEnergyMonitor() {
        clearInterval(micEnergyInterval);
        micEnergyInterval = null;
        micEnergyActive = false;
        micEnergyConsecutive = 0;
        if (micEnergyAnalyser) {
            try { micEnergyAnalyser._source.disconnect(); } catch (e) { /* ok */ }
            try { micEnergyAnalyser._ctx.close(); } catch (e) { /* ok */ }
            micEnergyAnalyser = null;
        }
    }

    function startAzureSystemTranscriber() {
        if (!window.SpeechSDK) return;
        var sdk = window.SpeechSDK;
        var isOneToOne = interviewMode === 'one-to-one';

        if (isOneToOne) {
            // ONE-TO-ONE MODE: Keep mic recognizer running separately (= "You").
            // System audio uses ConversationTranscriber but ALL speakers are "Candidate".
            // No mixing, no energy detection needed — clean separation.
            console.log('1-on-1 mode: mic = You, system audio = Candidate');
        } else {
            // PANEL MODE: Stop mic recognizer, mix mic into system pipeline,
            // ConversationTranscriber diarizes everyone, mic energy identifies "You".
            if (azureMicRecognizer) {
                console.log('Panel mode: stopping mic recognizer — diarization takes over');
                stopAzureMicRecognizer();
            }
            if (micStream) {
                startMicEnergyMonitor();
            }
        }

        getAzureToken().then(function (token) {
            startTokenRefresh();

            var pipeline;
            if (!isOneToOne && micStream) {
                pipeline = createMixedAudioPipeline(systemStream, micStream);
                console.log('Mixed audio pipeline: system + mic → ConversationTranscriber');
            } else {
                pipeline = createAudioPipeline(systemStream);
                console.log('System-only audio pipeline → ConversationTranscriber');
            }
            azureSystemPushStream = pipeline;

            var speechConfig = sdk.SpeechConfig.fromAuthorizationToken(token, ASR_REGION);
            speechConfig.speechRecognitionLanguage = 'en-US';
            var audioConfig = sdk.AudioConfig.fromStreamInput(pipeline.pushStream);

            azureSystemTranscriber = new sdk.ConversationTranscriber(speechConfig, audioConfig);

            if (PHRASE_HINTS.length) {
                var phraseList = sdk.PhraseListGrammar.fromRecognizer(azureSystemTranscriber);
                PHRASE_HINTS.forEach(function (phrase) { phraseList.addPhrase(phrase); });
            }

            azureSystemTranscriber.transcribing = function (s, e) {
                if (e.result.text) {
                    if (isOneToOne) {
                        // All system audio is the candidate
                        interimEl.textContent = '[Candidate] ... ' + e.result.text;
                    } else {
                        var rawId = e.result.speakerId;
                        var isInterviewer = rawId && rawId === interviewerSpeakerId;
                        var label = isInterviewer ? 'You' : mapSpeakerId(rawId);
                        interimEl.textContent = '[' + label + '] ... ' + e.result.text;
                    }
                    interimEl.style.display = 'block';
                }
            };

            azureSystemTranscriber.transcribed = function (s, e) {
                if (e.result.reason === sdk.ResultReason.RecognizedSpeech && e.result.text) {
                    if (isOneToOne) {
                        // All system audio = Candidate, simple
                        addTranscriptEntry('candidate', e.result.text, null, 'Candidate');
                    } else {
                        // Panel mode — vote-based interviewer identification
                        var rawId = e.result.speakerId;
                        if (micEnergyActive && rawId && rawId !== 'Unknown') {
                            speakerIdVotes[rawId] = (speakerIdVotes[rawId] || 0) + 1;
                            console.log('Speaker vote: ' + rawId + ' = ' + speakerIdVotes[rawId] + ' (need ' + SPEAKER_VOTE_THRESHOLD + ')');
                            if (!interviewerSpeakerId && speakerIdVotes[rawId] >= SPEAKER_VOTE_THRESHOLD) {
                                interviewerSpeakerId = rawId;
                                console.log('Locked interviewer speakerId: ' + rawId + ' (after ' + speakerIdVotes[rawId] + ' votes)');
                            }
                        }
                        var isInterviewer = rawId && rawId === interviewerSpeakerId;
                        if (isInterviewer) {
                            addTranscriptEntry('interviewer', e.result.text, null, 'You');
                        } else {
                            var spk = mapSpeakerId(rawId);
                            addTranscriptEntry('candidate', e.result.text, null, spk);
                        }
                    }
                    interimEl.style.display = 'none';
                }
            };

            azureSystemTranscriber.canceled = function (s, e) {
                if (e.reason === sdk.CancellationReason.Error) {
                    console.error('Azure system transcriber error:', e.errorDetails);
                }
            };

            azureSystemTranscriber.sessionStarted = function (s, e) {
                console.log('Azure system session started, id:', e.sessionId);
            };

            azureSystemTranscriber.startTranscribingAsync(
                function () { console.log('Azure system transcriber started (diarization + mic energy)'); updateStatus(); },
                function (err) { console.error('Azure system transcriber start error:', err); }
            );
        }).catch(function (err) {
            console.error('Azure token error for system audio:', err);
        });
    }

    function stopAzureSystemTranscriber(skipMicRestart) {
        if (azureSystemTranscriber) {
            try { azureSystemTranscriber.stopTranscribingAsync(); } catch (e) { /* ok */ }
            try { azureSystemTranscriber.close(); } catch (e) { /* ok */ }
            azureSystemTranscriber = null;
        }
        teardownAudioPipeline(azureSystemPushStream);
        azureSystemPushStream = null;
        stopMicEnergyMonitor();
        interviewerSpeakerId = null; // reset for next session
        speakerIdVotes = {};         // reset vote tally

        // Restart mic recognizer now that system audio is off
        // (skip when we're about to immediately restart the system transcriber)
        if (!skipMicRestart && micStream && ASR_PROVIDER === 'azure_speech' && !azureMicRecognizer) {
            console.log('System audio stopped — resuming mic recognizer');
            startAzureMicRecognizer();
        }
    }

    // -----------------------------------------------------------------------
    // Whisper mode — chunked MediaRecorder → Whisper API
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
            if (e.data && e.data.size > 500) onChunk(e.data);
        };
        recorder.onerror = function (e) { console.error('MediaRecorder error:', e.error); };
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
                        var newRec = createRecorder(stream, onChunk);
                        if (newRec) { recorder = newRec; newRec.start(); }
                    }
                }, 100);
            }
        }, chunkDuration);
        return { recorder: recorder, interval: interval };
    }

    function sendToWhisper(audioBlob, speaker, busyFlag, setBusyFlag) {
        if (busyFlag) return;
        setBusyFlag(true);
        var formData = new FormData();
        formData.append('file', audioBlob, 'audio.webm');
        fetch(URLS.transcribe, { method: 'POST', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var text = (data.text || '').trim();
            if (text) {
                addTranscriptEntry(speaker, text, null);
                if (speaker === 'interviewer') interimEl.style.display = 'none';
            }
        })
        .catch(function (err) { console.error('Whisper error (' + speaker + '):', err); })
        .finally(function () { setBusyFlag(false); });
    }

    function startMicRecorder() {
        if (!micStream || micStream.getAudioTracks().length === 0) return;
        var result = startChunkedRecording(micStream, MIC_CHUNK_DURATION, function (blob) {
            sendToWhisper(blob, 'interviewer', isMicTranscribing, function (v) { isMicTranscribing = v; });
        });
        micRecorder = result.recorder;
        micRecorderInterval = result.interval;
    }

    function stopMicRecorder() {
        clearInterval(micRecorderInterval); micRecorderInterval = null;
        if (micRecorder && micRecorder.state !== 'inactive') { try { micRecorder.stop(); } catch (e) { /* ok */ } }
        micRecorder = null;
    }

    function startSystemRecorder() {
        if (!systemStream || systemStream.getAudioTracks().length === 0) return;
        var result = startChunkedRecording(systemStream, SYSTEM_CHUNK_DURATION, function (blob) {
            sendToWhisper(blob, 'candidate', isSystemTranscribing, function (v) { isSystemTranscribing = v; });
        });
        systemRecorder = result.recorder;
        systemRecorderInterval = result.interval;
    }

    function stopSystemRecorder() {
        clearInterval(systemRecorderInterval); systemRecorderInterval = null;
        if (systemRecorder && systemRecorder.state !== 'inactive') { try { systemRecorder.stop(); } catch (e) { /* ok */ } }
        systemRecorder = null;
    }

    // -----------------------------------------------------------------------
    // Screen Capture — periodic screenshots from getDisplayMedia video track
    // -----------------------------------------------------------------------
    function startScreenCapture(videoTrack) {
        screenVideoTrack = videoTrack;
        if (!captureCanvas) {
            captureCanvas = document.createElement('canvas');
            captureCtx = captureCanvas.getContext('2d');
        }

        // Capture every 15 seconds
        screenCaptureTimer = setInterval(function () {
            if (!screenVideoTrack || screenVideoTrack.readyState !== 'live') {
                stopScreenCapture();
                return;
            }
            captureScreenshot();
        }, 15000);

        // Initial capture after 3 seconds
        setTimeout(captureScreenshot, 3000);

        updateStatus();
        console.log('Screen capture started (every 15s)');
    }

    function captureScreenshot() {
        if (!screenVideoTrack || screenVideoTrack.readyState !== 'live') return;

        try {
            var imageCapture = new ImageCapture(screenVideoTrack);
            imageCapture.grabFrame().then(function (bitmap) {
                captureCanvas.width = bitmap.width;
                captureCanvas.height = bitmap.height;
                captureCtx.drawImage(bitmap, 0, 0);
                bitmap.close();

                var dataUrl = captureCanvas.toDataURL('image/jpeg', 0.7);

                // Simple hash to avoid sending duplicate screenshots
                var simpleHash = dataUrl.length + '-' + dataUrl.substring(dataUrl.length - 100);
                if (simpleHash === lastScreenshotHash) return;
                lastScreenshotHash = simpleHash;

                // Send to server for Vision AI code extraction
                sendScreenshotForAnalysis(dataUrl);
            }).catch(function (err) {
                console.warn('Screenshot capture failed:', err);
            });
        } catch (e) {
            console.warn('ImageCapture not supported, using video element fallback');
            captureViaVideoElement();
        }
    }

    function captureViaVideoElement() {
        if (!screenVideoTrack || screenVideoTrack.readyState !== 'live') return;

        var video = document.createElement('video');
        video.srcObject = new MediaStream([screenVideoTrack]);
        video.muted = true;
        video.onloadedmetadata = function () {
            video.play().then(function () {
                captureCanvas.width = video.videoWidth || 1280;
                captureCanvas.height = video.videoHeight || 720;
                captureCtx.drawImage(video, 0, 0);
                video.srcObject = null;

                var dataUrl = captureCanvas.toDataURL('image/jpeg', 0.7);
                var simpleHash = dataUrl.length + '-' + dataUrl.substring(dataUrl.length - 100);
                if (simpleHash === lastScreenshotHash) return;
                lastScreenshotHash = simpleHash;

                sendScreenshotForAnalysis(dataUrl);
            });
        };
    }

    function sendScreenshotForAnalysis(dataUrl) {
        // Only send if we have the code extraction endpoint
        if (!URLS.correctTranscript) return;

        var base64 = dataUrl.split(',')[1];
        fetch(URLS.transcript.replace('/transcript', '/screenshot'), {
            method: 'POST',
            headers: headers(true),
            body: JSON.stringify({
                image: base64,
                offset_seconds: elapsedSeconds(),
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var code = (data.code || '').trim();
            if (code && looksLikeCode(code)) {
                addTranscriptEntry('candidate', '[Screen: Code] ' + code, null, 'Screen Share');
            }
        })
        .catch(function (err) {
            console.warn('Screenshot analysis error:', err);
        });
    }

    function stopScreenCapture() {
        clearInterval(screenCaptureTimer);
        screenCaptureTimer = null;
        screenVideoTrack = null;
        updateStatus();
    }

    // -----------------------------------------------------------------------
    // LLM Transcript Correction
    // -----------------------------------------------------------------------
    function scheduleLlmCorrection() {
        if (isCorrecting || correctionQueue.length < 3) return;

        isCorrecting = true;
        var batch = correctionQueue.splice(0, 5);
        var texts = batch.map(function (item) { return item.text; });

        fetch(URLS.correctTranscript, {
            method: 'POST',
            headers: headers(true),
            body: JSON.stringify({
                texts: texts,
                vocabulary: correctionVocabulary,
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.corrections) {
                data.corrections.forEach(function (corrected, idx) {
                    if (corrected && corrected !== texts[idx] && batch[idx] && batch[idx].entryEl) {
                        var textEl = batch[idx].entryEl.querySelector('.ir-transcript-entry__text');
                        if (textEl) {
                            textEl.textContent = corrected;
                            textEl.classList.add('ir-corrected');
                        }
                    }
                });
            }
            if (data.new_vocabulary) {
                data.new_vocabulary.forEach(function (term) {
                    if (correctionVocabulary.indexOf(term) === -1) {
                        correctionVocabulary.push(term);
                    }
                });
            }
        })
        .catch(function (err) { console.warn('LLM correction error:', err); })
        .finally(function () {
            isCorrecting = false;
            if (correctionQueue.length >= 3) scheduleLlmCorrection();
        });
    }

    // -----------------------------------------------------------------------
    // Mic Button — starts mic audio capture via appropriate provider
    // -----------------------------------------------------------------------
    btnMic.addEventListener('click', function () {
        if (micStream) {
            // Stop mic
            var hadSystemAudio = !!systemStream && !!azureSystemTranscriber;
            if (ASR_PROVIDER === 'azure_speech') {
                stopAzureMicRecognizer();
                stopMicEnergyMonitor();
            } else if (ASR_PROVIDER !== 'google_speech') {
                stopMicRecorder();
            }
            micStream.getTracks().forEach(function (t) { t.stop(); });
            micStream = null;
            btnMic.classList.remove('ir-audio-btn--on');
            btnMic.classList.add('ir-audio-btn--off');
            btnMic.querySelector('span').textContent = 'Mic: OFF';
            if (ASR_PROVIDER === 'whisper' || ASR_PROVIDER === 'google_speech') stopRecognition();
            // Panel mode: if system audio was running with mixed pipeline, restart without mic
            if (ASR_PROVIDER === 'azure_speech' && hadSystemAudio && interviewMode === 'panel') {
                console.log('Panel mode: mic stopped — restarting system transcriber without mic mix');
                stopAzureSystemTranscriber(true);
                startAzureSystemTranscriber();
            }
            updateStatus();
        } else {
            navigator.mediaDevices.getUserMedia({
                audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true }
            })
            .then(function (stream) {
                micStream = stream;
                btnMic.classList.remove('ir-audio-btn--off');
                btnMic.classList.add('ir-audio-btn--on');
                btnMic.querySelector('span').textContent = 'Mic: ON';

                if (ASR_PROVIDER === 'azure_speech') {
                    if (systemStream && azureSystemTranscriber && interviewMode === 'panel') {
                        // Panel mode: restart system transcriber with mixed pipeline
                        console.log('Panel mode: restarting system transcriber with mixed audio');
                        stopAzureSystemTranscriber(true);
                        startAzureSystemTranscriber();
                    } else {
                        // 1-on-1 mode OR no system audio — use mic recognizer directly
                        startAzureMicRecognizer();
                    }
                } else if (ASR_PROVIDER === 'google_speech') {
                    // Google Speech mode: Web Speech API for final results (uses Google engine in Chrome)
                    // No Whisper recorder for mic — Web Speech handles it
                    if (!SpeechRecognitionApi) {
                        alert('Google Speech mode requires Chrome browser. Falling back to Whisper.');
                        ASR_PROVIDER = 'whisper';
                        startMicRecorder();
                    }
                    startRecognition();
                } else {
                    // Whisper mode: chunked recording + Web Speech for interim preview
                    startMicRecorder();
                    startRecognition();
                }
                updateStatus();
            })
            .catch(function (err) {
                console.error('Mic error:', err);
                alert('Could not access microphone. Please allow microphone permissions.');
            });
        }
    });

    // -----------------------------------------------------------------------
    // System Audio Button — starts system audio capture + optional screen capture
    // -----------------------------------------------------------------------
    btnSystem.addEventListener('click', function () {
        if (systemStream) {
            if (ASR_PROVIDER === 'azure_speech') {
                stopAzureSystemTranscriber();
            } else {
                stopSystemRecorder();
            }
            stopScreenCapture();
            systemStream.getTracks().forEach(function (t) { t.stop(); });
            systemStream = null;
            btnSystem.classList.remove('ir-audio-btn--on');
            btnSystem.classList.add('ir-audio-btn--off');
            btnSystem.querySelector('span').textContent = 'System Audio: OFF';
            updateStatus();
        } else {
            navigator.mediaDevices.getDisplayMedia({
                video: ENABLE_SCREEN_CAP, // keep video track for screen capture
                audio: true,
            })
            .then(function (stream) {
                // Handle video track for screen capture
                var videoTracks = stream.getVideoTracks();
                if (ENABLE_SCREEN_CAP && videoTracks.length > 0) {
                    startScreenCapture(videoTracks[0]);
                    videoTracks[0].addEventListener('ended', function () { stopScreenCapture(); });
                } else {
                    videoTracks.forEach(function (t) { t.stop(); });
                }

                if (stream.getAudioTracks().length === 0) {
                    alert('No audio track captured. Make sure to check "Share system audio" or "Share audio" when prompted.');
                    stream.getTracks().forEach(function (t) { t.stop(); });
                    return;
                }

                // Create an audio-only stream for ASR
                var audioOnlyStream = new MediaStream(stream.getAudioTracks());
                systemStream = audioOnlyStream;

                btnSystem.classList.remove('ir-audio-btn--off');
                btnSystem.classList.add('ir-audio-btn--on');
                btnSystem.querySelector('span').textContent = 'System Audio: ON';

                audioOnlyStream.getAudioTracks().forEach(function (track) {
                    track.addEventListener('ended', function () {
                        if (ASR_PROVIDER === 'azure_speech') {
                            stopAzureSystemTranscriber();
                        } else {
                            stopSystemRecorder();
                        }
                        stopScreenCapture();
                        systemStream = null;
                        btnSystem.classList.remove('ir-audio-btn--on');
                        btnSystem.classList.add('ir-audio-btn--off');
                        btnSystem.querySelector('span').textContent = 'System Audio: OFF';
                        updateStatus();
                    });
                });

                if (ASR_PROVIDER === 'azure_speech') {
                    startAzureSystemTranscriber();
                } else {
                    // Both Whisper and google_speech use Whisper for system audio
                    // (Web Speech API can't process system audio streams)
                    startSystemRecorder();
                }
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
            if (data.error) { alert(data.error); return; }
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

            card.querySelector('.btn-ask').addEventListener('click', function () { markQuestion(q.id, 'asked', card); });
            card.querySelector('.btn-skip').addEventListener('click', function () { markQuestion(q.id, 'skipped', card); });
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
                actionsDiv.querySelector('.btn-evaluate').addEventListener('click', function () { evaluateAnswer(qId, cardEl); });
                actionsDiv.querySelector('.btn-skip').addEventListener('click', function () { markQuestion(qId, 'skipped', cardEl); });
            } else if (status === 'skipped') {
                actionsDiv.innerHTML = '<span class="text-muted" style="font-size:12px;">Skipped</span>';
            }
        })
        .catch(function (err) { console.error('Update question status error:', err); });
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
        if (evalBtn) { evalBtn.disabled = true; evalBtn.innerHTML = '<span class="spinner"></span>'; }

        fetch(URLS.evaluate, {
            method: 'POST',
            headers: headers(true),
            body: JSON.stringify({ question_id: qId, answer_text: answerText }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) { alert(data.error); return; }
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
            if (evalBtn) { evalBtn.disabled = false; evalBtn.textContent = 'Evaluate Answer'; }
        });
    }

    function renderEvaluation(evaluation, cardEl) {
        var scoreColor = (evaluation.score || 0) >= 70 ? 'var(--success)' :
                         (evaluation.score || 0) >= 40 ? 'var(--warning)' : 'var(--danger)';
        var depthColors = { expert: 'var(--success)', deep: '#22c55e', working: 'var(--warning)', surface: 'var(--danger)' };
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
                }).catch(function (err) { console.error('Notes save error:', err); });
            }, 2000);
        });
    }

    // -----------------------------------------------------------------------
    // End Session
    // -----------------------------------------------------------------------
    btnEnd.addEventListener('click', function () {
        if (!confirm('End this interview session? The AI summary will be generated automatically.')) return;

        flushTranscript();

        // Stop all audio
        if (ASR_PROVIDER === 'azure_speech') {
            stopAzureMicRecognizer();
            stopAzureSystemTranscriber();
        } else {
            stopMicRecorder();
            stopSystemRecorder();
        }
        stopScreenCapture();
        if (micStream) { micStream.getTracks().forEach(function (t) { t.stop(); }); micStream = null; }
        if (systemStream) { systemStream.getTracks().forEach(function (t) { t.stop(); }); systemStream = null; }
        if (ASR_PROVIDER === 'whisper' || ASR_PROVIDER === 'google_speech') stopRecognition();
        clearInterval(elapsedInterval);
        clearInterval(flushTimer);
        clearInterval(tokenRefreshTimer);

        btnEnd.disabled = true;
        btnEnd.innerHTML = '<span class="spinner"></span> Ending...';

        fetch(URLS.end, {
            method: 'POST',
            headers: headers(true),
            body: JSON.stringify({}),
        })
        .then(function (r) { return r.json(); })
        .then(function () { window.location.href = URLS.summary; })
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
