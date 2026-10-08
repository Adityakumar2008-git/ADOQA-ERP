@props([
    'title',
    'description' => null,
    'badge' => null,
    'badgeVariant' => 'slate',
])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between mb-3']) }}>
    <div>
        <div class="flex items-center space-x-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">{{ $title }}</h3>
            @if ($badge)
                <x-badge :variant="$badgeVariant" size="sm">{{ $badge }}</x-badge>
            @endif
        </div>
        @if ($description)
            <p class="text-[11px] text-slate-500 mt-0.5">{{ $description }}</p>
        @endif
    </div>

    @if (isset($action))
        <div class="flex-shrink-0">
            {{ $action }}
        </div>
    @endif
</div>
