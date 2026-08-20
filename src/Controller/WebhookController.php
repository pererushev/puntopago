<?php
namespace App\Controller;

use App\Service\PaymentService;

class WebhookController
{
    public function __construct(private PaymentService $service, private string $secret) {}

    public function handle(array $body, string $signature): void
    {
        // TODO: вызвать service->handleWebhook
    }
}