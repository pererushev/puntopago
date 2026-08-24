<?php
namespace App\Http;

final class PaymentWebhookRequest
{
    public function __construct(
        public readonly int $paymentId,
        public readonly string $status,
        public readonly string $signature,
        public readonly ?string $idempotencyKey,
    ) {}

    public static function fromHttp(array $body, string $signature): self
    {
        $idempotencyKey = Assert::optionalString($body, 'idempotency_key', 64);

        return new self(
            Assert::positiveInt($body, 'payment_id'),
            Assert::requireString($body, 'status', 64),
            Assert::requiredHeader($signature, 'X-Signature'),
            $idempotencyKey === '' ? null : $idempotencyKey,
        );
    }

    public function toPayload(): array
    {
        $payload = [
            'payment_id' => $this->paymentId,
            'status'     => $this->status,
        ];

        if ($this->idempotencyKey !== null) {
            $payload['idempotency_key'] = $this->idempotencyKey;
        }

        return $payload;
    }
}
