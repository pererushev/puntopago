<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Exception\PaymentException;
use App\Http\CreatePaymentRequest;
use PHPUnit\Framework\TestCase;

final class CreatePaymentRequestTest extends TestCase
{
    public function testFromHttpParsesValidPayload(): void
    {
        $request = CreatePaymentRequest::fromHttp(
            [
                'user_id' => 1,
                'amount_cents' => 1500,
                'currency' => 'usd',
                'description' => '  Test payment  ',
            ],
            'pay-abc-123',
        );

        self::assertSame('pay-abc-123', $request->idempotencyKey);
        self::assertSame(1, $request->userId);
        self::assertSame(1500, $request->amountCents);
        self::assertSame('USD', $request->currency);
        self::assertSame('Test payment', $request->description);
    }

    public function testFromHttpAllowsMissingDescription(): void
    {
        $request = CreatePaymentRequest::fromHttp(
            [
                'user_id' => 1,
                'amount_cents' => 1500,
                'currency' => 'USD',
            ],
            'pay-abc-123',
        );

        self::assertSame('', $request->description);
    }

    public function testFromHttpRequiresIdempotencyKey(): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Idempotency-Key header is required');

        CreatePaymentRequest::fromHttp(
            [
                'user_id' => 1,
                'amount_cents' => 1500,
                'currency' => 'USD',
            ],
            null,
        );
    }

    public function testFromHttpRequiresUserId(): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Missing field: user_id');

        CreatePaymentRequest::fromHttp(
            [
                'amount_cents' => 1500,
                'currency' => 'USD',
            ],
            'pay-abc-123',
        );
    }
}
