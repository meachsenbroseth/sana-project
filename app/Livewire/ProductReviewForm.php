<?php

namespace App\Livewire;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Livewire\Component;

class ProductReviewForm extends Component
{
    public Product $product;

    public int $rating = 5;

    public string $title = '';

    public string $comment = '';

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    public function submitReview(): void
    {
        $customerId = auth('customer')->id();

        if (! $customerId) {
            $this->addError('review', 'Please sign in as a customer to submit a review.');

            return;
        }

        if ($this->product->reviews()->where('customer_id', $customerId)->exists()) {
            $this->addError('review', 'You have already reviewed this product.');

            return;
        }

        $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $orderItem = OrderItem::query()
            ->whereHas('order', function (Builder $query) use ($customerId): void {
                $query->where('customer_id', $customerId)
                    ->where('status', 'delivered');
            })
            ->where('product_id', $this->product->id)
            ->first();

        if (! $orderItem) {
            $this->addError('review', 'You can only review products from delivered orders.');

            return;
        }

        try {
            Review::query()->create([
                'product_id' => $this->product->id,
                'customer_id' => $customerId,
                'order_id' => $orderItem?->order_id,
                'rating' => $this->rating,
                'title' => filled($this->title) ? $this->title : null,
                'comment' => filled($this->comment) ? $this->comment : null,
                'is_verified_purchase' => $orderItem !== null,
                'is_approved' => false,
            ]);
        } catch (QueryException $exception) {
            $this->addError('review', 'You have already reviewed this product.');

            return;
        }

        session()->flash('review_success', 'Review submitted and awaiting approval');

        $this->reset(['rating', 'title', 'comment']);
        $this->rating = 5;

        $this->dispatch('review-submitted');
    }

    public function render(): View
    {
        return view('livewire.product-review-form');
    }
}
