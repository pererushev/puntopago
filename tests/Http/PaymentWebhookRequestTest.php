<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Exception\PaymentException;
use App\Http\PaymentWebhookRequest;
use PHPUnit\Framework\TestCase;

final class PaymentWebhookRequestTest extends TestCase
{
    public function testFromHttpParsesValidPayload(): void
    {
        $request = PaymentWebhookRequest::fromHttp(
            [
                'payment_id' => 1,
                'status' => 'success',
                'idempotency_key' => 'pay-abc-123',
            ],
            'signature',
        );

        self::assertSame(1, $request->paymentId);
        self::assertSame('success', $request->status);
        self::assertSame('signature', $request->signature);
        self::assertSame('pay-abc-123', $request->idempotencyKey);
        self::assertSame(
            [
                'payment_id' => 1,
                'status' => 'success',
                'idempotency_key' => 'pay-abc-123',
            ],
            $request->toPayload(),
        );
    }

    public function testFromHttpTreatsBlankIdempotencyKeyAsNull(): void
    {
        $request = PaymentWebhookRequest::fromHttp(
            [
                'payment_id' => 1,
                'status' => 'success',
            ],
            'signature',
        );

        self::assertNull($request->idempotencyKey);
        self::assertSame(
            [
                'payment_id' => 1,
                'status' => 'success',
            ],
            $request->toPayload(),
        );
    }

    public function testFromHttpRequiresSignatureHeader(): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('X-Signature header is required');

        PaymentWebhookRequest::fromHttp(
            [
                'payment_id' => 1,
                'status' => 'success',
            ],
            '',
        );
    }

    public function testFromHttpRequiresStatus(): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Missing field: status');

        PaymentWebhookRequest::fromHttp(
            [
                'payment_id' => 1,
            ],
            'signature',
        );
    }
}
