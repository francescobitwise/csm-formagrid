<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report corsista</title>
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
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .card { padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px; background: #ffffff; }
        .kpi { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #64748b; }
        .kpiVal { font-size: 14px; font-weight: 700; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px 6px; vertical-align: top; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #475569; }
        td.num, th.num { text-align: right; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 999px; border: 1px solid #cbd5e1; font-size: 10px; color: #334155; }
        .badge.ok { border-color: rgba(16,185,129,.35); background: rgba(16,185,129,.12); color: #047857; }
        .badge.warn { border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.12); color: #92400e; }
        .footer { margin-top: 18px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #475569; }
    </style>
</head>
<body>
@php
    $fmt = function (int $sec): string {
        $sec = max(0, $sec);
        $h = intdiv($sec, 3600);
        $m = intdiv($sec % 3600, 60);
        $s = $sec % 60;
        if ($h > 0) return "{$h}h, {$m}min e {$s}s";
        if ($m > 0) return "{$m}min e {$s}s";
        return "{$s}s";
    };
    $pct = (float) ($enrollment->progress_pct ?? 0);
    $pctClamped = (int) min(100, max(0, $pct));
    $status = (string) ($enrollment->status?->value ?? $enrollment->status ?? '');
@endphp

    <div class="topbar">
        <div class="brand">
            @if ($logoDataUri)
                <div class="logo"><img src="{{ $logoDataUri }}" alt=""></div>
            @endif
            <div>
                <div class="h2">{{ $tenantName }}</div>
                <div class="muted">Report corsista</div>
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

    <div class="grid">
        <div class="card">
            <div class="kpi">Informazioni utente</div>
            <div class="kpiVal">{{ $user?->displayName() ?? ($user?->name ?? '—') }}</div>
            <div class="muted" style="margin-top: 4px;">
                <div><strong>Azienda:</strong> {{ $company?->name ?? '—' }}</div>
                <div><strong>P.IVA:</strong> {{ $company?->vat ?? '—' }}</div>
                <div><strong>Cod. fiscale:</strong> {{ $user?->tax_code ?? '—' }}</div>
                <div><strong>Telefono:</strong> {{ $user?->phone ?? '—' }}</div>
                <div><strong>Email:</strong> {{ $user?->email ?? '—' }}</div>
            </div>
        </div>
        <div class="card">
            <div class="kpi">Corso</div>
            <div class="kpiVal">{{ $course->title }}</div>
            <div class="muted" style="margin-top: 4px;">
                <div><strong>Iscritto al corso il:</strong> {{ $enrollment->enrolled_at?->format('d/m/Y, H:i') ?? '—' }}</div>
                <div><strong>Completamento:</strong> {{ number_format($pctClamped, 2, ',', '.') }}%</div>
                <div><strong>Stato:</strong>
                    <span class="badge {{ $status === 'completed' ? 'ok' : ($status === 'active' ? 'warn' : '') }}">
                        {{ $status !== '' ? $status : '—' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid" style="margin-top: 10px;">
        <div class="card">
            <div class="kpi">Sessioni contenuti (video + scorm)</div>
            <div class="muted" style="margin-top: 6px;">
                <div><strong>Sessioni:</strong> {{ (int) ($sessionSummary['count'] ?? 0) }}</div>
                <div><strong>Tempo totale visto:</strong> {{ $fmt((int) ($sessionSummary['total_seconds'] ?? 0)) }}</div>
                <div><strong>Video:</strong> {{ $fmt((int) ($sessionSummary['video_seconds'] ?? 0)) }}</div>
                <div><strong>SCORM:</strong> {{ $fmt((int) ($sessionSummary['scorm_seconds'] ?? 0)) }}</div>
            </div>
        </div>
        <div class="card">
            <div class="kpi">Piattaforma</div>
            <div class="muted" style="margin-top: 6px;">
                <div><strong>Ultimo login:</strong> {{ $user?->last_login_at?->format('d/m/Y, H:i') ?? '—' }}</div>
                <div><strong>Accessi totali:</strong> {{ (int) ($user?->login_count ?? 0) }}</div>
                <div><strong>Durata permanenza:</strong> {{ $fmt((int) ($platformSummary['total_seconds'] ?? 0)) }}</div>
                <div><strong>Durata media permanenza:</strong> {{ $fmt((int) ($platformSummary['avg_seconds'] ?? 0)) }}</div>
            </div>
        </div>
    </div>

    <h3 class="h2" style="margin-top: 14px;">Sessioni utente</h3>
    <table>
        <thead>
            <tr>
                <th>Data inizio sessione</th>
                <th>Data fine</th>
                <th>Durata</th>
                <th>Capitolo</th>
                <th>Browser</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sessions as $s)
                @php
                    $ua = (string) ($s->user_agent ?? '');
                    $u = strtolower($ua);
                    $browser = $ua === '' ? '—' : (str_contains($u, 'edg/') ? 'Edge' : (str_contains($u, 'chrome/') ? 'Chrome' : (str_contains($u, 'firefox/') ? 'Firefox' : (str_contains($u, 'safari/') && ! str_contains($u, 'chrome/') ? 'Safari' : 'Browser'))));
                    $start = \Illuminate\Support\Carbon::parse($s->started_at);
                    $end = \Illuminate\Support\Carbon::parse($s->ended_at);
                @endphp
                <tr>
                    <td>{{ $start->format('d/m/Y, H:i') }}</td>
                    <td>{{ $end->format('d/m/Y, H:i') }}</td>
                    <td>{{ $fmt((int) ($s->total_seconds ?? 0)) }}</td>
                    <td>{{ (string) ($s->lesson_title ?? '—') }}</td>
                    <td>{{ $browser }}</td>
                    <td>{{ (string) ($s->ip_address ?? '—') }}</td>
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

