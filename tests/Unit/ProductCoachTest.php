<?php

use App\Ai\Agents\ProductCoach;
use Laravel\Ai\Messages\Message;
use Tests\TestCase;

uses(TestCase::class);

it('uses the gemini provider and configured gemini model', function (): void {
    config()->set('ai.gemini_model', 'gemini-2.5-flash');

    $agent = new ProductCoach;

    expect($agent->provider())->toBe('gemini')
        ->and($agent->model())->toBe('gemini-2.5-flash');
});

it('contains product coaching instructions for overall product support', function (): void {
    $agent = new ProductCoach;

    expect((string) $agent->instructions())
        ->toContain('product coach for the customer storefront')
        ->toContain('compare options')
        ->toContain('Do not invent product names, prices, discounts, stock, or warranties.');
});

it('includes product catalog context in the instructions', function (): void {
    $agent = new ProductCoach(products: [
        [
            'name' => 'Lenovo Legion 5',
            'category' => 'Laptop',
            'brand' => 'Lenovo',
            'price' => 1299.99,
            'stock_status' => 'in_stock',
        ],
    ]);

    expect((string) $agent->instructions())
        ->toContain('Available product catalog context:')
        ->toContain('Lenovo Legion 5')
        ->toContain('category: Laptop')
        ->toContain('brand: Lenovo')
        ->toContain('stock: in_stock');
});

it('includes cart item context in the instructions', function (): void {
    $agent = new ProductCoach(cartItems: [
        ['name' => 'Razer Blade 16', 'price' => 2499.99, 'quantity' => 1],
    ]);

    expect((string) $agent->instructions())
        ->toContain('Current cart context:')
        ->toContain('Razer Blade 16')
        ->toContain('unit price: $2,499.99');
});

it('maps only user and assistant conversation messages', function (): void {
    $agent = new ProductCoach(conversation: [
        ['role' => 'user', 'content' => 'Is this good for editing?'],
        ['role' => 'assistant', 'content' => 'Yes, because of the GPU.'],
        ['role' => 'tool_result', 'content' => 'ignored'],
    ]);

    $messages = $agent->messages();

    expect($messages)->toHaveCount(2)
        ->and($messages[0])->toBeInstanceOf(Message::class)
        ->and($messages[0]->role->value)->toBe('user')
        ->and($messages[1]->role->value)->toBe('assistant');
});
