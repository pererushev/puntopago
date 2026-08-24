<?php
namespace App\Controller;

use App\Http\CreatePaymentRequest;
use App\Service\PaymentService;

class PaymentController
{
    public function __construct(private PaymentService $service) {}

    public function create(array $body, ?string $idempotencyKey): void
    {
        $request = CreatePaymentRequest::fromHttp($body, $idempotencyKey);

        $payment = $this->service->createPayment(
            $request->idempotencyKey,
            $request->userId,
            $request->amountCents,
            $request->currency,
            $request->description,
        );

        http_response_code(201);
        echo json_encode($payment);
    }
}
