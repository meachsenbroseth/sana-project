<?php

namespace App\Jobs;

use App\Models\UserInteraction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogInteractionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $customerId,
        public ?string $sessionId,
        public int $productId,
        public string $eventType,
    ) {}

    public function handle(): void
    {
        $weight = (float) config("recommendations.event_weights.{$this->eventType}", 1);

        UserInteraction::query()->create([
            'customer_id' => $this->customerId,
            'session_id' => $this->sessionId,
            'product_id' => $this->productId,
            'event_type' => $this->eventType,
            'weight' => $weight,
        ]);
    }
}
