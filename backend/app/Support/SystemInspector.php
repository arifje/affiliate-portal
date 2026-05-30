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
