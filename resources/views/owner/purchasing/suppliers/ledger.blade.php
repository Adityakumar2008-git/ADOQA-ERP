@extends('layouts.owner')

@inject('authService', 'App\Services\Auth\AuthorizationService')

@section('title', 'Supplier Subledger: ' . $supplier->name)

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Supplier Subledger: {{ $supplier->name }}"
        subtitle="Chronological transaction ledger of bills, payments, advances, and adjustments."
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <x-button variant="secondary" :href="route('owner.purchasing.suppliers.show', $supplier)">
                    Supplier Details
                </x-button>
                @if($authService->can(auth()->user(), 'suppliers.payments.create', $tenant))
                    <x-button variant="primary" :href="route('owner.purchasing.payments.create', ['supplier_id' => $supplier->id])">
                        Record Payment
                    </x-button>
                @endif
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Subledger Balance Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">Current Outstanding Balance</span>
            <p class="text-2xl font-bold font-mono {{ (float) $supplier->current_balance > 0 ? 'text-rose-600' : 'text-emerald-600' }} mt-1">
                {{ $tenant->currency }} {{ number_format((float) $supplier->current_balance, 2) }}
            </p>
        </x-card>

        <x-card>
            <span class="text-xs font-bold uppercase text-slate-500">Unallocated Advance</span>
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
    </div>

    <!-- Ledger Entries Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        @if($entries->isEmpty())
            <x-empty-state
                title="No subledger entries recorded"
                description="Posted bills and payments will produce verified financial entries here."
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-4">Posting Date</th>
                            <th class="py-3 px-4">Entry Type</th>
                            <th class="py-3 px-4">Document Reference</th>
                            <th class="py-3 px-4 text-right">Debit / Billed (+)</th>
                            <th class="py-3 px-4 text-right">Credit / Paid (-)</th>
                            <th class="py-3 px-4 text-right">Running Balance</th>
                            <th class="py-3 px-4">Description</th>
                            <th class="py-3 px-4">Actor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($entries as $entry)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-mono text-slate-600 text-[11px] whitespace-nowrap">
                                    {{ $entry->posted_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        @if(in_array($entry->entry_type->value, ['purchase_bill', 'opening_balance'])) bg-rose-50 text-rose-700 border border-rose-200
                                        @elseif(in_array($entry->entry_type->value, ['supplier_payment', 'supplier_advance'])) bg-emerald-50 text-emerald-700 border border-emerald-200
                                        @else bg-sky-50 text-sky-700 border border-sky-200 @endif">
                                        {{ str_replace('_', ' ', $entry->entry_type->value) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono font-medium text-slate-800">
                                    {{ $entry->source_reference ?? $entry->source_document_id }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold {{ (float) $entry->debit_amount > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                    {{ (float) $entry->debit_amount > 0 ? $tenant->currency . ' ' . number_format((float) $entry->debit_amount, 2) : '—' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold {{ (float) $entry->credit_amount > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                                    {{ (float) $entry->credit_amount > 0 ? $tenant->currency . ' ' . number_format((float) $entry->credit_amount, 2) : '—' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                    {{ $tenant->currency }} {{ number_format((float) $entry->running_balance, 2) }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 text-[11px] max-w-xs truncate" title="{{ $entry->notes }}">
                                    {{ $entry->notes ?? '—' }}
                                </td>
                                <td class="py-3 px-4 text-slate-500 text-[11px]">
                                    {{ $entry->actor?->name ?? 'System' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($entries->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $entries->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
