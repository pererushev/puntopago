<?php
declare(strict_types=1);

namespace App\Exception;

enum ErrorCode: string
{
    case ValidationError = 'VALIDATION_ERROR';
    case WalletNotFound = 'WALLET_NOT_FOUND';
    case IdempotencyConflict = 'IDEMPOTENCY_CONFLICT';
    case BalanceOverflow = 'BALANCE_OVERFLOW';
    case InsufficientFunds = 'INSUFFICIENT_FUNDS';
    case InternalError = 'INTERNAL_ERROR';
}
