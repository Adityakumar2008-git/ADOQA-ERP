@props([
    'title' => null,
    'subtitle' => null,
    'badge' => null,
    'badgeVariant' => 'slate',
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden']) }}>
    @if ($title || isset($header) || $badge)
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                @if ($title)
                    <h3 class="text-sm font-bold text-slate-900">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
                @endif
                @if (isset($header))
                    {{ $header }}
                @endif
            </div>

            @if ($badge)
                <x-badge :variant="$badgeVariant">{{ $badge }}</x-badge>
            @endif

            @if (isset($actions))
                <div class="flex items-center space-x-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $padding ? 'p-6' : '' }}">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 text-xs text-slate-600">
            {{ $footer }}
        </div>
    @endif
</div>
