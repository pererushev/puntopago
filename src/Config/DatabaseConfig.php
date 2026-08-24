<?php
declare(strict_types=1);

namespace App\Config;

use InvalidArgumentException;

final class DatabaseConfig
{
    public function __construct(
        public readonly string $host,
        public readonly string $name,
        public readonly string $user,
        public readonly string $password,
        public readonly int $port = 3306,
        public readonly string $charset = 'utf8mb4',
    ) {
        if ($this->host === '') {
            throw new InvalidArgumentException('Database host must not be empty');
        }
        if ($this->name === '') {
            throw new InvalidArgumentException('Database name must not be empty');
        }
        if ($this->port < 1 || $this->port > 65535) {
            throw new InvalidArgumentException('Database port must be between 1 and 65535');
        }
        if ($this->charset === '') {
            throw new InvalidArgumentException('Database charset must not be empty');
        }
    }

    public static function fromArray(array $config): self
    {
        foreach (['host', 'name', 'user', 'password'] as $key) {
            if (!array_key_exists($key, $config)) {
                throw new InvalidArgumentException("Database config requires key: {$key}");
            }
        }

        return new self(
            host: (string) $config['host'],
            name: (string) $config['name'],
            user: (string) $config['user'],
            password: (string) $config['password'],
            port: (int) ($config['port'] ?? 3306),
            charset: (string) ($config['charset'] ?? 'utf8mb4'),
        );
    }

    public static function fromFile(?string $path = null): self
    {
        $path ??= dirname(__DIR__, 2) . '/config/database.php';
        if (!is_file($path)) {
            throw new InvalidArgumentException("Database config file not found: {$path}");
        }

        $config = require $path;
        if (!is_array($config)) {
            throw new InvalidArgumentException('Database config must return an array');
        }

        return self::fromArray($config);
    }

    public function dsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->host,
            $this->port,
            $this->name,
            $this->charset,
        );
    }
}
