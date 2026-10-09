<?php

namespace App\Actions\Procurement;

use App\Enums\PurchaseOrderStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Illuminate\Validation\ValidationException;

class ApprovePurchaseOrder
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    public function execute(User $actor, Business $business, PurchaseOrder $order): PurchaseOrder
    {
        if ($order->business_id !== $business->id) {
            throw ValidationException::withMessages(['order' => ['Unauthorized purchase order context.']]);
        }

        // Authorize actor possesses purchases.approve permission
        if ($this->authorizationService->cannot($actor, 'purchases.approve', $business)) {
            throw ValidationException::withMessages(['authorization' => ['You do not have permission to approve purchase orders.']]);
        }

        if (! in_array($order->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Submitted], true)) {
            throw ValidationException::withMessages(['status' => ["Order in status '{$order->status->value}' cannot be approved."]]);
        }

        $order->update([
            'status' => PurchaseOrderStatus::Approved,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        AuditLog::create([
            'business_id' => $business->id,
            'user_id' => $actor->id,
            'action' => 'purchase_order.approved',
            'auditable_type' => PurchaseOrder::class,
            'auditable_id' => $order->id,
            'payload' => [
                'order_number' => $order->order_number,
                'approved_at' => $order->approved_at->toIso8601String(),
            ],
        ]);

        return $order->fresh(['lines.item', 'supplier']);
    }
}
