@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 rounded-lg select-none disabled:opacity-50 disabled:cursor-not-allowed';

    $sizeClasses = match ($size) {
        'sm' => 'px-2.5 py-1.5 text-xs',
        'lg' => 'px-5 py-2.5 text-sm',
        default => 'px-3.5 py-2 text-xs',
    };

    $variantClasses = match ($variant) {
        'secondary' => 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 focus:ring-slate-400 shadow-2xs',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 focus:ring-rose-500 shadow-2xs',
        'outline' => 'bg-transparent text-slate-700 border border-slate-200 hover:bg-slate-100 focus:ring-slate-400',
        'link' => 'bg-transparent text-[#1C9640] hover:text-[#13682C] p-0 focus:ring-0 font-medium',
        'adoqa-green' => 'bg-[#22B14C] text-white hover:bg-[#1E9E44] focus:ring-[#22B14C] shadow-2xs',
        'adoqa-yellow' => 'bg-[#FFDE2D] text-slate-900 hover:bg-[#F0CF20] focus:ring-[#FFDE2D] shadow-2xs',
        'adoqa-sky' => 'bg-[#99D9EA] text-slate-900 hover:bg-[#85CBDC] focus:ring-[#99D9EA] shadow-2xs',
        default => 'bg-[#22B14C] text-white hover:bg-[#1E9E44] focus:ring-[#22B14C]/50 shadow-2xs',
    };

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
