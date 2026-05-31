<?php

namespace App\Support;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use PDO;
use Throwable;

class SystemInspector
{
    /**
     * @return array<int, array{label: string, value: string, description: string|null}>
     */
    public function serverInfo(): array
    {
        $memory = $this->memoryInfo();
        $swap = $this->swapInfo();
        $disk = $this->diskInfo(base_path());

        return [
            [
                'label' => __('admin.system.labels.load_average'),
                'value' => $this->loadAverage(),
                'description' => __('admin.system.descriptions.load_average'),
            ],
            [
                'label' => __('admin.system.labels.cpu_usage'),
                'value' => $this->cpuUsage(),
                'description' => __('admin.system.descriptions.cpu_usage'),
            ],
            [
                'label' => __('admin.system.labels.cpu_cores'),
                'value' => $this->cpuCores(),
                'description' => __('admin.system.descriptions.cpu_cores'),
            ],
            [
                'label' => __('admin.system.labels.memory_usage'),
                'value' => $memory['value'],
                'description' => $memory['description'],
            ],
            [
                'label' => __('admin.system.labels.swap_usage'),
                'value' => $swap['value'],
                'description' => $swap['description'],
            ],
            [
                'label' => __('admin.system.labels.free_disk_space'),
                'value' => $disk['value'],
                'description' => $disk['description'],
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function applicationInfo(): array
    {
        $database = $this->databaseInfo();
        $redis = $this->redisInfo();
        $frontend = $this->frontendInfo();

        return [
            ['label' => __('admin.system.labels.php_version'), 'value' => PHP_VERSION],
            ['label' => __('admin.system.labels.php_sapi'), 'value' => PHP_SAPI],
            ['label' => __('admin.system.labels.os_version'), 'value' => php_uname('s').' '.php_uname('r')],
            ['label' => __('admin.system.labels.laravel_version'), 'value' => app()->version()],
            ['label' => __('admin.system.labels.filament_version'), 'value' => $this->packageVersion('filament/filament')],
            ['label' => __('admin.system.labels.application_environment'), 'value' => app()->environment()],
            ['label' => __('admin.system.labels.debug_mode'), 'value' => config('app.debug') ? __('admin.system.values.enabled') : __('admin.system.values.disabled')],
            ['label' => __('admin.system.labels.application_url'), 'value' => (string) config('app.url')],
            ['label' => __('admin.system.labels.database_driver_version'), 'value' => trim($database['driver'].' '.$database['version'])],
            ['label' => __('admin.system.labels.redis_client_status'), 'value' => trim($redis['client'].' '.$redis['status'])],
            ['label' => __('admin.system.labels.image_driver_version'), 'value' => $this->imageDriverVersion()],
            ['label' => __('admin.system.labels.cache_store'), 'value' => (string) config('cache.default')],
            ['label' => __('admin.system.labels.queue_connection'), 'value' => (string) config('queue.default')],
            ['label' => __('admin.system.labels.filesystem_disk'), 'value' => (string) config('filesystems.default')],
            ['label' => __('admin.system.labels.nuxt_constraint'), 'value' => $frontend['nuxt']],
            ['label' => __('admin.system.labels.vue_constraint'), 'value' => $frontend['vue']],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, status: string, description: string|null}>
     */
    public function requirements(): array
    {
        $database = $this->databaseInfo();
        $redis = $this->redisInfo();

        return [
            [
                'label' => 'PHP 8.3+',
                'value' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.3.0', '>=') ? 'pass' : 'fail',
                'description' => __('admin.system.descriptions.project_target_runtime'),
            ],
            [
                'label' => 'Laravel 12',
                'value' => app()->version(),
                'status' => version_compare(app()->version(), '12.0.0', '>=') ? 'pass' : 'fail',
                'description' => __('admin.system.descriptions.backend_framework_version'),
            ],
            [
                'label' => __('admin.system.labels.database_connection'),
                'value' => $database['version'] ?: $database['error'],
                'status' => $database['connected'] ? 'pass' : 'fail',
                'description' => $database['driver'],
            ],
            [
                'label' => __('admin.system.labels.mariadb_mysql_database'),
                'value' => $database['driver'],
                'status' => in_array($database['driver'], ['mariadb', 'mysql'], true) ? 'pass' : 'warning',
                'description' => __('admin.system.descriptions.production_database'),
            ],
            [
                'label' => __('admin.system.labels.innodb_support'),
                'value' => $database['innodb'] ?? __('admin.system.values.not_checked'),
                'status' => $database['innodb'] === 'Available' ? 'pass' : ($database['driver'] === 'sqlite' ? 'warning' : 'fail'),
                'description' => __('admin.system.descriptions.innodb_support'),
            ],
            [
                'label' => __('admin.system.labels.redis_connection'),
                'value' => $redis['status'],
                'status' => $redis['connected'] ? 'pass' : 'fail',
                'description' => __('admin.system.descriptions.redis_connection'),
            ],
            [
                'label' => __('admin.system.labels.application_key'),
                'value' => filled(config('app.key')) ? __('admin.messages.configured') : __('admin.system.values.missing'),
                'status' => filled(config('app.key')) ? 'pass' : 'fail',
                'description' => __('admin.system.descriptions.application_key'),
            ],
            [
                'label' => __('admin.system.labels.storage_writable'),
                'value' => is_writable(storage_path()) ? __('admin.system.values.writable') : __('admin.system.values.not_writable'),
                'status' => is_writable(storage_path()) ? 'pass' : 'fail',
                'description' => storage_path(),
            ],
            [
                'label' => __('admin.system.labels.public_storage_link'),
                'value' => File::exists(public_path('storage')) ? __('admin.system.values.exists') : __('admin.system.values.missing'),
                'status' => File::exists(public_path('storage')) ? 'pass' : 'warning',
                'description' => __('admin.system.descriptions.public_storage_link'),
            ],
            [
                'label' => __('admin.system.labels.sensitive_folders'),
                'value' => $this->sensitiveFoldersAreOutsidePublicRoot() ? __('admin.system.values.protected') : __('admin.system.values.review_needed'),
                'status' => $this->sensitiveFoldersAreOutsidePublicRoot() ? 'pass' : 'fail',
                'description' => __('admin.system.descriptions.sensitive_folders'),
            ],
            ...$this->extensionRequirements(),
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, status: string, description: string|null}>
     */
    public function phpExtensions(): array
    {
        return collect([
            'bcmath',
            'ctype',
            'curl',
            'dom',
            'fileinfo',
            'filter',
            'hash',
            'json',
            'mbstring',
            'openssl',
            'pdo',
            'pdo_mysql',
            'redis',
            'tokenizer',
            'xml',
        ])
            ->map(fn (string $extension): array => [
                'label' => $extension,
                'value' => extension_loaded($extension) ? (phpversion($extension) ?: __('admin.system.values.loaded')) : __('admin.system.values.missing'),
                'status' => extension_loaded($extension) ? 'pass' : ($extension === 'redis' ? 'warning' : 'fail'),
                'description' => $extension === 'redis' ? __('admin.system.descriptions.redis_extension') : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function storageInfo(): array
    {
        return [
            ['label' => __('admin.system.labels.base_path'), 'value' => base_path()],
            ['label' => __('admin.system.labels.public_path'), 'value' => public_path()],
            ['label' => __('admin.system.labels.storage_path'), 'value' => storage_path()],
            ['label' => __('admin.system.labels.public_disk_root'), 'value' => (string) config('filesystems.disks.public.root')],
            ['label' => __('admin.system.labels.public_disk_url'), 'value' => (string) config('filesystems.disks.public.url')],
            ['label' => __('admin.system.labels.logs_path'), 'value' => storage_path('logs')],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function serviceInfo(): array
    {
        $database = $this->databaseInfo();
        $redis = $this->redisInfo();

        return [
            ['label' => __('admin.system.labels.database_connection'), 'value' => (string) config('database.default')],
            ['label' => __('admin.system.labels.database_host'), 'value' => (string) config('database.connections.'.config('database.default').'.host', 'N/A')],
            ['label' => __('admin.system.labels.database_name'), 'value' => (string) config('database.connections.'.config('database.default').'.database', 'N/A')],
            ['label' => __('admin.system.labels.database_server'), 'value' => $database['version'] ?: $database['error']],
            ['label' => __('admin.system.labels.redis_client'), 'value' => $redis['client']],
            ['label' => __('admin.system.labels.redis_host'), 'value' => (string) config('database.redis.default.host', 'N/A')],
            ['label' => __('admin.system.labels.redis_port'), 'value' => (string) config('database.redis.default.port', 'N/A')],
            ['label' => __('admin.system.labels.redis_status'), 'value' => $redis['status']],
            ['label' => __('admin.system.labels.session_driver'), 'value' => (string) config('session.driver')],
            ['label' => __('admin.system.labels.cache_store'), 'value' => (string) config('cache.default')],
            ['label' => __('admin.system.labels.queue_connection'), 'value' => (string) config('queue.default')],
        ];
    }

    /**
     * @return array{connected: bool, driver: string, version: string, error: string, innodb: string|null}
     */
    private function databaseInfo(): array
    {
        $driver = (string) config('database.default');

        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            $serverVersion = (string) $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            $version = $serverVersion;

            if (in_array($driver, ['mariadb', 'mysql'], true)) {
                $versionRow = $connection->selectOne('select version() as version');
                $version = (string) ($versionRow->version ?? $serverVersion);
            }

            if ($driver === 'sqlite') {
                $versionRow = $connection->selectOne('select sqlite_version() as version');
                $version = 'SQLite '.(string) ($versionRow->version ?? $serverVersion);
            }

            return [
                'connected' => true,
                'driver' => $driver,
                'version' => $version,
                'error' => '',
                'innodb' => $this->innodbSupport($driver),
            ];
        } catch (Throwable $exception) {
            return [
                'connected' => false,
                'driver' => $driver,
                'version' => '',
                'error' => $exception->getMessage(),
                'innodb' => null,
            ];
        }
    }

    /**
     * @return array{connected: bool, client: string, status: string}
     */
    private function redisInfo(): array
    {
        $client = (string) config('database.redis.client');

        try {
            $response = Redis::connection()->ping();

            return [
                'connected' => true,
                'client' => $client,
                'status' => is_string($response) ? $response : __('admin.system.values.connected'),
            ];
        } catch (Throwable $exception) {
            return [
                'connected' => false,
                'client' => $client,
                'status' => __('admin.system.values.unavailable_with_message', ['message' => $exception->getMessage()]),
            ];
        }
    }

    private function innodbSupport(string $driver): ?string
    {
        if (! in_array($driver, ['mariadb', 'mysql'], true)) {
            return $driver === 'sqlite' ? __('admin.system.values.not_applicable_sqlite') : null;
        }

        try {
            $row = DB::selectOne("show variables like 'have_innodb'");
            $value = strtoupper((string) ($row->Value ?? $row->value ?? ''));

            return in_array($value, ['YES', 'ON'], true) ? 'Available' : __('admin.system.values.unavailable');
        } catch (Throwable) {
            try {
                DB::selectOne("select engine from information_schema.engines where engine = 'InnoDB'");

                return 'Available';
            } catch (Throwable) {
                return __('admin.system.values.unavailable');
            }
        }
    }

    private function loadAverage(): string
    {
        if (! function_exists('sys_getloadavg')) {
            return __('admin.system.values.unavailable');
        }

        $load = sys_getloadavg();

        if ($load === false) {
            return __('admin.system.values.unavailable');
        }

        return collect($load)
            ->take(3)
            ->map(fn (float $value): string => number_format($value, 2))
            ->implode(' / ');
    }

    private function cpuUsage(): string
    {
        $firstSample = $this->procCpuSample();

        if ($firstSample === null) {
            return __('admin.system.values.unavailable');
        }

        usleep(100000);

        $secondSample = $this->procCpuSample();

        if ($secondSample === null) {
            return __('admin.system.values.unavailable');
        }

        $totalDelta = $secondSample['total'] - $firstSample['total'];
        $idleDelta = $secondSample['idle'] - $firstSample['idle'];

        if ($totalDelta <= 0) {
            return __('admin.system.values.unavailable');
        }

        $usage = max(0, min(100, (1 - ($idleDelta / $totalDelta)) * 100));

        return number_format($usage, 1).'%';
    }

    private function cpuCores(): string
    {
        if (is_readable('/proc/cpuinfo')) {
            $cpuInfo = File::get('/proc/cpuinfo');
            preg_match_all('/^processor\s*:/m', $cpuInfo, $matches);
            $cores = count($matches[0]);

            if ($cores > 0) {
                return (string) $cores;
            }
        }

        if (function_exists('shell_exec')) {
            $cores = trim((string) @shell_exec('getconf _NPROCESSORS_ONLN 2>/dev/null'));

            if (ctype_digit($cores) && (int) $cores > 0) {
                return $cores;
            }
        }

        return __('admin.system.values.unavailable');
    }

    /**
     * @return array{value: string, description: string|null}
     */
    private function memoryInfo(): array
    {
        $meminfo = $this->procMeminfo();

        if (! isset($meminfo['MemTotal'], $meminfo['MemAvailable'])) {
            return [
                'value' => __('admin.system.values.unavailable'),
                'description' => __('admin.system.descriptions.memory_unavailable'),
            ];
        }

        $total = $meminfo['MemTotal'] * 1024;
        $available = $meminfo['MemAvailable'] * 1024;
        $used = max(0, $total - $available);

        return [
            'value' => $this->formatBytes($used).' / '.$this->formatBytes($total).' ('.$this->formatPercent($used, $total).')',
            'description' => __('admin.system.descriptions.bytes_available', ['bytes' => $this->formatBytes($available)]),
        ];
    }

    /**
     * @return array{value: string, description: string|null}
     */
    private function swapInfo(): array
    {
        $meminfo = $this->procMeminfo();

        if (! isset($meminfo['SwapTotal'], $meminfo['SwapFree'])) {
            return [
                'value' => __('admin.system.values.unavailable'),
                'description' => __('admin.system.descriptions.swap_unavailable'),
            ];
        }

        $total = $meminfo['SwapTotal'] * 1024;
        $free = $meminfo['SwapFree'] * 1024;

        if ($total <= 0) {
            return [
                'value' => __('admin.system.values.no_swap_configured'),
                'description' => __('admin.system.descriptions.zero_total'),
            ];
        }

        $used = max(0, $total - $free);

        return [
            'value' => $this->formatBytes($used).' / '.$this->formatBytes($total).' ('.$this->formatPercent($used, $total).')',
            'description' => __('admin.system.descriptions.bytes_available', ['bytes' => $this->formatBytes($free)]),
        ];
    }

    /**
     * @return array{value: string, description: string|null}
     */
    private function diskInfo(string $path): array
    {
        $total = disk_total_space($path);
        $free = disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return [
                'value' => __('admin.system.values.unavailable'),
                'description' => $path,
            ];
        }

        $used = max(0, $total - $free);

        return [
            'value' => __('admin.system.values.bytes_free', ['bytes' => $this->formatBytes($free)]),
            'description' => __('admin.system.descriptions.disk_usage', [
                'total' => $this->formatBytes($total),
                'percentage' => $this->formatPercent($used, $total),
                'path' => $path,
            ]),
        ];
    }

    /**
     * @return array{idle: int, total: int}|null
     */
    private function procCpuSample(): ?array
    {
        if (! is_readable('/proc/stat')) {
            return null;
        }

        $line = strtok(File::get('/proc/stat'), "\n");

        if (! is_string($line) || ! str_starts_with($line, 'cpu ')) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($line));

        if (! is_array($parts)) {
            return null;
        }

        $values = collect($parts)
            ->skip(1)
            ->map(fn (string $value): int => (int) $value)
            ->values();

        if ($values->count() < 4) {
            return null;
        }

        $idle = (int) $values->get(3) + (int) $values->get(4, 0);

        return [
            'idle' => $idle,
            'total' => $values->sum(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function procMeminfo(): array
    {
        if (! is_readable('/proc/meminfo')) {
            return [];
        }

        $info = [];

        foreach (explode("\n", File::get('/proc/meminfo')) as $line) {
            if (! preg_match('/^([A-Za-z_()]+):\s+(\d+)\s+kB$/', $line, $matches)) {
                continue;
            }

            $info[$matches[1]] = (int) $matches[2];
        }

        return $info;
    }

    private function formatBytes(float|int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = max(0, (float) $bytes);
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return number_format($value, $unit === 0 ? 0 : 1).' '.$units[$unit];
    }

    private function formatPercent(float|int $used, float|int $total): string
    {
        if ($total <= 0) {
            return '0%';
        }

        return number_format(($used / $total) * 100, 1).'%';
    }

    private function imageDriverVersion(): string
    {
        if (extension_loaded('imagick') && class_exists('Imagick')) {
            $version = \Imagick::getVersion();

            return 'Imagick '.(phpversion('imagick') ?: 'loaded').' ('.($version['versionString'] ?? 'ImageMagick').')';
        }

        if (extension_loaded('gd') && function_exists('gd_info')) {
            $info = gd_info();

            return 'GD '.($info['GD Version'] ?? (phpversion('gd') ?: 'loaded'));
        }

        return __('admin.system.values.no_image_extension');
    }

    private function packageVersion(string $package): string
    {
        try {
            return InstalledVersions::getPrettyVersion($package) ?? __('admin.system.values.installed');
        } catch (Throwable) {
            return __('admin.system.values.not_installed');
        }
    }

    /**
     * @return array{nuxt: string, vue: string}
     */
    private function frontendInfo(): array
    {
        $packageJson = base_path('../frontend/package.json');

        if (! File::exists($packageJson)) {
            return [
                'nuxt' => __('admin.system.values.unknown'),
                'vue' => __('admin.system.values.unknown'),
            ];
        }

        $contents = json_decode((string) File::get($packageJson), true);
        $dependencies = is_array($contents) ? ($contents['dependencies'] ?? []) : [];

        return [
            'nuxt' => (string) ($dependencies['nuxt'] ?? __('admin.system.values.unknown')),
            'vue' => (string) ($dependencies['vue'] ?? __('admin.system.values.unknown')),
        ];
    }

    private function sensitiveFoldersAreOutsidePublicRoot(): bool
    {
        $publicPath = realpath(public_path());
        $storagePath = realpath(storage_path());
        $vendorPath = realpath(base_path('vendor'));
        $environmentPath = realpath(base_path('.env'));

        if ($publicPath === false) {
            return false;
        }

        foreach ([$storagePath, $vendorPath, $environmentPath] as $path) {
            if ($path !== false && str_starts_with($path, $publicPath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array{label: string, value: string, status: string, description: string|null}>
     */
    private function extensionRequirements(): array
    {
        return collect(['bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml'])
            ->map(fn (string $extension): array => [
                'label' => __('admin.system.labels.extension', ['extension' => $extension]),
                'value' => extension_loaded($extension) ? __('admin.system.values.loaded') : __('admin.system.values.missing'),
                'status' => extension_loaded($extension) ? 'pass' : 'fail',
                'description' => null,
            ])
            ->values()
            ->all();
    }
}
