<?php
namespace App\Service;

use App\Infrastructure\Database;
use App\Exception\PaymentException;

class WalletService
{
    public function __construct(private Database $db) {}

    public function deposit(int $walletId, int $amountCents): array
    {
        // TODO: Реализовать
        // 1. Валидация (amount > 0)
        // 2. SELECT ... FOR UPDATE
        // 3. UPDATE balance_cents = balance_cents + amount
        // 4. INSERT в wallet_transactions
        // 5. COMMIT
    }

    public function withdraw(int $walletId, int $amountCents): array
    {
        // TODO: Реализовать
        // 1. Валидация
        // 2. SELECT ... FOR UPDATE
        // 3. Проверка sufficient funds
        // 4. UPDATE balance_cents = balance_cents - amount
        // 5. INSERT в wallet_transactions
    }
}