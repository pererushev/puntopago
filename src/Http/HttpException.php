<?php
declare(strict_types=1);

namespace App\Http;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(
        string $message,
        private int $status,
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
