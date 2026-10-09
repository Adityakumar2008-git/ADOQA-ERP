@extends('layouts.owner')

@section('title', 'Inventory & Ledger Reconciliation')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Ledger & Subledger Reconciliation"
        subtitle="Cryptographic & transactional integrity audit across stock balances, movements, physical units, and supplier payables."
    >
        <x-slot:actions>
            <x-button variant="primary" :href="route('owner.inventory.stock.reconcile')">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Re-run Audit
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <!-- Overall Status Alert -->
    <div class="p-4 rounded-xl border flex items-center justify-between {{ $report['reconciled'] ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' }}">
        <div class="flex items-center space-x-3">
            @if($report['reconciled'])
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <h3 class="font-bold text-sm">System Invariants Reconciled</h3>
                    <p class="text-xs text-emerald-700">All current balances match posted movement histories with zero mathematical or physical discrepancies.</p>
                </div>
            @else
                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <h3 class="font-bold text-sm">Discrepancies Detected</h3>
                    <p class="text-xs text-rose-700">One or more ledger projections deviate from append-only movement logs. Review details below.</p>
                </div>
            @endif
        </div>
        <span class="text-xs font-mono font-bold">{{ $report['timestamp'] }}</span>
    </div>

    <!-- Audit Dimensions Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Stock Balance Invariant -->
        <x-card>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase text-slate-500">Stock Balances vs Movements</span>
                <span class="w-2.5 h-2.5 rounded-full {{ count($report['stock_discrepancies']) === 0 ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
            </div>
            <p class="text-2xl font-bold font-mono text-slate-900">{{ count($report['stock_discrepancies']) }}</p>
            <p class="text-xs text-slate-500 mt-1">Discrepancy rows identified</p>
        </x-card>

        <!-- Unit Count Invariant -->
        <x-card>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase text-slate-500">Serial/IMEI Unit Count Integrity</span>
                <span class="w-2.5 h-2.5 rounded-full {{ count($report['unit_discrepancies']) === 0 ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
            </div>
            <p class="text-2xl font-bold font-mono text-slate-900">{{ count($report['unit_discrepancies']) }}</p>
            <p class="text-xs text-slate-500 mt-1">Physical unit vs balance mismatches</p>
        </x-card>

        <!-- Supplier Subledger Invariant -->
        <x-card>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase text-slate-500">Supplier Subledger Payables</span>
                <span class="w-2.5 h-2.5 rounded-full {{ count($report['supplier_discrepancies']) === 0 ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
            </div>
            <p class="text-2xl font-bold font-mono text-slate-900">{{ count($report['supplier_discrepancies']) }}</p>
            <p class="text-xs text-slate-500 mt-1">Subledger reconciliation discrepancies</p>
        </x-card>
    </div>

    @if(!$report['reconciled'])
        @if(count($report['stock_discrepancies']) > 0)
            <x-card title="Stock Balance Discrepancy Details">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 uppercase">
                                <th class="py-2 px-3">Item</th>
                                <th class="py-2 px-3">Warehouse</th>
                                <th class="py-2 px-3 text-right">Balance Projection</th>
                                <th class="py-2 px-3 text-right">Movement Sum</th>
                                <th class="py-2 px-3 text-right">Difference</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($report['stock_discrepancies'] as $disc)
                                <tr>
                                    <td class="py-2 px-3 font-semibold">{{ $disc['item_name'] }}</td>
                                    <td class="py-2 px-3">{{ $disc['warehouse_name'] }}</td>
                                    <td class="py-2 px-3 text-right font-mono">{{ number_format($disc['balance_quantity'], 2) }}</td>
                                    <td class="py-2 px-3 text-right font-mono">{{ number_format($disc['movement_quantity'], 2) }}</td>
                                    <td class="py-2 px-3 text-right font-mono font-bold text-rose-600">{{ number_format($disc['difference'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif
    @endif
</div>
@endsection
