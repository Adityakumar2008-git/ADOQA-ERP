@props([
    'label',
    'value',
    'subtext' => null,
    'badge' => null,
    'badgeVariant' => 'slate',
    'iconVariant' => 'adoqa-green',
    'available' => true,
])

@php
    $iconBgClasses = match ($iconVariant) {
        'emerald' => 'bg-[#E8F8EE] text-[#22B14C]',
        'sky' => 'bg-[#EDF9FC] text-[#0284C7]',
        'amber' => 'bg-[#FFFDE6] text-[#B49600]',
        'yellow', 'adoqa-yellow' => 'bg-[#FFFDE6] text-[#B49600]',
        'green', 'adoqa-green' => 'bg-[#E8F8EE] text-[#22B14C]',
        'lime', 'adoqa-lime' => 'bg-[#F6FCE9] text-[#6B8E0B]',
        'grey', 'adoqa-grey' => 'bg-[#F1F3F5] text-[#7F7F7F]',
        'rose' => 'bg-rose-50 text-rose-600',
        'purple' => 'bg-[#F6FCE9] text-[#6B8E0B]',
        default => 'bg-[#E8F8EE] text-[#22B14C]',
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
