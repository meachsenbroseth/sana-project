<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class ProductCoach implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * @param  array<int, array{role: string, content: string}>  $conversation
     * @param  array<int, array{name?: string, category?: string, brand?: string, price?: float|int|string, stock_status?: string}>  $products
     * @param  array<int, array{name?: string, price?: float|int|string, quantity?: int}>  $cartItems
     */
    public function __construct(
        public array $conversation = [],
        public array $products = [],
        public array $cartItems = [],
    ) {}

    /**
     * Get the provider that should be used by this agent.
     */
    public function provider(): string
    {
        return 'gemini';
    }

    /**
     * Get the model that should be used by this agent.
     */
    public function model(): string
    {
        return (string) config('ai.gemini_model', 'gemini-2.5-flash');
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $productCatalogContext = $this->productCatalogContext();
        $cartContext = $this->cartContext();

        return <<<'PROMPT'
You are a product coach for the customer storefront.

Help customers understand products, compare options, and make buying decisions using the available catalog context.
Be concise, accurate, and practical.

Rules:
- Do not invent product names, prices, discounts, stock, or warranties.
- If information is missing, say what is unknown and ask a clarifying question.
- Focus only on product and purchase-help topics.
PROMPT."\n\nAvailable product catalog context:\n".$productCatalogContext."\n\nCurrent cart context:\n".$cartContext;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     */
    public function messages(): iterable
    {
        return collect($this->conversation)
            ->filter(function (array $message): bool {
                return isset($message['role'], $message['content'])
                    && in_array($message['role'], ['user', 'assistant'], true);
            })
            ->map(fn (array $message): Message => new Message($message['role'], (string) $message['content']))
            ->values()
            ->all();
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'value' => $schema->string()->required(),
        ];
    }

    protected function cartContext(): string
    {
        if ($this->cartItems === []) {
            return 'No current cart context provided.';
        }

        /** @var Collection<int, string> $lines */
        $lines = collect($this->cartItems)->map(function (array $item): string {
            $name = (string) ($item['name'] ?? 'Unknown product');
            $quantity = (int) ($item['quantity'] ?? 1);
            $price = number_format((float) ($item['price'] ?? 0), 2);

            return "- {$name} | qty: {$quantity} | unit price: \${$price}";
        });

        return $lines->implode("\n");
    }

    protected function productCatalogContext(): string
    {
        if ($this->products === []) {
            return 'No product catalog context provided.';
        }

        /** @var Collection<int, string> $lines */
        $lines = collect($this->products)->map(function (array $product): string {
            $name = (string) ($product['name'] ?? 'Unknown product');
            $category = (string) ($product['category'] ?? 'Uncategorized');
            $brand = (string) ($product['brand'] ?? 'Unknown brand');
            $price = number_format((float) ($product['price'] ?? 0), 2);
            $stockStatus = (string) ($product['stock_status'] ?? 'unknown');

            return "- {$name} | category: {$category} | brand: {$brand} | price: \${$price} | stock: {$stockStatus}";
        });

        return $lines->implode("\n");
    }
}
