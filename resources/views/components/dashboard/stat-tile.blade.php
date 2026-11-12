@props(['label', 'value', 'meta' => null, 'tone' => 'neutral', 'icon' => 'bi-activity', 'mock' => false])

<article class="dash-stat dash-stat-{{ $tone }}">
    <span class="dash-stat-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
    <div class="dash-stat-copy">
        <span class="dash-stat-label">
            {{ $label }}
            @if ($mock)
                <span class="dash-mock-dot" title="Data contoh, endpoint backend belum tersedia" aria-label="Data contoh">contoh</span>
            @endif
        </span>
        <strong class="dash-stat-value">{{ $value }}</strong>
        @if ($meta)
            <span class="dash-stat-meta">{{ $meta }}</span>
        @endif
    </div>
</article>
