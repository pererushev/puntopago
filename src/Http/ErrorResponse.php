<?php
declare(strict_types=1);

namespace App\Http;

use App\Exception\ErrorCode;
use App\Exception\PaymentException;
use Throwable;

final class ErrorResponse
{
    public function __construct(
        public readonly int $status,
        public readonly string $message,
    ) {}

    public static function fromThrowable(Throwable $e): self
    {
        if ($e instanceof PaymentException) {
            return new self(self::statusFor($e->errorCode()), $e->getMessage());
        }

        if ($e instanceof HttpException) {
            return new self($e->status(), $e->getMessage());
        }

        return new self(500, $e->getMessage());
    }

    public function toArray(): array
    {
        return ['error' => $this->message];
    }

    private static function statusFor(ErrorCode $code): int
    {
        return match ($code) {
            ErrorCode::ValidationError => 400,
            ErrorCode::WalletNotFound => 404,
            ErrorCode::IdempotencyConflict => 409,
            ErrorCode::BalanceOverflow => 422,
            ErrorCode::InternalError => 500,
        };
    }
}
