<?php

use App\Ai\Agents\ProductCoach;
use App\Models\Product;
use Livewire\Component;

new class extends Component {
    public array $botMessages = [];
    public string $question = '';
    public array $products = [];

    public function mount(): void
    {
        $this->loadProducts();
        $this->loadMessages();
    }

    public function loadProducts(): void
    {
        $this->products = Product::query()
            ->with(['category', 'brand'])
            ->where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->limit(60)
            ->get()
            ->map(function (Product $product): array {
                return [
                    'name' => $product->name,
                    'category' => $product->category?->name,
                    'brand' => $product->brand?->name,
                    'price' => (float) $product->price,
                    'stock_status' => (string) $product->stock_status,
                ];
            })
            ->all();
    }

    public function loadMessages(): void
    {
        $this->botMessages = session()->get('product_coach_messages', [
            [
                'role' => 'assistant',
                'content' => 'Hi! I can help you compare and choose products from our catalog. What are you looking for?',
            ],
        ]);
    }

    public function clearChat(): void
    {
        session()->forget('product_coach_messages');
        $this->loadMessages();
    }

    public function askProductCoach(): void
    {
        $validated = $this->validate([
            'question' => ['required', 'string', 'max:500'],
        ], [
            'question.required' => 'Please enter a question first.',
            'question.max' => 'Please keep your question under 500 characters.',
        ]);

        $question = trim($validated['question']);

        if ($question === '') {
            return;
        }

        $history = collect($this->botMessages)
            ->filter(fn (array $message): bool => isset($message['role'], $message['content']))
            ->values()
            ->all();

        $this->botMessages[] = [
            'role' => 'user',
            'content' => $question,
        ];

        try {
            $agent = new ProductCoach(
                conversation: $history,
                products: $this->products
            );

            $result = $agent->prompt($question);
            $answer = trim((string) ($result['value'] ?? $result->text ?? ''));

            if ($answer === '') {
                $answer = 'I could not generate a response right now. Please try again.';
            }
        } catch (\Throwable $exception) {
            report($exception);

            $answer = 'I ran into an issue while answering. Please try again in a moment.';
        }

        $this->botMessages[] = [
            'role' => 'assistant',
            'content' => $answer,
        ];

        session()->put('product_coach_messages', $this->botMessages);
        $this->question = '';
    }
};
?>

<div class="py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <nav class="mb-6 text-sm">
            <ol class="flex items-center gap-2">
                <li><a href="{{ route('home') }}" wire:navigate class="text-gray-500 hover:text-blue-600">Home</a></li>
                <li class="text-gray-400">/</li>
                <li class="text-gray-900 font-medium">Product Coach</li>
            </ol>
        </nav>

        <div class="mb-6 rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-indigo-50 p-6">
            <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">Product Coach</h1>
            <p class="mt-2 text-sm text-gray-700 sm:text-base">
                Ask about laptops, accessories, and product comparisons across our catalog.
                Catalog context loaded: <span class="font-semibold">{{ count($products) }} products</span>.
            </p>
            <p class="mt-2 text-sm text-gray-600">
                Try: "Best gaming laptop under $1500" or "Compare two options for video editing".
            </p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">Chat</h2>
                <button wire:click="clearChat" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                    Clear Chat
                </button>
            </div>

            <div class="max-h-[30rem] space-y-3 overflow-y-auto rounded-lg bg-gray-50 p-4">
                @foreach ($botMessages as $message)
                    <div class="{{ $message['role'] === 'user' ? 'text-right' : 'text-left' }}">
                        <div
                            class="{{ $message['role'] === 'user' ? 'bg-blue-600 text-white' : 'bg-white text-gray-900' }} inline-block max-w-2xl rounded-2xl px-4 py-2 text-sm shadow-sm">
                            {{ $message['content'] }}
                        </div>
                    </div>
                @endforeach
            </div>

            <form wire:submit="askProductCoach" class="mt-4 space-y-3">
                <div>
                    <label for="product-coach-question" class="mb-1 block text-sm font-medium text-gray-700">
                        Ask Product Coach
                    </label>
                    <textarea id="product-coach-question" wire:model="question" rows="3"
                        placeholder="Example: Which option has better value for gaming?"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    @error('question')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        wire:loading.attr="disabled" wire:target="askProductCoach">
                        Ask Coach
                    </button>
                    <p wire:loading wire:target="askProductCoach" class="text-sm text-gray-500">
                        Thinking...
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
