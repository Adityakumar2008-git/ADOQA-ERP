<?php

namespace App\Actions\Procurement;

use App\Enums\SupplierLedgerEntryType;
use App\Enums\SupplierStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSupplier
{
    /**
     * @param array{
     *     name: string,
     *     code?: ?string,
     *     phone?: ?string,
     *     email?: ?string,
     *     address?: ?string,
     *     gst_number?: ?string,
     *     credit_limit?: ?numeric,
     *     opening_balance?: ?numeric,
     *     status?: string|SupplierStatus,
     *     notes?: ?string
     * } $data
     */
    public function execute(User $actor, Business $business, array $data): Supplier
    {
        $name = trim($data['name']);
        $code = filled($data['code'] ?? null) ? strtoupper(trim($data['code'])) : null;

        if ($code !== null && Supplier::where('business_id', $business->id)->where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'code' => ["A supplier with code '{$code}' already exists for this business."],
            ]);
        }

        $openingBalance = (float) ($data['opening_balance'] ?? 0.00);
        $creditLimit = filled($data['credit_limit'] ?? null) ? (float) $data['credit_limit'] : null;

        $status = isset($data['status'])
            ? ($data['status'] instanceof SupplierStatus ? $data['status'] : SupplierStatus::from($data['status']))
            : SupplierStatus::Active;

        return DB::transaction(function () use ($actor, $business, $name, $code, $openingBalance, $creditLimit, $status, $data) {
            $supplier = Supplier::create([
                'business_id' => $business->id,
                'name' => $name,
                'code' => $code,
                'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
                'email' => filled($data['email'] ?? null) ? strtolower(trim($data['email'])) : null,
                'address' => filled($data['address'] ?? null) ? trim($data['address']) : null,
                'gst_number' => filled($data['gst_number'] ?? null) ? strtoupper(trim($data['gst_number'])) : null,
                'credit_limit' => $creditLimit,
                'opening_balance' => $openingBalance,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]);

            // If an opening balance is configured, record it in the supplier subledger
            if ($openingBalance != 0.00) {
                SupplierLedgerEntry::create([
                    'business_id' => $business->id,
                    'supplier_id' => $supplier->id,
                    'entry_type' => SupplierLedgerEntryType::Adjustment,
                    'amount' => $openingBalance,
                    'running_balance' => $openingBalance,
                    'source_type' => 'opening_balance',
                    'source_id' => $supplier->id,
                    'source_reference' => 'OPENING',
                    'posted_at' => now(),
                    'actor_id' => $actor->id,
                    'notes' => 'Opening account balance',
                ]);
            }

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'supplier.created',
                'auditable_type' => Supplier::class,
                'auditable_id' => $supplier->id,
                'payload' => [
                    'name' => $supplier->name,
                    'code' => $supplier->code,
                    'opening_balance' => $openingBalance,
                ],
            ]);

            return $supplier;
        });
    }
}
