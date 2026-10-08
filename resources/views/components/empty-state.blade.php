@props([
    'title',
    'description' => null,
    'badge' => null,
    'badgeVariant' => 'slate',
])

<div {{ $attributes->merge(['class' => 'p-8 sm:p-12 text-center rounded-xl border border-dashed border-slate-200 bg-slate-50/60']) }}>
    @if (isset($icon))
        <div class="mx-auto w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 mb-3 shadow-2xs">
            {{ $icon }}
        </div>
    @else
        <div class="mx-auto w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 mb-3 shadow-2xs">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
        </div>
    @endif

    <div class="flex items-center justify-center space-x-2">
        <h4 class="text-sm font-bold text-slate-800">{{ $title }}</h4>
        @if ($badge)
            <x-badge :variant="$badgeVariant">{{ $badge }}</x-badge>
        @endif
    </div>

    @if ($description)
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto leading-relaxed">{{ $description }}</p>
    @endif

    @if (isset($action))
        <div class="mt-4">
            {{ $action }}
        </div>
    @endif
</div>
