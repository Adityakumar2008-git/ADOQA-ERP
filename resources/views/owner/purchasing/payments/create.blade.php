@extends('layouts.owner')

@section('title', 'Record Supplier Payment')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-page-header
        title="Record Supplier Payment"
        subtitle="Disburse payments to vendors, settle outstanding purchase bills, or record advances."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.purchasing.payments.index')">
                Back to Payments
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

    <x-card>
        <form method="POST" action="{{ route('owner.purchasing.payments.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="supplier_id" class="block text-xs font-semibold text-slate-700 mb-1">Pay To Supplier *</label>
                    <select
                        name="supplier_id"
                        id="supplier_id"
                        required
                        onchange="window.location.href = '{{ route('owner.purchasing.payments.create') }}?supplier_id=' + this.value"
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                    >
                        <option value="">-- Choose Vendor --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id', request('supplier_id')) === $s->id)>
                                {{ $s->name }} ({{ $s->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="amount" class="block text-xs font-semibold text-slate-700 mb-1">Payment Amount ({{ $tenant->currency }}) *</label>
                    <input
                        type="number"
                        name="amount"
                        id="amount"
                        step="0.01"
                        min="0.01"
                        value="{{ old('amount') }}"
                        required
                        placeholder="0.00"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                    >
                </div>

                <div>
                    <label for="payment_date" class="block text-xs font-semibold text-slate-700 mb-1">Payment Date *</label>
                    <input
                        type="date"
                        name="payment_date"
                        id="payment_date"
                        value="{{ old('payment_date', date('Y-m-d')) }}"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="payment_mode" class="block text-xs font-semibold text-slate-700 mb-1">Payment Mode *</label>
                    <select
                        name="payment_mode"
                        id="payment_mode"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                    >
                        <option value="bank_transfer" @selected(old('payment_mode') === 'bank_transfer')>Bank Transfer (NEFT/RTGS/IMPS)</option>
                        <option value="upi" @selected(old('payment_mode') === 'upi')>UPI</option>
                        <option value="cash" @selected(old('payment_mode') === 'cash')>Cash</option>
                        <option value="cheque" @selected(old('payment_mode') === 'cheque')>Cheque</option>
                        <option value="card" @selected(old('payment_mode') === 'card')>Card</option>
                    </select>
                </div>

                <div>
                    <label for="reference_number" class="block text-xs font-semibold text-slate-700 mb-1">Transaction Ref / Cheque / UTR #</label>
                    <input
                        type="text"
                        name="reference_number"
                        id="reference_number"
                        value="{{ old('reference_number') }}"
                        placeholder="e.g. UTR123456789"
                        class="w-full text-xs font-mono uppercase rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                    >
                </div>
            </div>

            <!-- Outstanding Bills Allocation Section -->
            @if($outstandingBills->isNotEmpty())
                <div class="pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Allocate Payment to Outstanding Bills</h4>
                        <p class="text-[11px] text-slate-500">Unallocated surplus will automatically be credited as vendor advance.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 uppercase text-[11px]">
                                    <th class="py-2 px-3">Bill #</th>
                                    <th class="py-2 px-3">Invoice #</th>
                                    <th class="py-2 px-3 text-right">Bill Total</th>
                                    <th class="py-2 px-3 text-right">Balance Due</th>
                                    <th class="py-2 px-3 text-right">Allocate Amount ({{ $tenant->currency }})</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($outstandingBills as $index => $bill)
                                    @php
                                        $balanceDue = (float) $bill->total_amount - (float) $bill->paid_amount;
                                    @endphp
                                    <tr>
                                        <td class="py-2.5 px-3 font-mono font-bold">{{ $bill->bill_number }}</td>
                                        <td class="py-2.5 px-3 font-mono text-slate-600">{{ $bill->supplier_invoice_number }}</td>
                                        <td class="py-2.5 px-3 text-right font-mono">{{ $tenant->currency }} {{ number_format((float) $bill->total_amount, 2) }}</td>
                                        <td class="py-2.5 px-3 text-right font-mono font-bold text-rose-600">{{ $tenant->currency }} {{ number_format($balanceDue, 2) }}</td>
                                        <td class="py-2.5 px-3 text-right">
                                            <input type="hidden" name="allocations[{{ $index }}][purchase_bill_id]" value="{{ $bill->id }}">
                                            <input
                                                type="number"
                                                name="allocations[{{ $index }}][amount]"
                                                step="0.01"
                                                min="0.01"
                                                max="{{ $balanceDue }}"
                                                placeholder="0.00"
                                                class="w-32 text-right font-mono text-xs rounded border-slate-300 py-1 px-2 focus:ring-[#22B14C]"
                                            >
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div>
                <label for="notes" class="block text-xs font-semibold text-slate-700 mb-1">Payment Remarks</label>
                <textarea
                    name="notes"
                    id="notes"
                    rows="2"
                    placeholder="Disbursement details, recipient remarks..."
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                >{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <x-button variant="secondary" :href="route('owner.purchasing.payments.index')">Cancel</x-button>
                <x-button type="submit" variant="primary">Post Payment &amp; Update Subledger</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
