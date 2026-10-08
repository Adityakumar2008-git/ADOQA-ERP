@extends('layouts.owner')

@section('title', 'New Brand')

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <x-page-header
        title="Create Brand"
        subtitle="Define a manufacturer or product brand for your store."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.catalogue.brands.index')">Cancel</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="POST" action="{{ route('owner.catalogue.brands.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Brand Name <span class="text-rose-500">*</span></label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    required
                    value="{{ old('name') }}"
                    placeholder="e.g. Apple, Samsung, Nike, Bosch, local brand..."
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                >
                @error('name')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="slug" class="block text-xs font-semibold text-slate-700 mb-1">Slug (Optional)</label>
                <input
                    type="text"
                    name="slug"
                    id="slug"
                    value="{{ old('slug') }}"
                    placeholder="Leave blank to auto-generate from name"
                    class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                >
                @error('slug')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold text-slate-700 mb-1">Description (Optional)</label>
                <textarea
                    name="description"
                    id="description"
                    rows="3"
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                >{{ old('description') }}</textarea>
                @error('description')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                <x-button variant="secondary" :href="route('owner.catalogue.brands.index')">Cancel</x-button>
                <x-button type="submit" variant="primary">Create Brand</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
