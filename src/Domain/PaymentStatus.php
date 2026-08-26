<?php
namespace App\Domain;

use JsonSerializable;

enum PaymentStatus: string implements JsonSerializable
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Failed     = 'failed';
    case Refunded   = 'refunded';
    case Cancelled  = 'cancelled';

    public static function tryFromProvider(string $status): ?self
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'success', 'succeeded', 'paid' => self::Completed,
            'failure', 'declined' => self::Failed,
            'canceled' => self::Cancelled,
            'refund' => self::Refunded,
            default => self::tryFrom($normalized),
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending    => in_array($next, [self::Processing, self::Completed, self::Failed, self::Cancelled], true),
            self::Processing => in_array($next, [self::Completed, self::Failed, self::Cancelled], true),
            self::Completed  => $next === self::Refunded,
            self::Failed     => $next === self::Pending,
            default          => false,
        };
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}