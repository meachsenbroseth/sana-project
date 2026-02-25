<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (): bool => auth()->id() === $this->getRecord()->getKey()),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! EmployeeResource::canManageRoles()) {
            unset($data['roles']);

            return $data;
        }

        $data['roles'] = EmployeeResource::sanitizeAssignableRoleIds((array) ($data['roles'] ?? []));

        return $data;
    }
}
