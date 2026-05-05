{{-- Inline SVG sparkline. $points = array of numeric scores 0..100 --}}
@php
    $w = 100; $h = 26; $pad = 2;
    $count = count($points);
    $max = max($points) ?: 1;
    $min = min($points);
    $range = max(1, 100); // always 0-100 scale
    $stepX = $count > 1 ? ($w - 2 * $pad) / ($count - 1) : 0;
    $coords = [];
    foreach ($points as $i => $p) {
        $x = $pad + $i * $stepX;
        $y = $h - $pad - (($p / $range) * ($h - 2 * $pad));
        $coords[] = round($x, 1) . ',' . round($y, 1);
    }
    $polyline = implode(' ', $coords);
    $lastScore = end($points);
    $color = $lastScore >= 70 ? '#16a34a' : ($lastScore >= 40 ? '#f59e0b' : '#dc2626');
@endphp
<svg width="{{ $w }}" height="{{ $h }}" viewBox="0 0 {{ $w }} {{ $h }}" style="vertical-align:middle">
    {{-- 50% reference line --}}
    <line x1="{{ $pad }}" y1="{{ $h/2 }}" x2="{{ $w - $pad }}" y2="{{ $h/2 }}" stroke="var(--border)" stroke-width="1" stroke-dasharray="2,2"/>
    <polyline points="{{ $polyline }}" fill="none" stroke="{{ $color }}" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"/>
    @if(!empty($coords))
        @php $last = explode(',', end($coords)); @endphp
        <circle cx="{{ $last[0] }}" cy="{{ $last[1] }}" r="2.5" fill="{{ $color }}"/>
    @endif
</svg>
