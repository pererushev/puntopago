<?php
namespace App\Controller;

use App\Service\PaymentService;
use App\Exception\PaymentException;

class PaymentController
{
    public function __construct(private PaymentService $service) {}

    public function create(array $body, ?string $idempotencyKey): void
    {
        // TODO: Валидация body, вызов service, вывод JSON с кодом 201
    }
}