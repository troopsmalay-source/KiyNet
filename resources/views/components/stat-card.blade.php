@props(['title', 'value', 'description', 'icon', 'tone'])

<article class="stat-card stat-card-{{ $tone }}">
    <span class="stat-icon"><i class="bi {{ $icon }}"></i></span>
    <div class="stat-content">
        <div class="stat-title">{{ $title }}</div>
        <div class="stat-value">{{ $value }}</div>
        <div class="stat-description">{{ $description }}</div>
    </div>
    <span class="stat-trend" aria-label="Perbandingan periode sebelumnya belum tersedia">—</span>
</article>
