<?php
declare(strict_types=1);

namespace Tests\Http;

use App\Exception\ErrorCode;
use App\Exception\PaymentException;
use App\Http\ErrorResponse;
use App\Http\HttpException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ErrorResponseTest extends TestCase
{
    #[DataProvider('paymentExceptionProvider')]
    public function testMapsPaymentExceptionToHttpStatus(ErrorCode $code, int $status): void
    {
        $response = ErrorResponse::fromThrowable(new PaymentException('boom', $code));

        self::assertSame($status, $response->status);
        self::assertSame('boom', $response->message);
        self::assertSame(['error' => 'boom'], $response->toArray());
    }

    public static function paymentExceptionProvider(): iterable
    {
        yield 'validation' => [ErrorCode::ValidationError, 400];
        yield 'invalid signature' => [ErrorCode::InvalidSignature, 401];
        yield 'wallet not found' => [ErrorCode::WalletNotFound, 404];
        yield 'payment not found' => [ErrorCode::PaymentNotFound, 404];
        yield 'idempotency conflict' => [ErrorCode::IdempotencyConflict, 409];
        yield 'invalid status transition' => [ErrorCode::InvalidStatusTransition, 409];
        yield 'balance overflow' => [ErrorCode::BalanceOverflow, 422];
        yield 'insufficient funds' => [ErrorCode::InsufficientFunds, 422];
        yield 'internal' => [ErrorCode::InternalError, 500];
    }

    public function testDefaultPaymentExceptionIsValidationError(): void
    {
        $response = ErrorResponse::fromThrowable(new PaymentException('missing field'));

        self::assertSame(400, $response->status);
        self::assertSame(['error' => 'missing field'], $response->toArray());
    }

    public function testMapsHttpExceptionStatus(): void
    {
        $response = ErrorResponse::fromThrowable(new HttpException('Not Found', 404));

        self::assertSame(404, $response->status);
        self::assertSame(['error' => 'Not Found'], $response->toArray());
    }

    public function testUnknownThrowableBecomesInternalServerError(): void
    {
        $response = ErrorResponse::fromThrowable(new RuntimeException('db down', 1045));

        self::assertSame(500, $response->status);
        self::assertSame(['error' => 'db down'], $response->toArray());
    }

    public function testJsonBodyDoesNotIncludeHttpCode(): void
    {
        $response = ErrorResponse::fromThrowable(
            new PaymentException('conflict', ErrorCode::IdempotencyConflict),
        );

        self::assertArrayNotHasKey('code', $response->toArray());
    }
}
