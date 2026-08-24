<?php
namespace App\Controller;

use App\Http\PaymentWebhookRequest;
use App\Service\PaymentService;

class WebhookController
{
    public function __construct(private PaymentService $service, private string $secret) {}

    public function handle(array $body, string $signature): void
    {
        $request = PaymentWebhookRequest::fromHttp($body, $signature);
        $result  = $this->service->handleWebhook(
            $request->toPayload(),
            $request->signature,
            $this->secret,
        );

        http_response_code(200);
        echo json_encode($result);
    }
}
