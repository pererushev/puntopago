<?php
declare(strict_types=1);

namespace App\Config;

final class Env
{
    /** @var array<string, true> */
    private static array $loadedPaths = [];

    public static function load(string $path): void
    {
        $real = realpath($path);
        if ($real === false || isset(self::$loadedPaths[$real])) {
            return;
        }

        self::$loadedPaths[$real] = true;

        $lines = file($real, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            if ($key === '' || self::has($key)) {
                continue;
            }

            $value = self::unquote(trim($value));
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? false;
        }

        return $value === false ? $default : $value;
    }

    private static function has(string $key): bool
    {
        return getenv($key) !== false || array_key_exists($key, $_ENV);
    }

    private static function unquote(string $value): string
    {
        $len = strlen($value);
        if ($len >= 2) {
            $first = $value[0];
            $last  = $value[$len - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }
}
