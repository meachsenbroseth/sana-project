<?php

namespace App\Filament\Pages;

use App\Ai\Agents\AiBusinessAssistantAgent;
use App\Filament\Widgets\AiAssistantOverview;
use App\Filament\Widgets\AiProductDemandChart;
use App\Services\Ai\BusinessIntelligenceContextService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Streaming\Events\TextDelta;
use Throwable;
use UnitEnum;

class AiBusinessAssistant extends Page
{
    protected string $view = 'filament.pages.ai-business-assistant';

    protected static string $routePath = 'ai-assistant';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 2;

    public string $question = '';

    public ?string $conversationId = null;

    /**
     * @var array<int, array{role: string, content: string}>
     */
    public array $messages = [];

    /**
     * @var array<string, mixed>
     */
    public array $snapshot = [];

    public function mount(BusinessIntelligenceContextService $context): void
    {
        $this->snapshot = $context->snapshot();
        $this->conversationId = session('ai_business_assistant_conversation_id');
        $this->loadConversationMessages();

        if ($this->messages === []) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => __('analytics.ai_assistant.welcome_message'),
            ];
        }
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('nav.analytics');
    }

    public static function getNavigationLabel(): string
    {
        return __('analytics.ai_assistant.navigation_label');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:' . class_basename(static::class)) ?? false;
    }

    public function getTitle(): string
    {
        return __('analytics.ai_assistant.title');
    }

    /**
     * @return array<class-string>
     */
    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         AiAssistantOverview::class,
    //         AiProductDemandChart::class,
    //     ];
    // }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'xl' => 2,
        ];
    }

    public function ask(?string $suggestedQuestion = null): void
    {
        $question = trim((string) ($suggestedQuestion ?: $this->question));

        if ($question === '') {
            return;
        }

        $this->question = '';
        $this->messages[] = [
            'role' => 'user',
            'content' => $question,
        ];
        $this->messages[] = [
            'role' => 'assistant',
            'content' => '',
        ];

        $assistantIndex = array_key_last($this->messages);
        $answer = '';

        try {
            $agent = new AiBusinessAssistantAgent;
            $user = auth()->user();

            if ($this->conversationId !== null && $user !== null) {
                $agent->continue($this->conversationId, as: $user);
            } elseif ($user !== null) {
                $agent->forUser($user);
            }

            $stream = $agent->stream($question);

            foreach ($stream as $event) {
                if (! $event instanceof TextDelta) {
                    continue;
                }

                $answer .= $event->delta;
                $this->messages[$assistantIndex]['content'] = $answer;
                $this->stream(to: 'assistant-response-'.$assistantIndex, content: (string) str($answer)->markdown(), replace: true);
            }

            $stream->then(function ($response) use ($stream): void {
                $this->conversationId = $stream->conversationId;

                if ($this->conversationId !== null) {
                    session(['ai_business_assistant_conversation_id' => $this->conversationId]);
                }
            });

            if ($answer === '') {
                $response = $agent->prompt($question);
                $answer = trim((string) $response->text);
                $this->conversationId = $response->conversationId;
                $this->messages[$assistantIndex]['content'] = $answer;
            }
        } catch (Throwable $exception) {
            report($exception);

            $this->messages[$assistantIndex]['content'] = __('analytics.ai_assistant.error_message');
        }
    }

    public function clearConversation(): void
    {
        session()->forget('ai_business_assistant_conversation_id');
        $this->conversationId = null;
        $this->messages = [
            [
                'role' => 'assistant',
                'content' => __('analytics.ai_assistant.welcome_message'),
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function suggestedQuestions(): array
    {
        return __('analytics.ai_assistant.suggested_questions');
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function headlineMetrics(): array
    {
        return [
            [
                'label' => __('analytics.ai_assistant.metrics.revenue_month'),
                'value' => '$'.number_format((float) data_get($this->snapshot, 'sales.revenue_this_month', 0), 2),
            ],
            [
                'label' => __('analytics.ai_assistant.metrics.revenue_growth'),
                'value' => number_format((float) data_get($this->snapshot, 'sales.revenue_growth_percent', 0), 1).'%',
            ],
            [
                'label' => __('analytics.ai_assistant.metrics.low_stock'),
                'value' => (string) count((array) data_get($this->snapshot, 'products.low_stock', [])),
            ],
            [
                'label' => __('analytics.ai_assistant.metrics.next_month'),
                'value' => '$'.number_format((float) data_get($this->snapshot, 'sales.next_month_revenue_estimate', 0), 2),
            ],
        ];
    }

    protected function loadConversationMessages(): void
    {
        if ($this->conversationId === null) {
            return;
        }

        $this->messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $this->conversationId)
            ->where('agent', AiBusinessAssistantAgent::class)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id')
            ->limit(50)
            ->get(['role', 'content'])
            ->map(fn (object $message): array => [
                'role' => (string) $message->role,
                'content' => (string) $message->content,
            ])
            ->all();
    }
}
