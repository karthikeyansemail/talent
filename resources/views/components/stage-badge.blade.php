@php
$badgeMap = [
    'applied' => 'badge-gray',
    'ai_shortlisted' => 'badge-blue',
    'hr_screening' => 'badge-yellow',
    'technical_round_1' => 'badge-purple',
    'technical_round_2' => 'badge-purple',
    'offer' => 'badge-orange',
    'hired' => 'badge-green',
    'rejected' => 'badge-red',
    'draft' => 'badge-gray',
    'open' => 'badge-green',
    'on_hold' => 'badge-yellow',
    'closed' => 'badge-red',
    'planning' => 'badge-gray',
    'active' => 'badge-green',
    'completed' => 'badge-blue',
];
@endphp
<span class="badge {{ $badgeMap[$stage] ?? 'badge-gray' }}">{{ ucwords(str_replace('_', ' ', $stage)) }}</span>
