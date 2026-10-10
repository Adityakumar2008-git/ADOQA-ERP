@extends('layouts.owner')

@section('title', 'Categories')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Product Categories"
        subtitle="Organize items into commercial groups and retail tax departments."
    >
        <x-slot:actions>
            <x-button variant="primary" :href="route('owner.catalogue.categories.create')">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Category
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        @if($categories->isEmpty())
            <x-empty-state
                title="No categories defined"
                description="Categories help structure your items catalogue and speed up cashier operations."
                actionText="Add First Category"
                :actionUrl="route('owner.catalogue.categories.create')"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-4">Category Name</th>
                            <th class="py-3 px-4">Slug</th>
                            <th class="py-3 px-4">Description</th>
                            <th class="py-3 px-4 text-center">Items Count</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($categories as $category)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ $category->name }}
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-500">
                                    {{ $category->slug }}
                                </td>
                                <td class="py-3 px-4 text-slate-500 max-w-xs truncate">
                                    {{ $category->description ?? '—' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        {{ $category->items_count }} items
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($category->isActive())
                                        <x-badge variant="emerald" size="sm">Active</x-badge>
                                    @else
                                        <x-badge variant="slate" size="sm">Inactive</x-badge>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('owner.catalogue.categories.edit', $category) }}" class="text-[#1C9640] hover:text-[#13682C] font-medium">Edit</a>
                                        <form method="POST" action="{{ route('owner.catalogue.categories.status', $category) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-slate-400 hover:text-slate-700 text-[11px]">
                                                {{ $category->isActive() ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                        @if($category->items_count === 0)
                                            <form method="POST" action="{{ route('owner.catalogue.categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Delete this category?');">
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

            @if($categories->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $categories->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
