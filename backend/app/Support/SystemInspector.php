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
                'label' => 'Load average',
                'value' => $this->loadAverage(),
                'description' => '1, 5 and 15 minute averages.',
            ],
            [
                'label' => 'CPU usage',
                'value' => $this->cpuUsage(),
                'description' => 'Short live estimate from the operating system.',
            ],
            [
                'label' => 'CPU cores',
                'value' => $this->cpuCores(),
                'description' => 'Logical cores visible to PHP.',
            ],
            [
                'label' => 'Memory usage',
                'value' => $memory['value'],
                'description' => $memory['description'],
            ],
            [
                'label' => 'Swap usage',
                'value' => $swap['value'],
                'description' => $swap['description'],
            ],
            [
                'label' => 'Free disk space',
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
            ['label' => 'PHP version', 'value' => PHP_VERSION],
            ['label' => 'PHP SAPI', 'value' => PHP_SAPI],
            ['label' => 'OS version', 'value' => php_uname('s').' '.php_uname('r')],
            ['label' => 'Laravel version', 'value' => app()->version()],
            ['label' => 'Filament version', 'value' => $this->packageVersion('filament/filament')],
            ['label' => 'Application environment', 'value' => app()->environment()],
            ['label' => 'Debug mode', 'value' => config('app.debug') ? 'Enabled' : 'Disabled'],
            ['label' => 'Application URL', 'value' => (string) config('app.url')],
            ['label' => 'Database driver & version', 'value' => trim($database['driver'].' '.$database['version'])],
            ['label' => 'Redis client & status', 'value' => trim($redis['client'].' '.$redis['status'])],
            ['label' => 'Image driver & version', 'value' => $this->imageDriverVersion()],
            ['label' => 'Cache store', 'value' => (string) config('cache.default')],
            ['label' => 'Queue connection', 'value' => (string) config('queue.default')],
            ['label' => 'Filesystem disk', 'value' => (string) config('filesystems.default')],
            ['label' => 'Nuxt constraint', 'value' => $frontend['nuxt']],
            ['label' => 'Vue constraint', 'value' => $frontend['vue']],
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
                'description' => 'Project target runtime.',
            ],
            [
                'label' => 'Laravel 12',
                'value' => app()->version(),
                'status' => version_compare(app()->version(), '12.0.0', '>=') ? 'pass' : 'fail',
                'description' => 'Backend framework version.',
            ],
            [
                'label' => 'Database connection',
                'value' => $database['version'] ?: $database['error'],
                'status' => $database['connected'] ? 'pass' : 'fail',
                'description' => $database['driver'],
            ],
            [
                'label' => 'MariaDB/MySQL database',
                'value' => $database['driver'],
                'status' => in_array($database['driver'], ['mariadb', 'mysql'], true) ? 'pass' : 'warning',
                'description' => 'SQLite is fine for tests, but production should use MariaDB/MySQL.',
            ],
            [
                'label' => 'InnoDB support',
                'value' => $database['innodb'] ?? 'Not checked',
                'status' => $database['innodb'] === 'Available' ? 'pass' : ($database['driver'] === 'sqlite' ? 'warning' : 'fail'),
                'description' => 'Required for relational integrity on MariaDB/MySQL.',
            ],
            [
                'label' => 'Redis connection',
                'value' => $redis['status'],
                'status' => $redis['connected'] ? 'pass' : 'fail',
                'description' => 'Used for queues/cache when configured.',
            ],
            [
                'label' => 'Application key',
                'value' => filled(config('app.key')) ? 'Configured' : 'Missing',
                'status' => filled(config('app.key')) ? 'pass' : 'fail',
                'description' => 'Required for encryption, sessions and signed data.',
            ],
            [
                'label' => 'Storage writable',
                'value' => is_writable(storage_path()) ? 'Writable' : 'Not writable',
                'status' => is_writable(storage_path()) ? 'pass' : 'fail',
                'description' => storage_path(),
            ],
            [
                'label' => 'Public storage link',
                'value' => File::exists(public_path('storage')) ? 'Exists' : 'Missing',
                'status' => File::exists(public_path('storage')) ? 'pass' : 'warning',
                'description' => 'Needed for public uploads such as site hero images.',
            ],
            [
                'label' => 'Sensitive folders outside public root',
                'value' => $this->sensitiveFoldersAreOutsidePublicRoot() ? 'Protected' : 'Review needed',
                'status' => $this->sensitiveFoldersAreOutsidePublicRoot() ? 'pass' : 'fail',
                'description' => 'Storage, vendor and environment files should not be served directly.',
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
                'value' => extension_loaded($extension) ? (phpversion($extension) ?: 'Loaded') : 'Missing',
                'status' => extension_loaded($extension) ? 'pass' : ($extension === 'redis' ? 'warning' : 'fail'),
                'description' => $extension === 'redis' ? 'Required when REDIS_CLIENT=phpredis.' : null,
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
            ['label' => 'Base path', 'value' => base_path()],
            ['label' => 'Public path', 'value' => public_path()],
            ['label' => 'Storage path', 'value' => storage_path()],
            ['label' => 'Public disk root', 'value' => (string) config('filesystems.disks.public.root')],
            ['label' => 'Public disk URL', 'value' => (string) config('filesystems.disks.public.url')],
            ['label' => 'Logs path', 'value' => storage_path('logs')],
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
            ['label' => 'Database connection', 'value' => (string) config('database.default')],
            ['label' => 'Database host', 'value' => (string) config('database.connections.'.config('database.default').'.host', 'N/A')],
            ['label' => 'Database name', 'value' => (string) config('database.connections.'.config('database.default').'.database', 'N/A')],
            ['label' => 'Database server', 'value' => $database['version'] ?: $database['error']],
            ['label' => 'Redis client', 'value' => $redis['client']],
            ['label' => 'Redis host', 'value' => (string) config('database.redis.default.host', 'N/A')],
            ['label' => 'Redis port', 'value' => (string) config('database.redis.default.port', 'N/A')],
            ['label' => 'Redis status', 'value' => $redis['status']],
            ['label' => 'Session driver', 'value' => (string) config('session.driver')],
            ['label' => 'Cache store', 'value' => (string) config('cache.default')],
            ['label' => 'Queue connection', 'value' => (string) config('queue.default')],
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
                'status' => is_string($response) ? $response : 'Connected',
            ];
        } catch (Throwable $exception) {
            return [
                'connected' => false,
                'client' => $client,
                'status' => 'Unavailable: '.$exception->getMessage(),
            ];
        }
    }

    private function innodbSupport(string $driver): ?string
    {
        if (! in_array($driver, ['mariadb', 'mysql'], true)) {
            return $driver === 'sqlite' ? 'Not applicable for SQLite' : null;
        }

        try {
            $row = DB::selectOne("show variables like 'have_innodb'");
            $value = strtoupper((string) ($row->Value ?? $row->value ?? ''));

            return in_array($value, ['YES', 'ON'], true) ? 'Available' : 'Unavailable';
        } catch (Throwable) {
            try {
                DB::selectOne("select engine from information_schema.engines where engine = 'InnoDB'");

                return 'Available';
            } catch (Throwable) {
                return 'Unavailable';
            }
        }
    }

    private function loadAverage(): string
    {
        if (! function_exists('sys_getloadavg')) {
            return 'Unavailable';
        }

        $load = sys_getloadavg();

        if ($load === false) {
            return 'Unavailable';
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
            return 'Unavailable';
        }

        usleep(100000);

        $secondSample = $this->procCpuSample();

        if ($secondSample === null) {
            return 'Unavailable';
        }

        $totalDelta = $secondSample['total'] - $firstSample['total'];
        $idleDelta = $secondSample['idle'] - $firstSample['idle'];

        if ($totalDelta <= 0) {
            return 'Unavailable';
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

        return 'Unavailable';
    }

    /**
     * @return array{value: string, description: string|null}
     */
    private function memoryInfo(): array
    {
        $meminfo = $this->procMeminfo();

        if (! isset($meminfo['MemTotal'], $meminfo['MemAvailable'])) {
            return [
                'value' => 'Unavailable',
                'description' => 'Memory totals are not exposed by this operating system.',
            ];
        }

        $total = $meminfo['MemTotal'] * 1024;
        $available = $meminfo['MemAvailable'] * 1024;
        $used = max(0, $total - $available);

        return [
            'value' => $this->formatBytes($used).' / '.$this->formatBytes($total).' ('.$this->formatPercent($used, $total).')',
            'description' => $this->formatBytes($available).' available.',
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
                'value' => 'Unavailable',
                'description' => 'Swap totals are not exposed by this operating system.',
            ];
        }

        $total = $meminfo['SwapTotal'] * 1024;
        $free = $meminfo['SwapFree'] * 1024;

        if ($total <= 0) {
            return [
                'value' => 'No swap configured',
                'description' => '0 B total.',
            ];
        }

        $used = max(0, $total - $free);

        return [
            'value' => $this->formatBytes($used).' / '.$this->formatBytes($total).' ('.$this->formatPercent($used, $total).')',
            'description' => $this->formatBytes($free).' available.',
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
                'value' => 'Unavailable',
                'description' => $path,
            ];
        }

        $used = max(0, $total - $free);

        return [
            'value' => $this->formatBytes($free).' free',
            'description' => $this->formatBytes($total).' total, '.$this->formatPercent($used, $total).' used at '.$path.'.',
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

        return 'No GD or Imagick extension loaded';
    }

    private function packageVersion(string $package): string
    {
        try {
            return InstalledVersions::getPrettyVersion($package) ?? 'Installed';
        } catch (Throwable) {
            return 'Not installed';
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
                'nuxt' => 'Unknown',
                'vue' => 'Unknown',
            ];
        }

        $contents = json_decode((string) File::get($packageJson), true);
        $dependencies = is_array($contents) ? ($contents['dependencies'] ?? []) : [];

        return [
            'nuxt' => (string) ($dependencies['nuxt'] ?? 'Unknown'),
            'vue' => (string) ($dependencies['vue'] ?? 'Unknown'),
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
                'label' => "{$extension} extension",
                'value' => extension_loaded($extension) ? 'Loaded' : 'Missing',
                'status' => extension_loaded($extension) ? 'pass' : 'fail',
                'description' => null,
            ])
            ->values()
            ->all();
    }
}
