@props(['title', 'value', 'description', 'icon', 'tone'])

<article class="dashboard-stat stat-{{ $tone }}">
    <div class="stat-topline">
        <span class="dashboard-stat-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
        <span class="stat-context">{{ $title }}</span>
    </div>
    <strong class="dashboard-stat-value">{{ $value }}</strong>
    <span class="dashboard-stat-description">{{ $description }}</span>
</article>
