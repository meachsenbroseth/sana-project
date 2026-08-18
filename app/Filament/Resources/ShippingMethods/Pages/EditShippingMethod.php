<?php

namespace App\Filament\Resources\ShippingMethods\Pages;

use App\Filament\Resources\ShippingMethods\ShippingMethodResource;
use App\Models\Province;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class EditShippingMethod extends EditRecord
{
    protected static string $resource = ShippingMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Hydrate the province_fees repeater from the existing pivot rows.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['province_fees'] = $this->getRecord()
            ->provinces()
            ->get()
            ->map(fn (Province $province): array => [
                'province_id' => $province->id,
                'fee' => (float) $province->pivot->fee,
            ])
            ->toArray();

        return $data;
    }

    /**
     * Strip province_fees from the data before ShippingMethod::update() is called.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['province_fees']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncProvinceFees();
    }

    /**
     * Validate and sync the province_fees repeater rows to the pivot table.
     *
     * @throws ValidationException
     */
    private function syncProvinceFees(): void
    {
        $rows = collect($this->data['province_fees'] ?? [])
            ->filter(fn (array $row): bool => filled($row['province_id'] ?? null));

        $this->assertNoDuplicateProvinces($rows);

        $syncData = $rows->mapWithKeys(fn (array $row): array => [
            (int) $row['province_id'] => ['fee' => (float) $row['fee']],
        ]);

        $this->getRecord()->provinces()->sync($syncData);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     *
     * @throws ValidationException
     */
    private function assertNoDuplicateProvinces(Collection $rows): void
    {
        $provinceIds = $rows->pluck('province_id');

        if ($provinceIds->count() === $provinceIds->unique()->count()) {
            return;
        }

        $duplicates = $provinceIds
            ->countBy()
            ->filter(fn (int $count): bool => $count > 1)
            ->keys()
            ->map(fn (mixed $id): string => Province::find($id)?->name_en ?? (string) $id)
            ->join(', ');

        throw ValidationException::withMessages([
            'province_fees' => __('shipping_method.province_fees.duplicate_error', ['provinces' => $duplicates]),
        ]);
    }
}
