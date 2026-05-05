{{-- Standalone public layout for student test-taking. No app sidebar/topbar. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Aptitude Test')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <style>
        body { background: var(--bg-page); }
        .public-shell {
            max-width: 900px;
            margin: 0 auto;
            padding: 32px 24px 80px;
        }
        .public-header {
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        .public-header h1 { margin: 0 0 6px; font-size: 22px; font-weight: 700; color: var(--text-strong); }
        .public-header .sub { color: var(--text-muted); font-size: 14px; }
        .test-question {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 22px;
            margin-bottom: 20px;
        }
        .test-question.unanswered { border-left: 3px solid var(--warning); }
        .test-question.answered   { border-left: 3px solid var(--success); }
        .q-num {
            display: inline-block;
            background: var(--primary-100);
            color: var(--primary);
            font-weight: 600;
            font-size: 12px;
            padding: 3px 10px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .q-meta {
            display: inline-block;
            font-size: 11px;
            color: var(--text-muted);
            margin-left: 8px;
        }
        .q-context {
            margin: 12px 0;
            padding: 14px 16px;
            background: var(--code-bg);
            border-radius: 8px;
            font-family: ui-monospace, 'SF Mono', Consolas, monospace;
            font-size: 13px;
            line-height: 1.55;
            white-space: pre-wrap;
            overflow-x: auto;
            color: var(--text);
        }
        .mcq-option {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: border-color .12s, background .12s;
        }
        .mcq-option:hover  { border-color: var(--primary-200); background: var(--bg-hover); }
        .mcq-option.checked { border-color: var(--primary); background: var(--primary-50); }
        .mcq-option input[type="radio"] { margin-top: 3px; accent-color: var(--primary); }
        .mcq-option .opt-letter {
            font-weight: 700;
            color: var(--primary);
            min-width: 20px;
        }
        .desc-textarea {
            width: 100%;
            min-height: 140px;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            line-height: 1.5;
            background: var(--bg-input);
            color: var(--text);
            resize: vertical;
        }
        .desc-textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .timer-bar {
            position: sticky; top: 0; z-index: 10;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
        }
        .timer-clock { font-size: 22px; font-weight: 700; font-family: ui-monospace, monospace; color: var(--text-strong); }
        .timer-clock.warning { color: var(--warning); }
        .timer-clock.danger  { color: var(--danger); }
    </style>
</head>
<body>
    <div class="public-shell">
        @yield('content')
    </div>
</body>
</html>
