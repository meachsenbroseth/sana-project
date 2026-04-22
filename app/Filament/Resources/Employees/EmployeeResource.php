<?php

namespace App\Filament\Resources\Employees;

use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\Resources\Employees\Tables\EmployeesTable;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use UnitEnum;

class EmployeeResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('nav.employee');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.employees');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('nav.system_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.employees');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('roles')
            ->whereHas('roles');
    }

    public static function getPermissionKey(string $action): string
    {
        $permissions = FilamentShield::getResourcePolicyActionsWithPermissions(static::class) ?? [];

        if (isset($permissions[$action])) {
            return $permissions[$action];
        }

        $case = (string) config('filament-shield.permissions.case', 'pascal');
        $separator = (string) config('filament-shield.permissions.separator', ':');
        $subject = class_basename(static::getModel());

        return static::formatPermissionPart($action, $case).$separator.static::formatPermissionPart($subject, $case);
    }

    public static function canManageRoles(?User $actor = null): bool
    {
        $actor ??= auth()->user();

        if (! $actor instanceof User) {
            return false;
        }

        return $actor->can(static::getPermissionKey('create'))
            || $actor->can(static::getPermissionKey('update'));
    }

    /**
     * @param  array<int|string, int|string>  $roleIds
     * @return array<int, int>
     */
    public static function sanitizeAssignableRoleIds(array $roleIds, ?User $actor = null): array
    {
        $normalizedRoleIds = collect($roleIds)
            ->filter(fn (mixed $roleId): bool => filled($roleId))
            ->map(fn (mixed $roleId): int => (int) $roleId)
            ->unique()
            ->values();

        if ($normalizedRoleIds->isEmpty()) {
            return [];
        }

        $actor ??= auth()->user();
        $query = Role::query()->whereIn('id', $normalizedRoleIds);

        if (! $actor?->hasRole(config('filament-shield.super_admin.name', 'super_admin'))) {
            $query->where('name', '!=', config('filament-shield.super_admin.name', 'super_admin'));
        }

        return $query
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    protected static function formatPermissionPart(string $value, string $case): string
    {
        return match ($case) {
            'kebab' => Str::of($value)->kebab()->toString(),
            'pascal' => Str::of($value)->studly()->toString(),
            'upper_snake' => Str::of($value)->snake()->upper()->toString(),
            'lower_snake' => Str::of($value)->snake()->lower()->toString(),
            'camel' => Str::of($value)->camel()->toString(),
            default => Str::of($value)->snake()->toString(),
        };
    }
}
