@props([
    'variant' => 'slate',
    'size' => 'md',
    'dot' => false,
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'px-1.5 py-0.2 text-[10px]',
        'lg' => 'px-2.5 py-1 text-xs',
        default => 'px-2 py-0.5 text-[11px]',
    };

    $variantClasses = match ($variant) {
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'sky' => 'bg-sky-50 text-sky-700 border-sky-200',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
        'rose' => 'bg-rose-50 text-rose-700 border-rose-200',
        'indigo' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'purple' => 'bg-purple-50 text-purple-700 border-purple-200',
        'adoqa-yellow' => 'bg-[#FFFDE6] text-amber-950 border-[#FFDE2D]/60',
        'adoqa-grey' => 'bg-[#F1F3F5] text-slate-700 border-[#7F7F7F]/40',
        'adoqa-green' => 'bg-[#E8F8EE] text-emerald-950 border-[#22B14C]/50',
        'adoqa-lime' => 'bg-[#F6FCE9] text-lime-950 border-[#B5E61D]/60',
        'adoqa-sky' => 'bg-[#EDF9FC] text-sky-950 border-[#99D9EA]/60',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };

    $dotColors = match ($variant) {
        'emerald' => 'bg-emerald-500',
        'sky' => 'bg-sky-500',
        'amber' => 'bg-amber-500',
        'rose' => 'bg-rose-500',
        'indigo' => 'bg-indigo-500',
        'purple' => 'bg-purple-500',
        'adoqa-yellow' => 'bg-[#FFDE2D]',
        'adoqa-grey' => 'bg-[#7F7F7F]',
        'adoqa-green' => 'bg-[#22B14C]',
        'adoqa-lime' => 'bg-[#B5E61D]',
        'adoqa-sky' => 'bg-[#99D9EA]',
        default => 'bg-slate-400',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-md font-semibold border select-none {$sizeClasses} {$variantClasses}"]) }}>
    @if ($dot)
        <span class="w-1.5 h-1.5 mr-1.5 rounded-full {{ $dotColors }}"></span>
    @endif
    {{ $slot }}
</span>
