@php
    $files = $this->getLogFiles();
    $meta = $this->getLogMeta();
    $entries = $this->getLogEntries();
    $debugInfo = $this->getDebugInfo();
@endphp

<x-filament-panels::page>
    <div class="admin-utility-page">
        <x-filament::section
            :heading="__('admin.pages.logs.debug_info')"
            :description="__('admin.pages.logs.debug_description')"
        >
            <dl class="grid gap-4 md:grid-cols-4">
                @foreach ($debugInfo as $label => $value)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $value }}</dd>
                    </div>
                @endforeach

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.pages.logs.selected_file') }}</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $meta['name'] ?? __('admin.pages.logs.no_log_file') }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.pages.logs.file_size') }}</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $meta['formatted_size'] }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.pages.logs.last_updated') }}</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $meta['updated_at'] ?? __('admin.placeholders.never') }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.pages.logs.showing') }}</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ __('admin.pages.logs.latest_matching_entries', ['count' => count($entries)]) }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section
            :heading="__('admin.pages.logs.filters')"
            :description="__('admin.pages.logs.filters_description')"
        >
            <div class="grid gap-4 lg:grid-cols-5">
                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('admin.pages.logs.log_file') }}</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="logFile">
                            @forelse ($files as $file)
                                <option value="{{ $file['name'] }}">
                                    {{ $file['name'] }} ({{ $file['formatted_size'] }})
                                </option>
                            @empty
                                <option value="">{{ __('admin.pages.logs.no_log_files_found') }}</option>
                            @endforelse
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('admin.pages.logs.severity') }}</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="level">
                            <option value="">{{ __('admin.pages.logs.all_levels') }}</option>
                            @foreach ($this->getLevelOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('admin.pages.logs.date') }}</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="date" />
                    </x-filament::input.wrapper>
                </label>

                <label class="space-y-2 lg:col-span-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('admin.pages.logs.search') }}</span>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="search"
                            :placeholder="__('admin.placeholders.search_logs')"
                            wire:model.live.debounce.400ms="search"
                        />
                    </x-filament::input.wrapper>
                </label>
            </div>

            <div class="mt-4">
                <x-filament::button color="gray" wire:click="resetFilters">
                    {{ __('admin.actions.reset_filters') }}
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section
            :heading="__('admin.pages.logs.laravel_logs')"
            :description="__('admin.pages.logs.laravel_logs_description')"
        >
            <div class="space-y-4">
                @forelse ($entries as $entry)
                    <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-filament::badge :color="$this->getLevelColor($entry['level'])">
                                {{ $entry['level'] }}
                            </x-filament::badge>

                            <span class="text-sm font-medium text-gray-950 dark:text-white">
                                {{ $entry['timestamp'] }}
                            </span>

                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $entry['environment'] }}
                            </span>
                        </div>

                        <p class="mt-3 break-words text-sm text-gray-800 dark:text-gray-100">
                            {{ $entry['message'] }}
                        </p>

                        @if ($entry['context'] !== '')
                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm font-medium text-primary-600 dark:text-primary-400">
                                    {{ __('admin.pages.logs.stack_trace_context', ['count' => $entry['context_line_count']]) }}
                                </summary>

                                <pre class="mt-3 max-h-96 overflow-auto rounded-lg bg-gray-950 p-4 text-xs leading-5 text-gray-100"><code>{{ $entry['context'] }}@if ($entry['truncated'])

{{ __('admin.pages.logs.trimmed') }}
@endif</code></pre>
                            </details>
                        @endif
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center dark:border-white/20">
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ __('admin.pages.logs.no_matching_entries') }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.pages.logs.try_another_filter') }}</p>
                    </div>
                @endforelse
            </div>
        </x-filament::section>
    </div>

    <style>
        .admin-utility-page {
            display: grid;
            gap: 1.5rem;
            margin-top: 1rem;
        }
    </style>
</x-filament-panels::page>
