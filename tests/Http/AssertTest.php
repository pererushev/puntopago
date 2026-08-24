<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Exception\PaymentException;
use App\Http\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssertTest extends TestCase
{
    #[DataProvider('validPositiveIntProvider')]
    public function testPositiveIntAcceptsValidValues(array $body, string $field, int $expected): void
    {
        self::assertSame($expected, Assert::positiveInt($body, $field));
    }

    public static function validPositiveIntProvider(): iterable
    {
        yield 'integer' => [['amount_cents' => 1500], 'amount_cents', 1500];
        yield 'numeric string' => [['amount_cents' => '1500'], 'amount_cents', 1500];
        yield 'one' => [['user_id' => 1], 'user_id', 1];
    }

    #[DataProvider('invalidPositiveIntProvider')]
    public function testPositiveIntRejectsInvalidValues(array $body, string $field, string $message): void
    {
        $this->expectPaymentException($message);

        Assert::positiveInt($body, $field);
    }

    public static function invalidPositiveIntProvider(): iterable
    {
        yield 'missing field' => [[], 'user_id', 'Missing field: user_id'];
        yield 'null' => [['user_id' => null], 'user_id', 'user_id must be a positive integer'];
        yield 'bool true' => [['user_id' => true], 'user_id', 'user_id must be a positive integer'];
        yield 'bool false' => [['user_id' => false], 'user_id', 'user_id must be a positive integer'];
        yield 'array' => [['user_id' => []], 'user_id', 'user_id must be a positive integer'];
        yield 'object' => [['user_id' => (object) []], 'user_id', 'user_id must be a positive integer'];
        yield 'zero' => [['user_id' => 0], 'user_id', 'user_id must be a positive integer'];
        yield 'negative' => [['user_id' => -1], 'user_id', 'user_id must be a positive integer'];
        yield 'float string' => [['user_id' => '1.5'], 'user_id', 'user_id must be a positive integer'];
        yield 'non numeric' => [['user_id' => 'abc'], 'user_id', 'user_id must be a positive integer'];
        yield 'empty string' => [['user_id' => ''], 'user_id', 'user_id must be a positive integer'];
        yield 'leading zero' => [['user_id' => '01'], 'user_id', 'user_id must be a positive integer'];
    }

    public function testPositiveIntExceptionHasHttpBadRequestCode(): void
    {
        try {
            Assert::positiveInt([], 'amount_cents');
            self::fail('Expected PaymentException');
        } catch (PaymentException $e) {
            self::assertSame(400, $e->getCode());
        }
    }

    #[DataProvider('validCurrencyProvider')]
    public function testCurrencyAcceptsIsoCodes(array $body, string $expected): void
    {
        self::assertSame($expected, Assert::currency($body));
    }

    public static function validCurrencyProvider(): iterable
    {
        yield 'uppercase' => [['currency' => 'USD'], 'USD'];
        yield 'lowercase' => [['currency' => 'usd'], 'USD'];
        yield 'mixed case' => [['currency' => 'Eur'], 'EUR'];
    }

    #[DataProvider('invalidCurrencyProvider')]
    public function testCurrencyRejectsInvalidValues(array $body, string $message): void
    {
        $this->expectPaymentException($message);

        Assert::currency($body);
    }

    public static function invalidCurrencyProvider(): iterable
    {
        yield 'missing field' => [[], 'Missing field: currency'];
        yield 'null' => [['currency' => null], 'Missing field: currency'];
        yield 'empty string' => [['currency' => ''], 'Missing field: currency'];
        yield 'integer' => [['currency' => 123], 'currency must be a 3-letter ISO code'];
        yield 'bool' => [['currency' => true], 'currency must be a 3-letter ISO code'];
        yield 'too short' => [['currency' => 'US'], 'currency must be a 3-letter ISO code'];
        yield 'too long' => [['currency' => 'USDT'], 'currency must be a 3-letter ISO code'];
        yield 'non letters' => [['currency' => 'US$'], 'currency must be a 3-letter ISO code'];
    }

    public function testCurrencyUsesCustomFieldName(): void
    {
        self::assertSame('RUB', Assert::currency(['ccy' => 'rub'], 'ccy'));
    }

    public function testRequireStringTrimsValue(): void
    {
        self::assertSame('hello', Assert::requireString(['status' => '  hello  '], 'status'));
    }

    #[DataProvider('invalidRequireStringProvider')]
    public function testRequireStringRejectsInvalidValues(array $body, string $message): void
    {
        $this->expectPaymentException($message);

        Assert::requireString($body, 'status', 8);
    }

    public static function invalidRequireStringProvider(): iterable
    {
        yield 'missing field' => [[], 'Missing field: status'];
        yield 'null' => [['status' => null], 'Missing field: status'];
        yield 'empty string' => [['status' => ''], 'Missing field: status'];
        yield 'whitespace only' => [['status' => '   '], 'Missing field: status'];
        yield 'not a string' => [['status' => 1], 'status must be a string'];
        yield 'too long' => [['status' => '123456789'], 'status must be at most 8 characters'];
    }

    public function testOptionalStringReturnsEmptyWhenMissingOrNull(): void
    {
        self::assertSame('', Assert::optionalString([], 'description', 255));
        self::assertSame('', Assert::optionalString(['description' => null], 'description', 255));
    }

    public function testOptionalStringTrimsAndAllowsBlank(): void
    {
        self::assertSame('note', Assert::optionalString(['description' => '  note  '], 'description', 255));
        self::assertSame('', Assert::optionalString(['description' => '   '], 'description', 255));
    }

    public function testOptionalStringRejectsNonStringAndTooLong(): void
    {
        try {
            Assert::optionalString(['description' => 1], 'description', 8);
            self::fail('Expected PaymentException');
        } catch (PaymentException $e) {
            self::assertSame('description must be a string', $e->getMessage());
        }

        try {
            Assert::optionalString(['description' => '123456789'], 'description', 8);
            self::fail('Expected PaymentException');
        } catch (PaymentException $e) {
            self::assertSame('description must be at most 8 characters', $e->getMessage());
        }
    }

    public function testIdempotencyKeyAcceptsValueWithinLimit(): void
    {
        self::assertSame('pay-abc-123', Assert::idempotencyKey('pay-abc-123'));
        self::assertSame(str_repeat('a', 64), Assert::idempotencyKey(str_repeat('a', 64)));
    }

    #[DataProvider('invalidIdempotencyKeyProvider')]
    public function testIdempotencyKeyRejectsInvalidValues(?string $key, string $message): void
    {
        $this->expectPaymentException($message);

        Assert::idempotencyKey($key);
    }

    public static function invalidIdempotencyKeyProvider(): iterable
    {
        yield 'null' => [null, 'Idempotency-Key header is required'];
        yield 'empty' => ['', 'Idempotency-Key header is required'];
        yield 'too long' => [str_repeat('a', 65), 'Idempotency-Key must be at most 64 characters'];
    }

    public function testRequiredHeaderReturnsValue(): void
    {
        self::assertSame('sig', Assert::requiredHeader('sig', 'X-Signature'));
    }

    public function testRequiredHeaderRejectsEmptyValue(): void
    {
        $this->expectPaymentException('X-Signature header is required');

        Assert::requiredHeader('', 'X-Signature');
    }

    private function expectPaymentException(string $message): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode(400);
    }
}
