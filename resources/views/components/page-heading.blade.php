@props(['title', 'description' => null])

<header class="page-heading">
    <div>
        <p class="page-eyebrow">KiNet eBilling</p>
        <h2>{{ $title }}</h2>
        @if ($description)
            <p class="page-description">{{ $description }}</p>
        @endif
    </div>
    <div class="page-heading-actions">{{ $slot }}</div>
</header>
