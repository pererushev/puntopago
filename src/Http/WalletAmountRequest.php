<?php
namespace App\Http;

use App\Exception\PaymentException;

final class WalletAmountRequest
{
    public function __construct(
        public readonly int $walletId,
        public readonly int $amountCents,
    ) {}

    public static function fromHttp(int $walletId, array $body): self
    {
        if ($walletId <= 0) {
            throw new PaymentException('wallet id must be a positive integer');
        }

        return new self($walletId, Assert::positiveInt($body, 'amount_cents'));
    }
}
