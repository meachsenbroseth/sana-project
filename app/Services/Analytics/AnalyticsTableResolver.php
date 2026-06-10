<?php

namespace App\Services\Analytics;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class AnalyticsTableResolver
{
    public function product(): string
    {
        return app(Product::class)->getTable();
    }

    public function category(): string
    {
        return app(Category::class)->getTable();
    }

    public function order(): string
    {
        return app(Order::class)->getTable();
    }

    public function orderItem(): string
    {
        return app(OrderItem::class)->getTable();
    }

    public function customer(): string
    {
        return app(Customer::class)->getTable();
    }

    public function hasTable(Model|string $modelOrTable): bool
    {
        return Schema::hasTable($this->resolveTableName($modelOrTable));
    }

    public function hasProducts(): bool
    {
        return $this->hasTable($this->product());
    }

    public function hasOrders(): bool
    {
        return $this->hasTable($this->order());
    }

    public function hasOrderItems(): bool
    {
        return $this->hasTable($this->orderItem());
    }

    public function hasCategories(): bool
    {
        return $this->hasTable($this->category());
    }

    public function hasCustomers(): bool
    {
        return $this->hasTable($this->customer());
    }

    public function resolveTableName(Model|string $modelOrTable): string
    {
        if ($modelOrTable instanceof Model) {
            return $modelOrTable->getTable();
        }

        if (is_subclass_of($modelOrTable, Model::class)) {
            return app($modelOrTable)->getTable();
        }

        return $modelOrTable;
    }

    public function isOperational(): bool
    {
        return $this->hasOrders() && $this->hasOrderItems();
    }

    public function qualifiedProductColumn(string $column): string
    {
        return "{$this->product()}.{$column}";
    }

    public function qualifiedCategoryColumn(string $column): string
    {
        return "{$this->category()}.{$column}";
    }

    public function qualifiedOrderColumn(string $column): string
    {
        return "{$this->order()}.{$column}";
    }

    public function qualifiedOrderItemColumn(string $column): string
    {
        return "{$this->orderItem()}.{$column}";
    }

    public function productUsesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive(Product::class), true);
    }
}
