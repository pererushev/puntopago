<?php
declare(strict_types=1);

namespace Tests\Config;

use App\Config\DatabaseConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DatabaseConfigTest extends TestCase
{
    public function testFromArrayBuildsConfigAndDsn(): void
    {
        $config = DatabaseConfig::fromArray([
            'host' => '127.0.0.1',
            'name' => 'punto_pago',
            'user' => 'root',
            'password' => 'secret',
            'port' => 3307,
            'charset' => 'utf8mb4',
        ]);

        self::assertSame('127.0.0.1', $config->host);
        self::assertSame('punto_pago', $config->name);
        self::assertSame('root', $config->user);
        self::assertSame('secret', $config->password);
        self::assertSame(3307, $config->port);
        self::assertSame('utf8mb4', $config->charset);
        self::assertSame(
            'mysql:host=127.0.0.1;port=3307;dbname=punto_pago;charset=utf8mb4',
            $config->dsn(),
        );
    }

    public function testFromArrayUsesDefaultPortAndCharset(): void
    {
        $config = DatabaseConfig::fromArray([
            'host' => 'db',
            'name' => 'punto_pago',
            'user' => 'root',
            'password' => '',
        ]);

        self::assertSame(3306, $config->port);
        self::assertSame('utf8mb4', $config->charset);
        self::assertSame('', $config->password);
    }

    public function testFromFileLoadsProjectConfig(): void
    {
        $config = DatabaseConfig::fromFile();

        self::assertNotSame('', $config->host);
        self::assertNotSame('', $config->name);
        self::assertStringContainsString('mysql:host=', $config->dsn());
        self::assertStringContainsString('dbname=', $config->dsn());
    }

    public function testFromFileRejectsMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Database config file not found');

        DatabaseConfig::fromFile('/tmp/missing-database-config.php');
    }

    public function testFromArrayRequiresHost(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Database config requires key: host');

        DatabaseConfig::fromArray([
            'name' => 'punto_pago',
            'user' => 'root',
            'password' => 'secret',
        ]);
    }

    public function testRejectsEmptyHost(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Database host must not be empty');

        new DatabaseConfig('', 'punto_pago', 'root', 'secret');
    }

    public function testRejectsInvalidPort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Database port must be between 1 and 65535');

        new DatabaseConfig('db', 'punto_pago', 'root', 'secret', 0);
    }
}
