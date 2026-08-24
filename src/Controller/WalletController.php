<?php
namespace App\Controller;

use App\Http\WalletAmountRequest;
use App\Service\WalletService;

class WalletController
{
    public function __construct(private WalletService $service) {}

    public function deposit(int $walletId, array $body): void
    {
        $request = WalletAmountRequest::fromHttp($walletId, $body);
        $result  = $this->service->deposit($request->walletId, $request->amountCents);

        http_response_code(200);
        echo json_encode($result);
    }

    public function withdraw(int $walletId, array $body): void
    {
        $request = WalletAmountRequest::fromHttp($walletId, $body);
        $result  = $this->service->withdraw($request->walletId, $request->amountCents);

        http_response_code(200);
        echo json_encode($result);
    }
}
