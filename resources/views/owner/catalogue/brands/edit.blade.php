@extends('layouts.owner')

@section('title', 'Edit Brand — '.$brand->name)

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <x-page-header
        title="Edit Brand"
        subtitle="{{ $brand->name }}"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.catalogue.brands.index')">Cancel</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="POST" action="{{ route('owner.catalogue.brands.update', $brand) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Brand Name <span class="text-rose-500">*</span></label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    required
                    value="{{ old('name', $brand->name) }}"
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                >
                @error('name')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="slug" class="block text-xs font-semibold text-slate-700 mb-1">Slug</label>
                <input
                    type="text"
                    name="slug"
                    id="slug"
                    value="{{ old('slug', $brand->slug) }}"
                    class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                >
                @error('slug')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                <textarea
                    name="description"
                    id="description"
                    rows="3"
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                >{{ old('description', $brand->description) }}</textarea>
                @error('description')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                <x-button variant="secondary" :href="route('owner.catalogue.brands.index')">Cancel</x-button>
                <x-button type="submit" variant="primary">Save Changes</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
