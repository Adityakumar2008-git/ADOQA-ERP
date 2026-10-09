@extends('layouts.owner')

@section('title', 'Supplier Payments')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Supplier Payments"
        subtitle="Disbursement transactions, bank transfers, payment allocations to bills, and vendor advance credits."
    >
        <x-slot:actions>
            <x-button variant="primary" :href="route('owner.purchasing.payments.create')">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Record Payment
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        @if($payments->isEmpty())
            <x-empty-state
                title="No supplier payments recorded"
                description="Record disbursements made to vendors against purchase bills or advances."
                actionText="Record Payment"
                :actionUrl="route('owner.purchasing.payments.create')"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-4">Payment #</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Supplier</th>
                            <th class="py-3 px-4">Mode</th>
                            <th class="py-3 px-4 text-right">Disbursed Amount</th>
                            <th class="py-3 px-4 text-right">Unapplied Advance</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($payments as $p)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    <a href="{{ route('owner.purchasing.payments.show', $p) }}" class="text-indigo-600 hover:text-indigo-800">
                                        {{ $p->payment_number }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-slate-600 font-mono text-[11px]">
                                    {{ $p->payment_date ? $p->payment_date->format('Y-m-d') : $p->created_at->format('Y-m-d') }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800">
                                    {{ $p->supplier->name }}
                                </td>
                                <td class="py-3 px-4 capitalize text-slate-700">
                                    {{ str_replace('_', ' ', $p->payment_mode->value) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600 text-sm">
                                    {{ $tenant->currency }} {{ number_format((float) $p->amount, 2) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold {{ (float) $p->unallocated_amount > 0 ? 'text-indigo-600' : 'text-slate-400' }}">
                                    {{ (float) $p->unallocated_amount > 0 ? $tenant->currency . ' ' . number_format((float) $p->unallocated_amount, 2) : '—' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        @if($p->status->value === 'posted') bg-emerald-50 text-emerald-700 border border-emerald-200
                                        @elseif($p->status->value === 'reversed') bg-rose-50 text-rose-700 border border-rose-200
                                        @else bg-slate-100 text-slate-600 border border-slate-200 @endif">
                                        {{ ucfirst($p->status->value) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('owner.purchasing.payments.show', $p) }}" class="font-semibold text-xs text-indigo-600 hover:text-indigo-800">
                                        View Details &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($payments->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $payments->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
