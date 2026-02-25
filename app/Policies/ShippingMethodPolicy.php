<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ShippingMethod;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShippingMethodPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ShippingMethod');
    }

    public function view(AuthUser $authUser, ShippingMethod $shippingMethod): bool
    {
        return $authUser->can('View:ShippingMethod');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ShippingMethod');
    }

    public function update(AuthUser $authUser, ShippingMethod $shippingMethod): bool
    {
        return $authUser->can('Update:ShippingMethod');
    }

    public function delete(AuthUser $authUser, ShippingMethod $shippingMethod): bool
    {
        return $authUser->can('Delete:ShippingMethod');
    }

    public function restore(AuthUser $authUser, ShippingMethod $shippingMethod): bool
    {
        return $authUser->can('Restore:ShippingMethod');
    }

    public function forceDelete(AuthUser $authUser, ShippingMethod $shippingMethod): bool
    {
        return $authUser->can('ForceDelete:ShippingMethod');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ShippingMethod');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ShippingMethod');
    }

    public function replicate(AuthUser $authUser, ShippingMethod $shippingMethod): bool
    {
        return $authUser->can('Replicate:ShippingMethod');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ShippingMethod');
    }

}