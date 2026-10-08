@props([
    'label',
    'value',
    'subtext' => null,
    'badge' => null,
    'badgeVariant' => 'slate',
    'iconVariant' => 'indigo',
    'available' => true,
])

@php
    $iconBgClasses = match ($iconVariant) {
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'sky' => 'bg-sky-50 text-sky-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'purple' => 'bg-purple-50 text-purple-600',
        'rose' => 'bg-rose-50 text-rose-600',
        default => 'bg-indigo-50 text-indigo-600',
    };
@endphp

<div {{ $attributes->merge(['class' => 'bg-white p-5 rounded-xl border border-slate-200 shadow-2xs']) }}>
    <div class="flex items-center justify-between">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $label }}</p>
        @if (isset($icon))
            <span class="p-2 rounded-lg {{ $iconBgClasses }}">
                {{ $icon }}
            </span>
        @elseif ($badge)
            <x-badge :variant="$badgeVariant">{{ $badge }}</x-badge>
        @endif
    </div>

    <div class="mt-3 flex items-baseline">
        @if ($available)
            <span class="text-2xl font-bold text-slate-900 tracking-tight">{{ $value }}</span>
        @else
            <span class="text-sm font-semibold text-slate-500 italic">{{ $value }}</span>
        @endif
    </div>

    @if ($subtext)
        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>{{ $subtext }}</span>
            @if ($badge && isset($icon))
                <x-badge :variant="$badgeVariant">{{ $badge }}</x-badge>
            @endif
        </div>
    @endif
</div>
