@props([
    'type' => 'info',
    'title' => null,
])

@php
    $typeClasses = match ($type) {
        'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
        'error', 'danger' => 'bg-rose-50 border-rose-200 text-rose-800',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
        default => 'bg-sky-50 border-sky-200 text-sky-800',
    };

    $iconClasses = match ($type) {
        'success' => 'text-emerald-600',
        'error', 'danger' => 'text-rose-600',
        'warning' => 'text-amber-600',
        default => 'text-sky-600',
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-xl border p-4 text-xs font-medium flex items-start space-x-3 {$typeClasses}"]) }} role="alert">
    <div class="flex-shrink-0 mt-0.5 {{ $iconClasses }}">
        @if ($type === 'success')
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        @elseif ($type === 'error' || $type === 'danger')
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        @elseif ($type === 'warning')
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        @endif
    </div>

    <div class="flex-1 space-y-0.5">
        @if ($title)
            <p class="font-bold text-xs">{{ $title }}</p>
        @endif
        <div class="text-xs">
            {{ $slot }}
        </div>
    </div>
</div>
