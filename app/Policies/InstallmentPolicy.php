<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Installment;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstallmentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Installment');
    }

    public function view(AuthUser $authUser, Installment $installment): bool
    {
        return $authUser->can('View:Installment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Installment');
    }

    public function update(AuthUser $authUser, Installment $installment): bool
    {
        return $authUser->can('Update:Installment');
    }

    public function delete(AuthUser $authUser, Installment $installment): bool
    {
        return $authUser->can('Delete:Installment') && $installment->isDeletable();
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        // Bulk deletion cannot enforce each installment's payment-history check.
        return false;
    }

    public function restore(AuthUser $authUser, Installment $installment): bool
    {
        return $authUser->can('Restore:Installment');
    }

    public function forceDelete(AuthUser $authUser, Installment $installment): bool
    {
        return $authUser->can('ForceDelete:Installment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Installment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Installment');
    }

    public function replicate(AuthUser $authUser, Installment $installment): bool
    {
        return $authUser->can('Replicate:Installment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Installment');
    }

    public function cancel(AuthUser $authUser, Installment $installment): bool
    {
        return ($authUser->can('Cancel:Installment') || $authUser->can('Update:Installment') || $authUser->hasRole('super_admin'))
            && in_array($installment->status, ['pending', 'partial'], true);
    }

    public function cancelAny(AuthUser $authUser): bool
    {
        return $authUser->can('CancelAny:Installment')
            || $authUser->can('Cancel:Installment')
            || $authUser->can('Update:Installment')
            || $authUser->hasRole('super_admin');
    }

    public function chargeWhatsApp(AuthUser $authUser, Installment $installment): bool
    {
        return ($authUser->can('ChargeWhatsApp:Installment') || $authUser->hasRole('super_admin'))
            && in_array($installment->status, ['pending', 'partial'], true);
    }

    public function applyPunctualityDiscount(AuthUser $authUser, Installment $installment): bool
    {
        return $authUser->can('ApplyPunctualityDiscount:Installment') || $authUser->hasRole('super_admin');
    }

    public function waiveFine(AuthUser $authUser, Installment $installment): bool
    {
        return $authUser->can('WaiveFine:Installment') || $authUser->hasRole('super_admin');
    }

    public function refund(AuthUser $authUser, Installment $installment): bool
    {
        return ($authUser->can('Refund:Installment') || $authUser->hasRole('super_admin'))
            && $installment->status === 'paid';
    }
}