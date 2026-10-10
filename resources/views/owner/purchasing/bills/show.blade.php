@extends('layouts.owner')

@inject('authService', 'App\Services\Auth\AuthorizationService')

@section('title', 'Purchase Bill: ' . $bill->bill_number)

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Purchase Bill: {{ $bill->bill_number }}"
        subtitle="Vendor: {{ $bill->supplier->name }} &bull; Supplier Invoice: {{ $bill->supplier_invoice_number }}"
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <x-button variant="secondary" :href="route('owner.purchasing.bills.index')">
                    Back to Bills
                </x-button>

                @if($bill->status->value === 'draft' && $authService->can(auth()->user(), 'purchases.bills.post', $tenant))
                    <form method="POST" action="{{ route('owner.purchasing.bills.post', $bill) }}" class="inline">
                        @csrf
                        <x-button type="submit" variant="primary">
                            Post Bill to Subledger
                        </x-button>
                    </form>
                @endif

                @if(in_array($bill->status->value, ['posted', 'partially_paid']) && $authService->can(auth()->user(), 'suppliers.payments.create', $tenant))
                    <x-button variant="primary" :href="route('owner.purchasing.payments.create', ['supplier_id' => $bill->supplier_id])">
                        Record Payment
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

    <!-- Status Banner & Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">Bill Total</span>
            <p class="text-2xl font-bold font-mono text-slate-900 mt-1">
                {{ $tenant->currency }} {{ number_format((float) $bill->total_amount, 2) }}
            </p>
        </x-card>

        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">Amount Paid</span>
            <p class="text-2xl font-bold font-mono text-emerald-600 mt-1">
                {{ $tenant->currency }} {{ number_format((float) $bill->paid_amount, 2) }}
            </p>
        </x-card>

        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">Balance Due</span>
            <p class="text-2xl font-bold font-mono text-rose-600 mt-1">
                {{ $tenant->currency }} {{ number_format((float) $bill->total_amount - (float) $bill->paid_amount, 2) }}
            </p>
        </x-card>

        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">Status</span>
            <div class="mt-1.5">
                <span class="px-2.5 py-1 rounded text-xs font-bold uppercase tracking-wider
                    @if($bill->status->value === 'posted') bg-rose-50 text-rose-700 border border-rose-200
                    @elseif($bill->status->value === 'paid') bg-emerald-50 text-emerald-700 border border-emerald-200
                    @elseif($bill->status->value === 'partially_paid') bg-amber-50 text-amber-700 border border-amber-200
                    @else bg-slate-100 text-slate-600 border border-slate-200 @endif">
                    {{ str_replace('_', ' ', $bill->status->value) }}
                </span>
            </div>
        </x-card>
    </div>

    <!-- Bill Lines Table -->
    <x-card title="Invoiced Lines">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 font-semibold uppercase text-[11px]">
                        <th class="py-2.5 px-3">Item & SKU</th>
                        <th class="py-2.5 px-3 text-right">Billed Quantity</th>
                        <th class="py-2.5 px-3 text-right">Agreed Unit Cost</th>
                        <th class="py-2.5 px-3 text-right">Tax Rate</th>
                        <th class="py-2.5 px-3 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($bill->lines as $line)
                        <tr>
                            <td class="py-3 px-3">
                                <span class="font-bold text-slate-900 block">{{ $line->item->name }}</span>
                                <span class="text-[11px] text-slate-500 font-mono">{{ $line->item->sku }}</span>
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-800">
                                {{ number_format((float) $line->quantity, 2) }} {{ $line->item->unit?->code }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-slate-700">
                                {{ $tenant->currency }} {{ number_format((float) $line->unit_cost, 2) }}
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
            </table>
        </div>
    </x-card>

    <!-- Payment Allocations Table -->
    @if($bill->allocations->isNotEmpty())
        <x-card title="Settlement Allocations Applied to this Bill">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 uppercase text-[11px]">
                            <th class="py-2 px-3">Payment #</th>
                            <th class="py-2 px-3">Payment Mode</th>
                            <th class="py-2 px-3 text-right">Allocated Amount</th>
                            <th class="py-2 px-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($bill->allocations as $alloc)
                            <tr>
                                <td class="py-2 px-3 font-mono font-bold">
                                    <a href="{{ route('owner.purchasing.payments.show', $alloc->payment) }}" class="text-[#1C9640] hover:text-[#13682C]">
                                        {{ $alloc->payment->payment_number }}
                                    </a>
                                </td>
                                <td class="py-2 px-3 capitalize">{{ str_replace('_', ' ', $alloc->payment->payment_mode->value) }}</td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600">
                                    {{ $tenant->currency }} {{ number_format((float) $alloc->allocated_amount, 2) }}
                                </td>
                                <td class="py-2 px-3 text-slate-500 font-mono text-[11px]">
                                    {{ $alloc->created_at->format('Y-m-d') }}
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
