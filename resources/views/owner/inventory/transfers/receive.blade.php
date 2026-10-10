@extends('layouts.owner')

@section('title', 'Receive Transfer: ' . $transfer->transfer_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-page-header
        title="Receive Transfer: {{ $transfer->transfer_number }}"
        subtitle="Verify arrival at destination depot: {{ $transfer->destinationWarehouse->name }}."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.inventory.transfers.index')">
                Back to Transfers
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if($errors->any())
        <x-alert type="error">
            <ul class="list-disc list-inside text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <div class="bg-[#E8F8EE] border border-[#22B14C]/30 rounded-xl p-4 text-xs flex items-center justify-between text-slate-900">
        <div>
            <span class="font-bold">Dispatched from:</span> {{ $transfer->sourceWarehouse->name }}
            <span class="mx-2">&rarr;</span>
            <span class="font-bold">Receiving at:</span> {{ $transfer->destinationWarehouse->name }}
        </div>
        <div class="font-mono text-[#0E5C25]">
            Dispatched on {{ $transfer->dispatched_at?->format('d M Y H:i') }}
        </div>
    </div>

    <x-card title="Verify Quantities Received">
        <form method="POST" action="{{ route('owner.inventory.transfers.receive.store', $transfer) }}" class="space-y-6">
            @csrf

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 font-semibold uppercase text-[11px]">
                            <th class="py-2.5 px-3">Item & SKU</th>
                            <th class="py-2.5 px-3 text-right">Dispatched Qty</th>
                            <th class="py-2.5 px-3 text-right">Accepted Qty *</th>
                            <th class="py-2.5 px-3 text-right">Damaged Qty</th>
                            <th class="py-2.5 px-3 text-right">Lost Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($transfer->lines as $line)
                            <tr>
                                <td class="py-3 px-3">
                                    <span class="font-bold text-slate-900 block">{{ $line->item->name }}</span>
                                    <span class="text-[11px] text-slate-500 font-mono">{{ $line->item->sku }}</span>
                                    @if($line->batch)
                                        <span class="text-[10px] text-[#1C9640] font-mono block">Batch: {{ $line->batch->batch_number }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-slate-800">
                                    {{ number_format((float) $line->quantity, 2) }}
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <input
                                        type="number"
                                        name="receipts[{{ $line->id }}][received_quantity]"
                                        step="any"
                                        min="0"
                                        value="{{ old("receipts.{$line->id}.received_quantity", (float) $line->quantity) }}"
                                        required
                                        class="w-24 text-right font-mono text-xs rounded border-slate-300 py-1 px-2 focus:ring-[#22B14C]"
                                    >
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <input
                                        type="number"
                                        name="receipts[{{ $line->id }}][damaged_quantity]"
                                        step="any"
                                        min="0"
                                        value="{{ old("receipts.{$line->id}.damaged_quantity", 0) }}"
                                        class="w-20 text-right font-mono text-xs rounded border-slate-300 py-1 px-2 focus:ring-rose-500 text-rose-700"
                                    >
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <input
                                        type="number"
                                        name="receipts[{{ $line->id }}][lost_quantity]"
                                        step="any"
                                        min="0"
                                        value="{{ old("receipts.{$line->id}.lost_quantity", 0) }}"
                                        class="w-20 text-right font-mono text-xs rounded border-slate-300 py-1 px-2 focus:ring-amber-500 text-amber-700"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <x-button variant="secondary" :href="route('owner.inventory.transfers.index')">Cancel</x-button>
                <x-button type="submit" variant="primary">Confirm Receipt into Destination Depot</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
