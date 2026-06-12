<?php

use App\Ai\Agents\AiBusinessAssistantAgent;
use App\Filament\Pages\AiBusinessAssistant;
use App\Models\User;
use App\Services\Ai\BusinessIntelligenceContextService;
use Livewire\Livewire;

it('renders the ai business assistant page for authenticated admin users', function (): void {
    $user = User::factory()->create([
        'email' => 'ai-assistant-admin@example.com',
    ]);

    $this->actingAs($user)
        ->get(AiBusinessAssistant::getUrl())
        ->assertOk()
        ->assertSee(__('analytics.ai_assistant.title'));
});

it('localizes the ai assistant navigation label', function (): void {
    app()->setLocale('en');

    expect(AiBusinessAssistant::getNavigationLabel())->toBe('AI Assistant')
        ->and(AiBusinessAssistant::getNavigationGroup())->toBe('Analytics');

    app()->setLocale('km');

    expect(AiBusinessAssistant::getNavigationLabel())->toBe('ជំនួយការ AI')
        ->and(AiBusinessAssistant::getNavigationGroup())->toBe(__('nav.analytics'));
});

it('builds cached business intelligence context for the assistant', function (): void {
    $snapshot = app(BusinessIntelligenceContextService::class)->snapshot();

    expect($snapshot)
        ->toHaveKeys(['sales', 'orders', 'products', 'customers', 'inventory', 'insights', 'recommended_actions'])
        ->and($snapshot['sales'])->toHaveKeys([
            'total_revenue',
            'revenue_today',
            'revenue_this_month',
            'revenue_this_year',
            'revenue_growth_percent',
            'next_month_revenue_estimate',
        ])
        ->and($snapshot['orders'])->toHaveKeys([
            'total_orders',
            'pending_orders',
            'processing_orders',
            'shipped_orders',
            'delivered_orders',
            'cancelled_orders',
        ]);
});

it('sends questions through the ai business assistant agent', function (): void {
    AiBusinessAssistantAgent::fake(['ចម្លើយសាកល្បង']);

    $user = User::factory()->create([
        'email' => 'ai-assistant-chat@example.com',
    ]);

    $this->actingAs($user);

    Livewire::test(AiBusinessAssistant::class)
        ->set('question', 'តើផលិតផលណាលក់ដាច់ជាងគេ?')
        ->call('ask')
        ->assertSee('ចម្លើយសាកល្បង');

    AiBusinessAssistantAgent::assertPrompted('តើផលិតផលណាលក់ដាច់ជាងគេ?');
});
