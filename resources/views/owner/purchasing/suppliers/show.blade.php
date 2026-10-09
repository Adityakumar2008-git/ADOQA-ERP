@extends('layouts.owner')

@section('title', 'Supplier: ' . $supplier->name)

@section('content')
<div class="space-y-6">
    <x-page-header
        title="{{ $supplier->name }}"
        subtitle="Vendor code: {{ $supplier->code }} &bull; Procurement and billing records."
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <x-button variant="secondary" :href="route('owner.purchasing.suppliers.ledger', $supplier)">
                    View Subledger
                </x-button>
                <x-button variant="primary" :href="route('owner.purchasing.orders.create', ['supplier_id' => $supplier->id])">
                    Create PO
                </x-button>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif

    <!-- Stat Cards Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">Current Outstanding</span>
            <p class="text-2xl font-bold font-mono {{ (float) $supplier->current_balance > 0 ? 'text-rose-600' : 'text-emerald-600' }} mt-1">
                {{ $tenant->currency }} {{ number_format((float) $supplier->current_balance, 2) }}
            </p>
        </x-card>

        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">Unallocated Advances</span>
            <p class="text-2xl font-bold font-mono text-indigo-600 mt-1">
                {{ $tenant->currency }} {{ number_format((float) $supplier->unallocated_advance, 2) }}
            </p>
        </x-card>

        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">Credit Limit</span>
            <p class="text-2xl font-bold font-mono text-slate-800 mt-1">
                {{ $supplier->credit_limit ? $tenant->currency . ' ' . number_format((float) $supplier->credit_limit, 2) : 'No Limit' }}
            </p>
        </x-card>

        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">GSTIN / Tax ID</span>
            <p class="text-lg font-mono text-slate-900 mt-1">
                {{ $supplier->gst_number ?? 'Not Registered' }}
            </p>
        </x-card>
    </div>

    <!-- Recent Purchase Bills & Orders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Purchase Bills -->
        <x-card title="Recent Purchase Bills">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 uppercase text-[11px]">
                            <th class="py-2 px-3">Bill #</th>
                            <th class="py-2 px-3">Invoice #</th>
                            <th class="py-2 px-3 text-right">Amount</th>
                            <th class="py-2 px-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($bills as $bill)
                            <tr>
                                <td class="py-2 px-3 font-mono font-bold">
                                    <a href="{{ route('owner.purchasing.bills.show', $bill) }}" class="text-indigo-600 hover:text-indigo-800">
                                        {{ $bill->bill_number }}
                                    </a>
                                </td>
                                <td class="py-2 px-3 font-mono text-slate-600">{{ $bill->supplier_invoice_number }}</td>
                                <td class="py-2 px-3 text-right font-mono font-bold">{{ $tenant->currency }} {{ number_format((float) $bill->total_amount, 2) }}</td>
                                <td class="py-2 px-3 text-center">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $bill->status->value === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $bill->status->value }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-slate-400 italic">No purchase bills recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- Payments -->
        <x-card title="Recent Payments">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 uppercase text-[11px]">
                            <th class="py-2 px-3">Payment #</th>
                            <th class="py-2 px-3">Mode</th>
                            <th class="py-2 px-3 text-right">Amount</th>
                            <th class="py-2 px-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($payments as $payment)
                            <tr>
                                <td class="py-2 px-3 font-mono font-bold">
                                    <a href="{{ route('owner.purchasing.payments.show', $payment) }}" class="text-indigo-600 hover:text-indigo-800">
                                        {{ $payment->payment_number }}
                                    </a>
                                </td>
                                <td class="py-2 px-3 capitalize">{{ str_replace('_', ' ', $payment->payment_mode->value) }}</td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600">{{ $tenant->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="py-2 px-3 text-center">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $payment->status->value === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $payment->status->value }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-slate-400 italic">No payments posted.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
@endsection
