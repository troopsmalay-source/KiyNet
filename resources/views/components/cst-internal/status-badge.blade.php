@props(['status' => 'Pending', 'size' => 'sm'])

@php
    $tone = match ($status) {
        'Aktif' => 'aktif',
        'Pending' => 'pending',
        'Menunggak' => 'menunggak',
        'Isolir' => 'isolir',
        'Suspend' => 'suspend',
        'Berhenti' => 'berhenti',
        default => 'netral',
    };
@endphp

<span {{ $attributes->class(['cst-badge', 'cst-badge--'.$tone, 'cst-badge--lg' => $size === 'lg']) }} data-status="{{ $status }}">
    <span class="cst-badge-dot" aria-hidden="true"></span>{{ $status }}
</span>
