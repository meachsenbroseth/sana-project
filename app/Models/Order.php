<?php

namespace App\Models;

use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[ObservedBy(OrderObserver::class)]
class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'subtotal',
        'discount_amount',
        'shipping_cost',
        'total',
        'shipping_method',
        'shipping_full_name',
        'shipping_phone',
        'shipping_address_line_1',
        'shipping_address_line_2',
        'shipping_city',
        'shipping_state',
        'shipping_country',
        'payment_method',
        'payment_status',
        'transaction_id',
        'status',
        'tracking_number',
        'customer_notes',
        'admin_notes',
        'stock_deducted_at',
    ];

    protected function casts(): array
    {
        return [
            'stock_deducted_at' => 'datetime',
        ];
    }

    // ==========================================
    // SCOPES (Must be prefixed with 'scope')
    // ==========================================

    public function scopeOfStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    public function scopePaymentStatus(Builder $query, string $status): void
    {
        $query->where('payment_status', $status);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    public function scopeProcessing(Builder $query): void
    {
        $query->where('status', 'processing');
    }

    public function scopeShipped(Builder $query): void
    {
        $query->where('status', 'shipped');
    }

    public function scopeDelivered(Builder $query): void
    {
        $query->where('status', 'delivered');
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    // ==========================================
    // HELPER METHODS & ACCESSORS
    // ==========================================

    /**
     * Modern Laravel 9+ Accessor for the full shipping address.
     * Can be accessed via $order->shipping_address
     */
    protected function shippingAddress(): Attribute
    {
        return Attribute::make(
            get: fn () => implode(', ', array_filter([
                $this->shipping_address_line_1,
                $this->shipping_address_line_2,
                $this->shipping_city,
                $this->shipping_state,
                $this->shipping_country,
            ]))
        );
    }

    public function updateStatus(string $newStatus, ?string $notes = null, ?int $userId = null): void
    {
        $this->update(['status' => $newStatus]);

        $this->statusHistories()->create([
            'status' => $newStatus,
            'notes' => $notes,
            'user_id' => $userId,
        ]);
    }

    // ==========================================
    // BOOT METHODS
    // ==========================================

    protected static function boot(): void
    {
        parent::boot();

        // FIX: Must be `creating` (before save), not `created` (after save)
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-'.strtoupper(Str::random(10));
            }
        });
    }
}
