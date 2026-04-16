<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Attestato</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 36px 48px;
            color: #0f172a;
            background: #fff;
        }
        .frame {
            border: 3px solid var(--accent);
            border-radius: 12px;
            padding: 40px 48px 36px;
            min-height: 520px;
            position: relative;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 28px;
        }
        .header-left { display: table-cell; vertical-align: middle; width: 120px; }
        .header-mid { display: table-cell; vertical-align: middle; text-align: center; }
        .header-right { display: table-cell; width: 120px; }
        .logo { max-height: 72px; max-width: 120px; }
        .org-name {
            font-size: 14px;
            font-weight: bold;
            color: var(--accent);
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        h1 {
            text-align: center;
            font-size: 28px;
            margin: 8px 0 32px;
            color: #0f172a;
            font-weight: bold;
        }
        .statement {
            text-align: center;
            font-size: 15px;
            line-height: 1.65;
            margin: 0 auto 28px;
            max-width: 720px;
            color: #334155;
        }
        .learner-name {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
            text-align: center;
            margin: 12px 0 8px;
        }
        .course-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--accent);
            text-align: center;
            margin: 0 0 24px;
        }
        .meta {
            margin-top: 36px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
        .ref {
            margin-top: 8px;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 11px;
            color: #94a3b8;
        }
        .footer {
            position: absolute;
            bottom: 24px;
            left: 48px;
            right: 48px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
        }
    </style>
</head>
<body style="--accent: {{ $accent }};">
<div class="frame">
    <div class="header">
        <div class="header-left">
            @if (!empty($logoDataUri))
                <img class="logo" src="{{ $logoDataUri }}" alt="">
            @endif
        </div>
        <div class="header-mid">
            <div class="org-name">{{ $tenantDisplayName }}</div>
        </div>
        <div class="header-right"></div>
    </div>

    <h1>Certificato di completamento</h1>

    <p class="statement">
        Si attesta che
    </p>
    <div class="learner-name">{{ $learnerName }}</div>
    <p class="statement">
        ha completato con successo il percorso formativo
    </p>
    <div class="course-title">{{ $courseTitle }}</div>

    <div class="meta">
        Data di completamento: <strong>{{ $completedAt->timezone(config('app.timezone'))->format('d/m/Y') }}</strong>
    </div>
    <div class="ref">Riferimento: {{ $certificateReference }}</div>

    <div class="footer">
        Documento generato elettronicamente da FormaGrid per conto di {{ $tenantDisplayName }}.
    </div>
</div>
</body>
</html>
