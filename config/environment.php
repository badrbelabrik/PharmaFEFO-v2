<?php

declare(strict_types=1);

namespace PharmaFEFO\Config;

class Environment
{
    private static array $config = [];

    public static function load(string $envFile): void
    {
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                self::$config[$key] = $value;
                $_ENV[$key] = $value;
            }
        }
    }

    public static function get(string $key, $default = null)
    {
        return self::$config[$key] ?? $_ENV[$key] ?? $default;
    }

    public static function isDevelopment(): bool
    {
        return self::get('APP_ENV', 'development') === 'development';
    }

    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'development') === 'production';
    }

    public static function getDatabaseConfig(): array
    {
        return [
            'host' => self::get('DB_HOST', 'localhost'),
            'name' => self::get('DB_NAME', 'pharmafefo'),
            'user' => self::get('DB_USER', 'root'),
            'pass' => self::get('DB_PASS', ''),
        ];
    }
}