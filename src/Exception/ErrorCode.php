<?php
declare(strict_types=1);

namespace App\Exception;

enum ErrorCode: string
{
    case ValidationError = 'VALIDATION_ERROR';
    case InvalidSignature = 'INVALID_SIGNATURE';
    case WalletNotFound = 'WALLET_NOT_FOUND';
    case PaymentNotFound = 'PAYMENT_NOT_FOUND';
    case IdempotencyConflict = 'IDEMPOTENCY_CONFLICT';
    case InvalidStatusTransition = 'INVALID_STATUS_TRANSITION';
    case BalanceOverflow = 'BALANCE_OVERFLOW';
    case InsufficientFunds = 'INSUFFICIENT_FUNDS';
    case InternalError = 'INTERNAL_ERROR';
}
