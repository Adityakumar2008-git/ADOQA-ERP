@props([
    'title',
    'description' => null,
    'badge' => null,
    'badgeVariant' => 'slate',
])

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2']) }}>
    <div>
        @if (isset($breadcrumbs))
            <nav class="flex items-center space-x-2 text-xs text-slate-500 mb-1.5">
                {{ $breadcrumbs }}
            </nav>
        @endif

        <div class="flex items-center space-x-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
            @if ($badge)
                <x-badge :variant="$badgeVariant">{{ $badge }}</x-badge>
            @endif
        </div>

        @if ($description)
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $description }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex items-center space-x-3 flex-shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>
