<?php
declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

class PaymentException extends RuntimeException
{
    public function __construct(
        string $message,
        private ErrorCode $errorCode = ErrorCode::ValidationError,
    ) {
        parent::__construct($message);
    }

    public function errorCode(): ErrorCode
    {
        return $this->errorCode;
    }
}
