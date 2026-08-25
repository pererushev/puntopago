<?php
declare(strict_types=1);

namespace Tests\Exception;

use App\Exception\ErrorCode;
use App\Exception\PaymentException;
use PHPUnit\Framework\TestCase;

final class PaymentExceptionTest extends TestCase
{
    public function testDefaultsToValidationErrorWithoutHttpCode(): void
    {
        $e = new PaymentException('missing field');

        self::assertSame(ErrorCode::ValidationError, $e->errorCode());
        self::assertSame(0, $e->getCode());
    }

    public function testKeepsDomainErrorCode(): void
    {
        $e = new PaymentException('wallet not found', ErrorCode::WalletNotFound);

        self::assertSame(ErrorCode::WalletNotFound, $e->errorCode());
        self::assertSame(0, $e->getCode());
    }
}
