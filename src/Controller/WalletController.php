<?php
namespace App\Controller;

use App\Service\WalletService;

class WalletController
{
    public function __construct(private WalletService $service) {}

    public function deposit(int $walletId, array $body): void
    {
        // TODO
    }

    public function withdraw(int $walletId, array $body): void
    {
        // TODO
    }
}