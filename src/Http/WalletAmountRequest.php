<?php
namespace App\Http;

use App\Exception\PaymentException;

final class WalletAmountRequest
{
    public function __construct(
        public readonly int $walletId,
        public readonly int $amountCents,
        public readonly string $idempotencyKey,
    ) {}

    public static function fromHttp(int $walletId, array $body, ?string $idempotencyKey): self
    {
        if ($walletId <= 0) {
            throw new PaymentException('wallet id must be a positive integer');
        }

        return new self(
            $walletId,
            Assert::positiveInt($body, 'amount_cents'),
            Assert::idempotencyKey($idempotencyKey),
        );
    }
}
