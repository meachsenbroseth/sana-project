<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    public function saving(Product $product): void
    {
        if ($product->isDirty('stock_quantity') || $product->isDirty('stock_status') || ! $product->exists) {
            $product->syncStockStatusFromQuantity();
        }
    }
}
