@extends('layouts.owner')

@section('title', 'Payment: ' . $payment->payment_number)

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Supplier Payment: {{ $payment->payment_number }}"
        subtitle="Vendor: {{ $payment->supplier->name }} &bull; Payment mode: {{ str_replace('_', ' ', $payment->payment_mode->value) }}"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <x-button variant="secondary" :href="route('owner.purchasing.payments.index')">
                    Back to Payments
                </x-button>

                @if($payment->status->value === 'posted')
                    <form method="POST" action="{{ route('owner.purchasing.payments.reverse', $payment) }}" class="inline" onsubmit="return confirm('Are you sure you want to reverse this payment? This will update the subledger and reopen allocated bills.')">
                        @csrf
                        <input type="hidden" name="reason" value="Payment reversal requested by owner">
                        <x-button type="submit" variant="danger">
                            Reverse Payment
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

    <!-- Payment Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">Total Paid</span>
            <p class="text-2xl font-bold font-mono text-emerald-600 mt-1">
                {{ $tenant->currency }} {{ number_format((float) $payment->amount, 2) }}
            </p>
        </x-card>

        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">Allocated to Bills</span>
            <p class="text-2xl font-bold font-mono text-slate-800 mt-1">
                {{ $tenant->currency }} {{ number_format((float) $payment->allocated_amount, 2) }}
            </p>
        </x-card>

        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">Unapplied Advance Balance</span>
            <p class="text-2xl font-bold font-mono text-indigo-600 mt-1">
                {{ $tenant->currency }} {{ number_format((float) $payment->unallocated_amount, 2) }}
            </p>
        </x-card>

        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">Status</span>
            <div class="mt-1.5">
                <span class="px-2.5 py-1 rounded text-xs font-bold uppercase tracking-wider
                    @if($payment->status->value === 'posted') bg-emerald-50 text-emerald-700 border border-emerald-200
                    @elseif($payment->status->value === 'reversed') bg-rose-50 text-rose-700 border border-rose-200
                    @else bg-slate-100 text-slate-600 border border-slate-200 @endif">
                    {{ ucfirst($payment->status->value) }}
                </span>
            </div>
        </x-card>
    </div>

    <!-- Metadata Card -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs flex items-center justify-between text-xs text-slate-600">
        <div>
            <div><span class="font-semibold text-slate-800">Date:</span> {{ $payment->payment_date ? $payment->payment_date->format('d M Y') : $payment->created_at->format('d M Y') }}</div>
            <div><span class="font-semibold text-slate-800">Reference / UTR:</span> {{ $payment->reference_number ?? 'None' }}</div>
            <div><span class="font-semibold text-slate-800">Notes:</span> {{ $payment->notes ?? '—' }}</div>
        </div>
        <div class="text-right">
            <div>Posted by: <span class="font-semibold text-slate-900">{{ $payment->poster?->name ?? 'System' }}</span></div>
            @if($payment->reverser)
                <div class="text-rose-600">Reversed by: <span class="font-semibold">{{ $payment->reverser->name }}</span> ({{ $payment->reversed_at?->format('d M Y H:i') }})</div>
            @endif
        </div>
    </div>

    <!-- Existing Allocations -->
    <x-card title="Bill Allocations for this Payment">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 uppercase text-[11px]">
                        <th class="py-2 px-3">Purchase Bill #</th>
                        <th class="py-2 px-3">Invoice #</th>
                        <th class="py-2 px-3 text-right">Allocated Amount</th>
                        <th class="py-2 px-3">Allocation Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payment->allocations as $alloc)
                        <tr>
                            <td class="py-2.5 px-3 font-mono font-bold">
                                <a href="{{ route('owner.purchasing.bills.show', $alloc->bill) }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $alloc->bill->bill_number }}
                                </a>
                            </td>
                            <td class="py-2.5 px-3 font-mono text-slate-600">{{ $alloc->bill->supplier_invoice_number }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-600">
                                {{ $tenant->currency }} {{ number_format((float) $alloc->allocated_amount, 2) }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-500 font-mono text-[11px]">{{ $alloc->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-slate-400 italic">No bill allocations. Entire amount is held as vendor advance.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Allocate Remaining Advance Form -->
    @if((float) $payment->unallocated_amount > 0 && $payment->status->value === 'posted' && $outstandingBills->isNotEmpty())
        <x-card title="Allocate Remaining Advance to Outstanding Bill">
            <form method="POST" action="{{ route('owner.purchasing.payments.allocate_advance', $payment) }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                @csrf
                <div class="md:col-span-6">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select Outstanding Bill</label>
                    <select name="purchase_bill_id" required class="w-full text-xs rounded-lg border-slate-300 py-2 px-3">
                        <option value="">-- Choose Bill --</option>
                        @foreach($outstandingBills as $bill)
                            @php
                                $balanceDue = (float) $bill->total_amount - (float) $bill->paid_amount;
                            @endphp
                            <option value="{{ $bill->id }}">
                                {{ $bill->bill_number }} (Due: {{ $tenant->currency }} {{ number_format($balanceDue, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Allocation Amount (Max: {{ $tenant->currency }} {{ number_format((float) $payment->unallocated_amount, 2) }})</label>
                    <input
                        type="number"
                        name="amount"
                        step="0.01"
                        min="0.01"
                        max="{{ (float) $payment->unallocated_amount }}"
                        required
                        placeholder="0.00"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 py-2 px-3"
                    >
                </div>
                <div class="md:col-span-2">
                    <x-button type="submit" variant="primary" class="w-full justify-center">
                        Apply Advance
                    </x-button>
                </div>
            </form>
        </x-card>
    @endif
</div>
@endsection
