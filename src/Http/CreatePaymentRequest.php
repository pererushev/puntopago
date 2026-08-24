<?php
namespace App\Http;

final class CreatePaymentRequest
{
    public function __construct(
        public readonly string $idempotencyKey,
        public readonly int $userId,
        public readonly int $amountCents,
        public readonly string $currency,
        public readonly string $description,
    ) {}

    public static function fromHttp(array $body, ?string $idempotencyKey): self
    {
        return new self(
            Assert::idempotencyKey($idempotencyKey),
            Assert::positiveInt($body, 'user_id'),
            Assert::positiveInt($body, 'amount_cents'),
            Assert::currency($body),
            Assert::optionalString($body, 'description', 255),
        );
    }
}
