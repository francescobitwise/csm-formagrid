<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resoconto ore corso</title>
    <style>
        :root { --accent: {{ $accent }}; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        .muted { color: #475569; }
        .h1 { font-size: 18px; font-weight: 700; margin: 0; }
        .h2 { font-size: 12px; font-weight: 700; margin: 0; color: #0f172a; }
        .topbar { border-bottom: 2px solid var(--accent); padding-bottom: 10px; margin-bottom: 14px; }
        .brand { display: flex; align-items: center; gap: 10px; }
        .logo { width: 40px; height: 40px; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; }
        .logo img { width: 100%; height: 100%; object-fit: contain; }
        .meta { margin-top: 6px; font-size: 11px; }
        .box { margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px 6px; vertical-align: top; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #475569; }
        td.num, th.num { text-align: right; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 999px; border: 1px solid #cbd5e1; font-size: 10px; color: #334155; }
        .badge.ok { border-color: rgba(16,185,129,.35); background: rgba(16,185,129,.12); color: #047857; }
        .footer { margin-top: 18px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #475569; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="brand">
            @if ($logoDataUri)
                <div class="logo"><img src="{{ $logoDataUri }}" alt=""></div>
            @endif
            <div>
                <div class="h2">{{ $tenantName }}</div>
                <div class="muted">Resoconto ore corso</div>
            </div>
        </div>

        <div class="meta">
            <div><strong>Corso:</strong> {{ $course->title }}</div>
            <div><strong>Generato il:</strong> {{ $generatedAt->format('d/m/Y H:i') }}</div>
        </div>

        @if (trim($headerText) !== '')
            <div class="box">{{ $headerText }}</div>
        @endif
    </div>

    @php
        $totalSeconds = (int) collect($rows)->sum(fn ($r) => (int) ($r->watched_seconds_total ?? 0));
        $totalH = intdiv($totalSeconds, 3600);
        $totalM = intdiv($totalSeconds % 3600, 60);
    @endphp

    <div class="muted" style="font-size: 11px;">
        Totale tempo visto (somma corsisti):
        <strong style="color:#0f172a;">{{ $totalH }} ore {{ $totalM }} minuti</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>Corsista</th>
                <th class="num">Tempo visto</th>
                <th class="num">Completamento</th>
                <th class="num">Stato</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
                @php
                    $pct = (float) ($r->progress_pct ?? 0);
                    $status = (string) ($r->status?->value ?? $r->status ?? '');
                    $sec = (int) ($r->watched_seconds_total ?? 0);
                    $h = intdiv($sec, 3600);
                    $m = intdiv($sec % 3600, 60);
                @endphp
                <tr>
                    <td>
                        <div style="font-weight:700;">{{ $r->user?->name ?? '—' }}</div>
                        <div class="muted" style="font-size: 10px;">{{ $r->user?->email ?? '' }}</div>
                    </td>
                    <td class="num">{{ $h }} ore {{ $m }} min</td>
                    <td class="num">{{ number_format($pct, 0) }}%</td>
                    <td class="num">
                        <span class="badge {{ $status === 'completed' ? 'ok' : '' }}">{{ $status !== '' ? $status : '—' }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        @if (trim($footerText) !== '')
            <div>{{ $footerText }}</div>
        @endif
        <div style="margin-top: 6px;">
            Documento generato automaticamente dalla piattaforma.
        </div>
    </div>
</body>
</html>

