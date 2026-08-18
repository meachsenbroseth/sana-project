<?php

namespace App\Services;

use App\Models\Province;
use App\Models\ShippingMethod;

class ShippingFeeService
{
    /**
     * Return the province-specific shipping fee for a given shipping method,
     * or null if that method does not have a fee configured for the province.
     */
    public function feeFor(ShippingMethod $shippingMethod, Province $province): ?float
    {
        $pivot = $shippingMethod->provinces()
            ->wherePivot('province_id', $province->id)
            ->first();

        if (! $pivot) {
            return null;
        }

        return (float) $pivot->pivot->fee;
    }
}
