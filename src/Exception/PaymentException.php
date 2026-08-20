<?php
namespace App\Exception;

use RuntimeException;

class PaymentException extends RuntimeException
{
    public function __construct(string $message, int $code = 400)
    {
        parent::__construct($message, $code);
    }
}