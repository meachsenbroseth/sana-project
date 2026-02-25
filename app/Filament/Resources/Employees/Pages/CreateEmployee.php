<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! EmployeeResource::canManageRoles()) {
            unset($data['roles']);

            return $data;
        }

        $data['roles'] = EmployeeResource::sanitizeAssignableRoleIds((array) ($data['roles'] ?? []));

        return $data;
    }
}
