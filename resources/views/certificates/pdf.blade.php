<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certificate {{ $certificate->serial }}</title>
    <style>
        @page { margin: 0; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1b1b18;
        }
        .sheet {
            box-sizing: border-box;
            width: 100%;
            height: 560px;
            padding: 48px 64px;
            border: 12px solid #0f172a;
            text-align: center;
        }
        .eyebrow {
            font-size: 11px;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #64748b;
        }
        h1 { font-size: 30px; margin: 18px 0 4px; }
        .name { font-size: 40px; margin: 26px 0 6px; }
        .course { font-size: 21px; margin: 18px 0 0; }
        .rule { width: 260px; border-bottom: 1px solid #cbd5e1; margin: 14px auto; }
        .meta { margin-top: 34px; font-size: 12px; color: #475569; line-height: 1.7; }
        .serial { font-family: DejaVu Sans Mono, monospace; letter-spacing: 1px; }
    </style>
</head>
<body>
<div class="sheet">
    <div class="eyebrow">{{ config('app.name') }}</div>

    <h1>Certificate of Completion</h1>
    <div class="rule"></div>

    <div class="eyebrow">This certifies that</div>
    <div class="name">{{ $certificate->user->name }}</div>

    <div class="eyebrow">has completed</div>
    <div class="course">{{ $certificate->course->title }}</div>

    <div class="meta">
        Issued {{ $certificate->issued_at->format('j F Y') }}<br>
        Serial <span class="serial">{{ $certificate->serial }}</span><br>
        Verify at {{ $verifyUrl }}
    </div>
</div>
</body>
</html>
