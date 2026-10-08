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
        'link' => 'bg-transparent text-indigo-600 hover:text-indigo-800 p-0 focus:ring-0',
        default => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500 shadow-2xs',
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
