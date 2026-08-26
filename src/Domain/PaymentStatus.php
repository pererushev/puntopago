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

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending    => in_array($next, [self::Processing, self::Cancelled], true),
            self::Processing => in_array($next, [self::Completed, self::Failed], true),
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