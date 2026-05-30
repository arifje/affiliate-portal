@php
    $files = $this->getLogFiles();
    $meta = $this->getLogMeta();
    $entries = $this->getLogEntries();
    $debugInfo = $this->getDebugInfo();
@endphp

<x-filament-panels::page>
    <div class="mt-4 space-y-6">
        <x-filament::section
            heading="Debug info"
            description="Runtime information and the currently selected Laravel log file."
        >
            <dl class="grid gap-4 md:grid-cols-4">
                @foreach ($debugInfo as $label => $value)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $value }}</dd>
                    </div>
                @endforeach

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Selected file</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $meta['name'] ?? 'No log file' }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">File size</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $meta['formatted_size'] }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last updated</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $meta['updated_at'] ?? 'Never' }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Showing</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ count($entries) }} latest matching entries</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section
            heading="Filters"
            description="Filter the loaded log entries by file, severity, date or text."
        >
            <div class="grid gap-4 lg:grid-cols-5">
                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Log file</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="logFile">
                            @forelse ($files as $file)
                                <option value="{{ $file['name'] }}">
                                    {{ $file['name'] }} ({{ $file['formatted_size'] }})
                                </option>
                            @empty
                                <option value="">No log files found</option>
                            @endforelse
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Severity</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="level">
                            <option value="">All levels</option>
                            @foreach ($this->getLevelOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Date</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="date" />
                    </x-filament::input.wrapper>
                </label>

                <label class="space-y-2 lg:col-span-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Search</span>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="search"
                            placeholder="Search message or stack trace"
                            wire:model.live.debounce.400ms="search"
                        />
                    </x-filament::input.wrapper>
                </label>
            </div>

            <div class="mt-4">
                <x-filament::button color="gray" wire:click="resetFilters">
                    Reset filters
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section
            heading="Laravel logs"
            description="Newest matching entries are shown first. Very long stack traces are trimmed for readability."
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
                                    Stack trace / context ({{ $entry['context_line_count'] }} lines)
                                </summary>

                                <pre class="mt-3 max-h-96 overflow-auto rounded-lg bg-gray-950 p-4 text-xs leading-5 text-gray-100"><code>{{ $entry['context'] }}@if ($entry['truncated'])

... trimmed ...
@endif</code></pre>
                            </details>
                        @endif
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center dark:border-white/20">
                        <p class="text-sm font-medium text-gray-950 dark:text-white">No matching log entries found.</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try another date, severity level or search term.</p>
                    </div>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
