@extends('layouts.owner')

@inject('authService', 'App\Services\Auth\AuthorizationService')

@section('title', 'Goods Receipt: ' . $receipt->receipt_number)

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Goods Receipt: {{ $receipt->receipt_number }}"
        subtitle="Delivered by: {{ $receipt->supplier->name }} &bull; Receiving Depot: {{ $receipt->warehouse->name }}"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <x-button variant="secondary" :href="route('owner.purchasing.receipts.index')">
                    Back to Receipts
                </x-button>

                @if($receipt->status->value === 'draft' && $authService->can(auth()->user(), 'purchases.receipts.post', $tenant))
                    <form method="POST" action="{{ route('owner.purchasing.receipts.post', $receipt) }}" class="inline">
                        @csrf
                        <x-button type="submit" variant="primary">
                            Post Receipt (Update Stock Balances)
                        </x-button>
                    </form>
                @endif
            </div>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif

    <!-- Status Banner -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs flex items-center justify-between">
        <div>
            <span class="text-xs text-slate-500 block uppercase font-semibold">Inventory Status</span>
            <span class="text-sm font-bold uppercase tracking-wider
                @if($receipt->status->value === 'posted') text-emerald-600
                @elseif($receipt->status->value === 'draft') text-slate-600
                @else text-rose-600 @endif">
                {{ $receipt->status->value === 'posted' ? 'Stock Received & Posted to Ledger' : 'Draft / Unposted Intake' }}
            </span>
        </div>
        <div class="text-right text-xs text-slate-600">
            <div>Prepared by: <span class="font-semibold text-slate-900">{{ $receipt->preparer?->name ?? 'Staff' }}</span></div>
            @if($receipt->poster)
                <div>Verified & Posted by: <span class="font-semibold text-slate-900">{{ $receipt->poster->name }}</span> ({{ $receipt->posted_at?->format('d M Y H:i') }})</div>
            @endif
        </div>
    </div>

    <!-- Lines Table -->
    <x-card title="Received Stock Items">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 font-semibold uppercase text-[11px]">
                        <th class="py-2.5 px-3">Item & SKU</th>
                        <th class="py-2.5 px-3 text-right">Accepted Qty</th>
                        <th class="py-2.5 px-3 text-right">Rejected Qty</th>
                        <th class="py-2.5 px-3 text-right">Actual Intake Cost</th>
                        <th class="py-2.5 px-3">Batch / Expiry</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($receipt->lines as $line)
                        <tr>
                            <td class="py-3 px-3">
                                <span class="font-bold text-slate-900 block">{{ $line->item->name }}</span>
                                <span class="text-[11px] text-slate-500 font-mono">{{ $line->item->sku }}</span>
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-emerald-600">
                                {{ number_format((float) $line->accepted_quantity, 2) }} {{ $line->item->unit?->code }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-rose-600">
                                {{ number_format((float) $line->rejected_quantity, 2) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-slate-800 font-semibold">
                                {{ $tenant->currency }} {{ number_format((float) $line->actual_unit_cost, 2) }}
                            </td>
                            <td class="py-3 px-3">
                                @if($line->batch)
                                    <span class="font-mono text-slate-800 font-medium">Batch #{{ $line->batch->batch_number }}</span>
                                    @if($line->batch->expiry_date)
                                        <span class="text-[10px] text-slate-500 block">Exp: {{ $line->batch->expiry_date->format('d M Y') }}</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Units Table -->
    @if($receipt->units->isNotEmpty())
        <x-card title="Physical Serial / IMEI Units Registered in this Receipt">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 uppercase text-[11px]">
                            <th class="py-2 px-3">Item</th>
                            <th class="py-2 px-3">Serial Number</th>
                            <th class="py-2 px-3">IMEI 1</th>
                            <th class="py-2 px-3">IMEI 2</th>
                            <th class="py-2 px-3 text-center">Physical State</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($receipt->units as $u)
                            <tr>
                                <td class="py-2 px-3 font-semibold">{{ $u->item->name }}</td>
                                <td class="py-2 px-3 font-mono">{{ $u->serial_number ?? '—' }}</td>
                                <td class="py-2 px-3 font-mono">{{ $u->imei1 ?? '—' }}</td>
                                <td class="py-2 px-3 font-mono">{{ $u->imei2 ?? '—' }}</td>
                                <td class="py-2 px-3 text-center">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700">
                                        {{ $u->status->value }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</div>
@endsection
