@extends('layouts.owner')

@section('title', 'Units of Measure')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Units of Measure"
        subtitle="Manage canonical measurement units (Piece, Bag, Kg, Meter, Box, etc.) for item stock counting."
    >
        <x-slot:actions>
            <x-button variant="primary" :href="route('owner.catalogue.units.create')">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Unit
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        @if($units->isEmpty())
            <x-empty-state
                title="No units defined"
                description="Units establish how items are quantified during sales and stock counts."
                actionText="Add Unit"
                :actionUrl="route('owner.catalogue.units.create')"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-4">Unit Name</th>
                            <th class="py-3 px-4">Symbol / Code</th>
                            <th class="py-3 px-4 text-center">Items Count</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($units as $unit)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ $unit->name }}
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold text-indigo-700">
                                    {{ $unit->code }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        {{ $unit->items_count }} items
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($unit->isActive())
                                        <x-badge variant="emerald" size="sm">Active</x-badge>
                                    @else
                                        <x-badge variant="slate" size="sm">Inactive</x-badge>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('owner.catalogue.units.edit', $unit) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</a>
                                        <form method="POST" action="{{ route('owner.catalogue.units.status', $unit) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-slate-400 hover:text-slate-700 text-[11px]">
                                                {{ $unit->isActive() ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                        @if($unit->items_count === 0)
                                            <form method="POST" action="{{ route('owner.catalogue.units.destroy', $unit) }}" class="inline" onsubmit="return confirm('Delete this unit?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-500 hover:text-rose-700 text-[11px]">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($units->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $units->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
