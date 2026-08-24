<?php
declare(strict_types=1);

namespace Tests\Config;

use App\Config\Env;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testGetReturnsDefaultWhenUnset(): void
    {
        $key = 'PUNTOPAGO_TEST_' . bin2hex(random_bytes(8));

        self::assertSame('fallback', Env::get($key, 'fallback'));
    }

    public function testLoadReadsValuesAndDoesNotOverrideExisting(): void
    {
        $existingKey = 'PUNTOPAGO_EXISTING_' . bin2hex(random_bytes(8));
        $newKey = 'PUNTOPAGO_NEW_' . bin2hex(random_bytes(8));
        $quotedKey = 'PUNTOPAGO_QUOTED_' . bin2hex(random_bytes(8));

        putenv("{$existingKey}=already-set");
        $_ENV[$existingKey] = 'already-set';

        $path = $this->writeEnv(<<<ENV
# comment
{$existingKey}=from-file
{$newKey}=plain-value
{$quotedKey}="quoted value"
ENV);

        Env::load($path);

        self::assertSame('already-set', Env::get($existingKey));
        self::assertSame('plain-value', Env::get($newKey));
        self::assertSame('quoted value', Env::get($quotedKey));

        putenv($existingKey);
        putenv($newKey);
        putenv($quotedKey);
        unset($_ENV[$existingKey], $_ENV[$newKey], $_ENV[$quotedKey]);
    }

    public function testLoadIgnoresMissingFile(): void
    {
        Env::load('/tmp/puntopago-missing.env');

        self::assertSame('ok', Env::get('PUNTOPAGO_MISSING_FILE_' . uniqid(), 'ok'));
    }

    private function writeEnv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'puntopago_env_');
        self::assertNotFalse($path);
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }
}
