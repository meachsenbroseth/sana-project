<?php

namespace App\Ai\Tools;

use App\Services\Ai\BusinessIntelligenceContextService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class BusinessIntelligenceContextTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Fetch current store analytics, inventory, customer, sales, order, and predictive business intelligence context. Use this before answering business performance questions.';
    }

    public function handle(Request $request): Stringable|string
    {
        return app(BusinessIntelligenceContextService::class)->contextForPrompt();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'focus' => $schema->string()
                ->description('Optional business area to focus on: sales, orders, products, customers, inventory, predictions, or recommendations.'),
        ];
    }
}
