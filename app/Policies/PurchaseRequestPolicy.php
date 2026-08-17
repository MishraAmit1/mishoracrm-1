<?php

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\User;

class PurchaseRequestPolicy
{
    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if ($user->tenant_id !== $purchaseRequest->tenant_id) {
            return false;
        }

        if ($user->user_type === 'superadmin' || $user->can('purchase_requests.view_all')) {
            return true;
        }

        return $user->can('purchase_requests.view_own') && $purchaseRequest->requested_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->user_type === 'superadmin' || $user->can('purchase_requests.create');
    }

    // Modify requires the single "purchase_requests.edit" permission AND
    // the request still being pending — once approved/rejected/converted
    // it is locked, matching the plan's confirmed decision.
    public function modify(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if ($user->tenant_id !== $purchaseRequest->tenant_id) {
            return false;
        }

        if (!$purchaseRequest->isPending()) {
            return false;
        }

        if (!$user->can('purchase_requests.edit')) {
            return false;
        }

        return $user->user_type === 'superadmin'
            || $user->can('purchase_requests.view_all')
            || $purchaseRequest->requested_by === $user->id;
    }

    public function update(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $this->modify($user, $purchaseRequest);
    }

    // Approve is a separate permission, not tied to ownership — any
    // holder of "purchase_requests.approve" can approve/reject any
    // pending request in the tenant.
    public function approve(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if ($user->tenant_id !== $purchaseRequest->tenant_id) {
            return false;
        }

        if (!$purchaseRequest->isPending()) {
            return false;
        }

        return $user->user_type === 'superadmin' || $user->can('purchase_requests.approve');
    }

    public function delete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if ($user->tenant_id !== $purchaseRequest->tenant_id) {
            return false;
        }

        if (!$user->can('purchase_requests.delete')) {
            return false;
        }

        return $user->user_type === 'superadmin'
            || $user->can('purchase_requests.view_all')
            || $purchaseRequest->requested_by === $user->id;
    }
}
