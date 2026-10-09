@extends('layouts.owner')

@section('title', 'PO: ' . $order->order_number)

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Purchase Order: {{ $order->order_number }}"
        subtitle="Vendor: {{ $order->supplier->name }} &bull; Order date: {{ $order->order_date ? $order->order_date->format('d M Y') : $order->created_at->format('d M Y') }}"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <x-button variant="secondary" :href="route('owner.purchasing.orders.index')">
                    Back to Orders
                </x-button>

                @if($order->status->value === 'draft')
                    <form method="POST" action="{{ route('owner.purchasing.orders.approve', $order) }}" class="inline">
                        @csrf
                        <x-button type="submit" variant="primary">
                            Approve Order
                        </x-button>
                    </form>
                @endif

                @if(in_array($order->status->value, ['approved', 'partially_received']))
                    <x-button variant="secondary" :href="route('owner.purchasing.receipts.create', ['purchase_order_id' => $order->id])">
                        Prepare Goods Receipt
                    </x-button>
                    <x-button variant="secondary" :href="route('owner.purchasing.bills.create', ['purchase_order_id' => $order->id])">
                        Record Purchase Bill
                    </x-button>
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
            <span class="text-xs text-slate-500 block uppercase font-semibold">Current State</span>
            <span class="text-sm font-bold uppercase tracking-wider
                @if($order->status->value === 'approved') text-indigo-600
                @elseif($order->status->value === 'received') text-emerald-600
                @elseif($order->status->value === 'partially_received') text-amber-600
                @elseif($order->status->value === 'cancelled') text-rose-600
                @else text-slate-600 @endif">
                {{ str_replace('_', ' ', $order->status->value) }}
            </span>
        </div>
        <div class="text-right text-xs text-slate-600">
            <div>Created by: <span class="font-semibold text-slate-900">{{ $order->creator?->name ?? 'System' }}</span></div>
            @if($order->approver)
                <div>Approved by: <span class="font-semibold text-slate-900">{{ $order->approver->name }}</span> ({{ $order->approved_at?->format('d M Y H:i') }})</div>
            @endif
        </div>
    </div>

    <!-- Order Lines -->
    <x-card title="Ordered Line Items">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 font-semibold uppercase text-[11px]">
                        <th class="py-2.5 px-3">Item & SKU</th>
                        <th class="py-2.5 px-3 text-right">Ordered Qty</th>
                        <th class="py-2.5 px-3 text-right">Received Qty</th>
                        <th class="py-2.5 px-3 text-right">Unit Price</th>
                        <th class="py-2.5 px-3 text-right">Tax Rate</th>
                        <th class="py-2.5 px-3 text-right">Total Line Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($order->lines as $line)
                        <tr>
                            <td class="py-3 px-3">
                                <span class="font-bold text-slate-900 block">{{ $line->item->name }}</span>
                                <span class="text-[11px] text-slate-500 font-mono">{{ $line->item->sku }}</span>
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-800">
                                {{ number_format((float) $line->ordered_quantity, 2) }} {{ $line->item->unit?->code }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold {{ (float) $line->received_quantity >= (float) $line->ordered_quantity ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ number_format((float) $line->received_quantity, 2) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-slate-700">
                                {{ $tenant->currency }} {{ number_format((float) $line->unit_price, 2) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-slate-600">
                                {{ $line->tax_rate ? number_format((float) $line->tax_rate, 1) . '%' : '0%' }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                                {{ $tenant->currency }} {{ number_format((float) $line->total_amount, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-200 font-bold bg-slate-50">
                        <td colspan="5" class="py-3 px-3 text-right uppercase text-slate-700">Order Grand Total:</td>
                        <td class="py-3 px-3 text-right font-mono text-base text-indigo-700">
                            {{ $tenant->currency }} {{ number_format((float) $order->total_amount, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-card>
</div>
@endsection
