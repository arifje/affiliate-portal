@php
    $applicationInfo = $this->getApplicationInfo();
    $requirements = $this->getRequirements();
    $phpExtensions = $this->getPhpExtensions();
    $storageInfo = $this->getStorageInfo();
    $serviceInfo = $this->getServiceInfo();
@endphp

<x-filament-panels::page>
    <div class="mt-4 space-y-6">
        <x-filament::section
            heading="Application info"
            description="Runtime, framework and service details for this installation."
        >
            <dl class="grid gap-x-8 gap-y-4 lg:grid-cols-2">
                @foreach ($applicationInfo as $item)
                    <div class="grid gap-2 sm:grid-cols-[240px_1fr]">
                        <dt class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $item['label'] }}</dt>
                        <dd class="break-words text-sm text-gray-950 dark:text-white">{{ $item['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-filament::section>

        <x-filament::section
            heading="Requirements"
            description="Checks for the current architecture target: Laravel 12, PHP 8.3+, MariaDB/MySQL and Redis."
        >
            <div class="grid gap-3 lg:grid-cols-2">
                @foreach ($requirements as $requirement)
                    <div class="flex gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                        <div class="pt-0.5">
                            <x-filament::icon
                                :icon="$this->statusIcon($requirement['status'])"
                                @class([
                                    'h-5 w-5',
                                    'text-success-600 dark:text-success-400' => $requirement['status'] === 'pass',
                                    'text-warning-600 dark:text-warning-400' => $requirement['status'] === 'warning',
                                    'text-danger-600 dark:text-danger-400' => $requirement['status'] === 'fail',
                                ])
                            />
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $requirement['label'] }}</p>
                                <x-filament::badge :color="$this->statusColor($requirement['status'])">
                                    {{ $requirement['value'] }}
                                </x-filament::badge>
                            </div>

                            @if ($requirement['description'])
                                <p class="mt-1 break-words text-sm text-gray-500 dark:text-gray-400">{{ $requirement['description'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-filament::section
                heading="Services"
                description="Configured backend services and their active drivers."
            >
                <dl class="space-y-4">
                    @foreach ($serviceInfo as $item)
                        <div class="grid gap-2 sm:grid-cols-[180px_1fr]">
                            <dt class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $item['label'] }}</dt>
                            <dd class="break-words text-sm text-gray-950 dark:text-white">{{ $item['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-filament::section>

            <x-filament::section
                heading="Storage"
                description="Important filesystem paths used by Laravel and public uploads."
            >
                <dl class="space-y-4">
                    @foreach ($storageInfo as $item)
                        <div>
                            <dt class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $item['label'] }}</dt>
                            <dd class="mt-1 break-all font-mono text-xs text-gray-950 dark:text-white">{{ $item['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-filament::section>
        </div>

        <x-filament::section
            heading="PHP extensions"
            description="Loaded extension status for Laravel, database access, Redis and feed processing."
        >
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($phpExtensions as $extension)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-900">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $extension['label'] }}</p>
                            @if ($extension['description'])
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $extension['description'] }}</p>
                            @endif
                        </div>
                        <x-filament::badge :color="$this->statusColor($extension['status'])">
                            {{ $extension['value'] }}
                        </x-filament::badge>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
