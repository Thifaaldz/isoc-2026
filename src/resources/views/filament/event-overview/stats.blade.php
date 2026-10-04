@php
    $s = $overview->stats();
    $pct = fn ($part, $whole) => $whole > 0 ? min(100, (int) round($part / $whole * 100)) : 0;
@endphp
<div class="eo-stats">
    <div class="eo-stat">
        <div class="eo-stat-label">Peserta terdaftar</div>
        <div class="eo-stat-value">{{ $s['participants'] }}<small>/{{ $s['target'] }}</small></div>
        <div class="eo-bar"><span style="width: {{ $pct($s['participants'], $s['target']) }}%;"></span></div>
    </div>
    <div class="eo-stat">
        <div class="eo-stat-label">Hadir</div>
        <div class="eo-stat-value">{{ $s['attended'] }}<small>/{{ $s['participants'] }}</small></div>
        <div class="eo-bar"><span style="background: var(--eo-success); width: {{ $pct($s['attended'], $s['participants']) }}%;"></span></div>
    </div>
    <div class="eo-stat">
        <div class="eo-stat-label">Pre / Post-Test</div>
        <div class="eo-stat-value">{{ $s['pre'] }}<small> / {{ $s['post'] }}</small></div>
        <div class="eo-bar"><span style="background: var(--eo-warning); width: {{ $pct($s['post'], $s['participants']) }}%;"></span></div>
    </div>
    <div class="eo-stat">
        <div class="eo-stat-label">Peserta lengkap</div>
        <div class="eo-stat-value">{{ $s['complete'] }}<small>/{{ $s['participants'] }}</small></div>
        <div class="eo-bar"><span style="background: var(--eo-success); width: {{ $pct($s['complete'], $s['participants']) }}%;"></span></div>
    </div>
    <div class="eo-stat">
        <div class="eo-stat-label">Tutor lulus ToT</div>
        <div class="eo-stat-value">{{ $s['tutors_tot'] }}<small>/{{ max($s['tutors'], $s['tutors_target']) }}</small></div>
        <div class="eo-bar"><span style="background: var(--eo-success); width: {{ $pct($s['tutors_tot'], max($s['tutors'], $s['tutors_target'])) }}%;"></span></div>
    </div>
</div>
