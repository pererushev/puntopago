<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Exception\PaymentException;
use App\Http\WalletAmountRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WalletAmountRequestTest extends TestCase
{
    public function testFromHttpParsesValidPayload(): void
    {
        $request = WalletAmountRequest::fromHttp(1, ['amount_cents' => 5000], 'dep-abc-123');

        self::assertSame(1, $request->walletId);
        self::assertSame(5000, $request->amountCents);
        self::assertSame('dep-abc-123', $request->idempotencyKey);
    }

    #[DataProvider('invalidWalletIdProvider')]
    public function testFromHttpRejectsNonPositiveWalletId(int $walletId): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('wallet id must be a positive integer');
        $this->expectExceptionCode(400);

        WalletAmountRequest::fromHttp($walletId, ['amount_cents' => 5000], 'dep-abc-123');
    }

    public static function invalidWalletIdProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }

    public function testFromHttpRequiresAmountCents(): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Missing field: amount_cents');

        WalletAmountRequest::fromHttp(1, [], 'dep-abc-123');
    }

    public function testFromHttpRequiresIdempotencyKey(): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Idempotency-Key header is required');
        $this->expectExceptionCode(400);

        WalletAmountRequest::fromHttp(1, ['amount_cents' => 5000], null);
    }
}
