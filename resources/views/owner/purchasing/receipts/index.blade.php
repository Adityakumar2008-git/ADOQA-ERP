@extends('layouts.owner')
@inject('authService', 'App\Services\Auth\AuthorizationService')

@section('title', 'Goods Receipts')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Goods Receipts"
        subtitle="Physical stock receiving documents, batch validation, serialized item intake, and warehouse arrival."
    >
        <x-slot:actions>
            @if($authService->can(auth()->user(), 'purchases.receipts.prepare', $tenant))
                <x-button variant="primary" :href="route('owner.purchasing.receipts.create')">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Receive Goods
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        @if($receipts->isEmpty())
            <x-empty-state
                title="No goods receipts found"
                description="Record physical deliveries arriving at your warehouses from suppliers."
                :actionText="$authService->can(auth()->user(), 'purchases.receipts.prepare', $tenant) ? 'Receive Goods' : null"
                :actionUrl="$authService->can(auth()->user(), 'purchases.receipts.prepare', $tenant) ? route('owner.purchasing.receipts.create') : null"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-4">Receipt #</th>
                            <th class="py-3 px-4">Delivery Date</th>
                            <th class="py-3 px-4">Supplier</th>
                            <th class="py-3 px-4">Destination Warehouse</th>
                            <th class="py-3 px-4 text-center">Lines</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($receipts as $gr)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    <a href="{{ route('owner.purchasing.receipts.show', $gr) }}" class="text-[#1C9640] hover:text-[#13682C]">
                                        {{ $gr->receipt_number }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-slate-600 font-mono text-[11px]">
                                    {{ $gr->received_date ? $gr->received_date->format('Y-m-d') : $gr->created_at->format('Y-m-d') }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800">
                                    {{ $gr->supplier->name }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800">
                                    {{ $gr->warehouse->name }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-slate-700 font-mono">
                                    {{ $gr->lines_count }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        @if($gr->status->value === 'posted') bg-emerald-50 text-emerald-700 border border-emerald-200
                                        @elseif($gr->status->value === 'draft') bg-slate-100 text-slate-600 border border-slate-200
                                        @else bg-rose-50 text-rose-700 border border-rose-200 @endif">
                                        {{ ucfirst($gr->status->value) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('owner.purchasing.receipts.show', $gr) }}" class="font-semibold text-xs text-[#1C9640] hover:text-[#13682C]">
                                        View Details &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($receipts->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $receipts->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
