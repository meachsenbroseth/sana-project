<?php

namespace App\Actions;

use App\Jobs\LogInteractionJob;
use App\Models\Customer;
use App\Models\Product;

class LogInteraction
{
    public function handle(?Customer $customer, ?string $sessionId, Product $product, string $eventType): void
    {
        LogInteractionJob::dispatch(
            $customer?->id,
            $sessionId,
            $product->id,
            $eventType,
        );
    }
}
