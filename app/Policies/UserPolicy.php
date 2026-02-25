<?php

namespace App\Policies;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(EmployeeResource::getPermissionKey('viewAny'));
    }

    public function view(User $user, User $record): bool
    {
        return $user->can(EmployeeResource::getPermissionKey('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(EmployeeResource::getPermissionKey('create'));
    }

    public function update(User $user, User $record): bool
    {
        return $user->can(EmployeeResource::getPermissionKey('update'));
    }

    public function delete(User $user, User $record): bool
    {
        if ($user->is($record)) {
            return false;
        }

        return $user->can(EmployeeResource::getPermissionKey('delete'));
    }

    public function deleteAny(User $user): bool
    {
        return $user->can(EmployeeResource::getPermissionKey('delete'));
    }

    public function forceDelete(User $user, User $record): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, User $record): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, User $record): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
