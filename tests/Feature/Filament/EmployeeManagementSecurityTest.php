<?php

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\User;
use App\Policies\UserPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('prevents an employee from deleting their own account', function (): void {
    $employee = User::factory()->create();
    $deletePermission = EmployeeResource::getPermissionKey('delete');

    Permission::findOrCreate($deletePermission, 'web');
    $employee->givePermissionTo($deletePermission);

    $policy = new UserPolicy;

    expect($policy->delete($employee, $employee))->toBeFalse();
});

it('filters out super admin role assignments for non super admins', function (): void {
    $superAdminRole = Role::findOrCreate(config('filament-shield.super_admin.name', 'super_admin'), 'web');
    $managerRole = Role::findOrCreate('manager', 'web');
    $actor = User::factory()->create();

    $sanitizedRoleIds = EmployeeResource::sanitizeAssignableRoleIds([
        $superAdminRole->id,
        $managerRole->id,
    ], $actor);

    expect($sanitizedRoleIds)->toBe([$managerRole->id]);
});

it('allows super admins to assign the super admin role', function (): void {
    $superAdminRoleName = config('filament-shield.super_admin.name', 'super_admin');
    $superAdminRole = Role::findOrCreate($superAdminRoleName, 'web');
    $managerRole = Role::findOrCreate('manager', 'web');
    $actor = User::factory()->create();
    $actor->assignRole($superAdminRole);

    $sanitizedRoleIds = EmployeeResource::sanitizeAssignableRoleIds([
        $superAdminRole->id,
        $managerRole->id,
    ], $actor);

    expect($sanitizedRoleIds)->toContain($superAdminRole->id);
    expect($sanitizedRoleIds)->toContain($managerRole->id);
});
