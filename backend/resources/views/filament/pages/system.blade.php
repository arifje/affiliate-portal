@php
    $serverInfo = $this->getServerInfo();
    $applicationInfo = $this->getApplicationInfo();
    $requirements = $this->getRequirements();
    $phpExtensions = $this->getPhpExtensions();
    $storageInfo = $this->getStorageInfo();
    $serviceInfo = $this->getServiceInfo();
@endphp

<x-filament-panels::page>
    <div class="admin-utility-page">
        <x-filament::section
            :heading="__('admin.pages.system.server_info')"
            :description="__('admin.pages.system.server_info_description')"
        >
            <div class="server-info-grid">
                @foreach ($serverInfo as $item)
                    <div class="server-info-card">
                        <p class="server-info-label">{{ $item['label'] }}</p>
                        <p class="server-info-value">{{ $item['value'] }}</p>

                        @if ($item['description'])
                            <p class="server-info-description">{{ $item['description'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section
            :heading="__('admin.pages.system.application_info')"
            :description="__('admin.pages.system.application_info_description')"
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
            :heading="__('admin.pages.system.requirements')"
            :description="__('admin.pages.system.requirements_description')"
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
                :heading="__('admin.pages.system.services')"
                :description="__('admin.pages.system.services_description')"
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
                :heading="__('admin.pages.system.storage')"
                :description="__('admin.pages.system.storage_description')"
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
            :heading="__('admin.pages.system.php_extensions')"
            :description="__('admin.pages.system.php_extensions_description')"
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

    <style>
        .admin-utility-page {
            display: grid;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .server-info-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        }

        .server-info-card {
            background: rgb(255 255 255);
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .server-info-label {
            color: rgb(75 85 99);
            font-size: 0.875rem;
            font-weight: 600;
            margin: 0 0 0.5rem;
        }

        .server-info-value {
            color: rgb(17 24 39);
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.3;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .server-info-description {
            color: rgb(107 114 128);
            font-size: 0.8125rem;
            line-height: 1.45;
            margin: 0.5rem 0 0;
            overflow-wrap: anywhere;
        }

        .dark .server-info-card {
            background: rgb(17 24 39);
            border-color: rgb(255 255 255 / 10%);
        }

        .dark .server-info-label {
            color: rgb(209 213 219);
        }

        .dark .server-info-value {
            color: rgb(255 255 255);
        }

        .dark .server-info-description {
            color: rgb(156 163 175);
        }
    </style>
</x-filament-panels::page>
