@props(['network', 'routers'])

@php
    $statusMeta = [
        'online' => ['label' => 'Online', 'tone' => 'success'],
        'degraded' => ['label' => 'Degraded', 'tone' => 'warning'],
        'offline' => ['label' => 'Offline', 'tone' => 'danger'],
        'unknown' => ['label' => 'Belum tersinkron', 'tone' => 'muted'],
    ];
    $linkMeta = [
        'ok' => ['label' => 'OK', 'tone' => 'success'],
        'failed' => ['label' => 'Gagal', 'tone' => 'danger'],
        'unknown' => ['label' => '—', 'tone' => 'muted'],
    ];
@endphp

<section class="dash-section" aria-labelledby="network-heading">
    <header class="dash-section-head">
        <div>
            <h2 id="network-heading">Kesehatan jaringan &amp; gateway MikroTik</h2>
            <p>Status konektivitas router, beban CPU, dan latensi API gateway.</p>
        </div>
        <a class="dash-link" href="{{ route('admin.development', 'mikrotik') }}">
            Integrasi MikroTik <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </header>

    <div class="dash-metric-grid">
        <div class="dash-metric">
            <span>Total Router</span>
            <strong>{{ number_format($network['totalRouter'], 0, ',', '.') }}</strong>
        </div>
        <div class="dash-metric is-success">
            <span><i class="bi bi-broadcast" aria-hidden="true"></i> Terhubung</span>
            <strong>{{ number_format($network['online'], 0, ',', '.') }}</strong>
        </div>
        <div class="dash-metric is-danger">
            <span><i class="bi bi-plug" aria-hidden="true"></i> Putus</span>
            <strong>{{ number_format($network['offline'], 0, ',', '.') }}</strong>
        </div>
        <div class="dash-metric is-warning">
            <span><i class="bi bi-cpu" aria-hidden="true"></i> Rata-rata CPU</span>
            <strong>{{ $network['avgCpuLoad'] }}%</strong>
        </div>
        <div class="dash-metric is-info">
            <span><i class="bi bi-stopwatch" aria-hidden="true"></i> Rata-rata Ping</span>
            <strong>{{ $network['avgLatencyMs'] }} ms</strong>
        </div>
    </div>

    <article class="dash-card">
        <div class="dash-card-head">
            <div>
                <h3>Router gateway</h3>
                <p>Ringkasan perangkat dan status API/SSH.</p>
            </div>
            <span class="dash-chip dash-chip-mock" title="Telemetri router masih data contoh">
                <i class="bi bi-flask" aria-hidden="true"></i> Data contoh
            </span>
        </div>
        <div class="table-responsive">
            <table class="table dash-router-table mb-0">
                <thead>
                    <tr>
                        <th>Router</th>
                        <th>IP Gateway</th>
                        <th>RouterOS</th>
                        <th>Uptime</th>
                        <th class="text-end">CPU</th>
                        <th class="text-end">Ping</th>
                        <th>API</th>
                        <th>SSH</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($routers as $router)
                        @php
                            $status = $statusMeta[$router['status']] ?? $statusMeta['unknown'];
                            $api = $linkMeta[$router['api']] ?? $linkMeta['unknown'];
                            $ssh = $linkMeta[$router['ssh']] ?? $linkMeta['unknown'];
                        @endphp
                        <tr>
                            <td>
                                <span class="dash-table-primary">{{ $router['name'] }}</span>
                                <span class="dash-table-sub">{{ $router['location'] ?? '—' }}</span>
                            </td>
                            <td class="dash-mono">{{ $router['ip'] }}</td>
                            <td>{{ $router['routeros'] ?: '—' }}</td>
                            <td>{{ $router['uptime'] ?: '—' }}</td>
                            <td class="text-end">{{ $router['cpu'] !== null ? $router['cpu'] . '%' : '—' }}</td>
                            <td class="text-end">{{ $router['latency'] !== null ? $router['latency'] . ' ms' : '—' }}</td>
                            <td><span class="dash-badge is-{{ $api['tone'] }}">{{ $api['label'] }}</span></td>
                            <td><span class="dash-badge is-{{ $ssh['tone'] }}">{{ $ssh['label'] }}</span></td>
                            <td><span class="dash-badge is-{{ $status['tone'] }}"><span class="dash-badge-dot"></span>{{ $status['label'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </article>
</section>
