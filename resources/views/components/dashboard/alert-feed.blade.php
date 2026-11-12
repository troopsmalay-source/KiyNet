@props(['incidents'])

@php
    $priorityMeta = [
        'high' => ['label' => 'High', 'tone' => 'danger'],
        'medium' => ['label' => 'Med', 'tone' => 'warning'],
        'low' => ['label' => 'Low', 'tone' => 'info'],
    ];
    $typeIcons = [
        'payment' => 'bi-cash-coin',
        'router_offline' => 'bi-router',
        'latency' => 'bi-activity',
        'auto_isolir' => 'bi-person-lock',
        'bandwidth' => 'bi-speedometer2',
        'reconnect' => 'bi-arrow-repeat',
    ];
    $highCount = collect($incidents)->where('priority', 'high')->count();
@endphp

<section class="dash-section" aria-labelledby="incident-heading">
    <header class="dash-section-head">
        <div>
            <h2 id="incident-heading">Aktivitas &amp; insiden terbaru</h2>
            <p>Pembayaran, gangguan router, kegagalan otomasi, dan kapasitas mitra.</p>
        </div>
        <span class="dash-live" title="Feed real-time belum tersambung">
            <span class="dash-live-dot" aria-hidden="true"></span>
            {{ $highCount }} prioritas tinggi
        </span>
    </header>

    <div class="dash-card dash-feed-card">
        <ol class="dash-feed" aria-label="Feed aktivitas dan insiden">
            @foreach ($incidents as $incident)
                @php
                    $priority = $priorityMeta[$incident['priority']] ?? $priorityMeta['low'];
                @endphp
                <li class="dash-feed-item is-{{ $incident['priority'] }}">
                    <span class="dash-feed-icon is-{{ $priority['tone'] }}" aria-hidden="true">
                        <i class="bi {{ $typeIcons[$incident['type']] ?? 'bi-bell' }}"></i>
                    </span>
                    <div class="dash-feed-copy">
                        <div class="dash-feed-title">
                            <strong>{{ $incident['title'] }}</strong>
                            <span class="dash-badge is-{{ $priority['tone'] }}">{{ $priority['label'] }}</span>
                        </div>
                        <p>{{ $incident['message'] }}</p>
                        <span class="dash-feed-meta">{{ $incident['meta'] }} &middot; {{ $incident['time'] }}</span>
                    </div>
                </li>
            @endforeach
        </ol>
        <p class="dash-note">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            Feed masih data contoh. TODO: sambungkan ke endpoint event/webhook (payment gateway, health-check MikroTik, auto-isolir).
        </p>
    </div>
</section>
