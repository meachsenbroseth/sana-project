<x-filament-panels::page>
    <div
        class="space-y-6"
        x-data="{
            copy(text) {
                navigator.clipboard?.writeText(text)
            }
        }"
    >
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->headlineMetrics() as $metric)
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-normal text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $metric['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="space-y-4">
                <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ __('analytics.ai_assistant.suggested_heading') }}
                        </h2>
                    </div>

                    <div class="mt-4 space-y-2">
                        @foreach ($this->suggestedQuestions() as $suggestedQuestion)
                            <button
                                type="button"
                                wire:click="ask(@js($suggestedQuestion))"
                                wire:loading.attr="disabled"
                                class="w-full rounded-md border border-gray-200 px-3 py-2 text-left text-sm text-gray-700 transition hover:border-primary-500 hover:text-primary-600 disabled:cursor-wait disabled:opacity-60 dark:border-gray-800 dark:text-gray-200 dark:hover:border-primary-500"
                            >
                                {{ $suggestedQuestion }}
                            </button>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ __('analytics.ai_assistant.insights_heading') }}
                    </h2>

                    <ul class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                        @foreach ((array) data_get($snapshot, 'insights', []) as $insight)
                            <li class="flex gap-2">
                                <span class="mt-2 size-1.5 shrink-0 rounded-full bg-primary-500"></span>
                                <span>{{ $insight }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </aside>

            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                    <div>
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('analytics.ai_assistant.chat_heading') }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('analytics.ai_assistant.chat_subheading') }}
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="clearConversation"
                        class="rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        {{ __('analytics.ai_assistant.clear') }}
                    </button>
                </div>

                <div
                    class="h-[34rem] space-y-5 overflow-y-auto bg-gray-50 p-4 dark:bg-gray-950/40"
                    x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
                    x-effect="$wire.messages.length; $nextTick(() => $el.scrollTop = $el.scrollHeight)"
                >
                    @foreach ($messages as $index => $message)
                        @php
                            $isUser = $message['role'] === 'user';
                            $content = (string) $message['content'];
                        @endphp

                        <div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[min(44rem,92%)] {{ $isUser ? 'rounded-br-sm bg-primary-600 text-white' : 'rounded-bl-sm border border-gray-200 bg-white text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100' }} rounded-2xl px-4 py-3 shadow-sm">
                                @if ($isUser)
                                    <p class="whitespace-pre-wrap text-sm leading-6">{{ $content }}</p>
                                @else
                                    <div class="flex items-start justify-between gap-3">
                                        <div
                                            wire:stream="assistant-response-{{ $index }}"
                                            class="prose prose-sm max-w-none dark:prose-invert prose-headings:font-semibold prose-p:leading-6 prose-ul:my-2 prose-ol:my-2"
                                        >
                                            {!! str($content)->markdown() !!}
                                        </div>

                                        @if ($content !== '')
                                            <button
                                                type="button"
                                                x-on:click="copy(@js($content))"
                                                class="shrink-0 rounded-md p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                                title="{{ __('analytics.ai_assistant.copy') }}"
                                                aria-label="{{ __('analytics.ai_assistant.copy') }}"
                                            >
                                                <x-filament::icon icon="heroicon-o-clipboard" class="size-4" />
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div wire:loading.flex wire:target="ask" class="justify-start">
                        <div class="rounded-2xl rounded-bl-sm border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                            {{ __('analytics.ai_assistant.thinking') }}
                        </div>
                    </div>
                </div>

                <form wire:submit="ask" class="border-t border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <label class="sr-only" for="ai-business-assistant-question">
                            {{ __('analytics.ai_assistant.input_label') }}
                        </label>
                        <textarea
                            id="ai-business-assistant-question"
                            wire:model="question"
                            rows="3"
                            maxlength="1000"
                            placeholder="{{ __('analytics.ai_assistant.placeholder') }}"
                            class="min-h-24 flex-1 resize-none rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm transition focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        ></textarea>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="ask"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-500 disabled:cursor-wait disabled:opacity-60"
                        >
                            <x-filament::icon icon="heroicon-o-paper-airplane" class="size-4" />
                            <span>{{ __('analytics.ai_assistant.send') }}</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-filament-panels::page>
