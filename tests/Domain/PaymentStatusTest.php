<?php
declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\PaymentStatus;
use PHPUnit\Framework\TestCase;

final class PaymentStatusTest extends TestCase
{
    /**
     * @dataProvider providerStatuses
     */
    public function testTryFromProvider(string $providerStatus, ?PaymentStatus $expected): void
    {
        self::assertSame($expected, PaymentStatus::tryFromProvider($providerStatus));
    }

    /**
     * @return iterable<string, array{string, ?PaymentStatus}>
     */
    public static function providerStatuses(): iterable
    {
        yield 'success' => ['success', PaymentStatus::Completed];
        yield 'succeeded' => ['succeeded', PaymentStatus::Completed];
        yield 'paid' => ['paid', PaymentStatus::Completed];
        yield 'native completed' => ['completed', PaymentStatus::Completed];
        yield 'uppercase alias' => ['SUCCESS', PaymentStatus::Completed];
        yield 'trimmed' => ['  failed  ', PaymentStatus::Failed];
        yield 'failure' => ['failure', PaymentStatus::Failed];
        yield 'declined' => ['declined', PaymentStatus::Failed];
        yield 'canceled' => ['canceled', PaymentStatus::Cancelled];
        yield 'cancelled' => ['cancelled', PaymentStatus::Cancelled];
        yield 'refund' => ['refund', PaymentStatus::Refunded];
        yield 'processing' => ['processing', PaymentStatus::Processing];
        yield 'unknown' => ['foo', null];
        yield 'empty' => ['', null];
    }

    public function testPendingCanCompleteOrFail(): void
    {
        self::assertTrue(PaymentStatus::Pending->canTransitionTo(PaymentStatus::Completed));
        self::assertTrue(PaymentStatus::Pending->canTransitionTo(PaymentStatus::Failed));
        self::assertTrue(PaymentStatus::Pending->canTransitionTo(PaymentStatus::Cancelled));
        self::assertTrue(PaymentStatus::Pending->canTransitionTo(PaymentStatus::Processing));
        self::assertFalse(PaymentStatus::Pending->canTransitionTo(PaymentStatus::Refunded));
    }

    public function testProcessingCanCompleteFailOrCancel(): void
    {
        self::assertTrue(PaymentStatus::Processing->canTransitionTo(PaymentStatus::Completed));
        self::assertTrue(PaymentStatus::Processing->canTransitionTo(PaymentStatus::Failed));
        self::assertTrue(PaymentStatus::Processing->canTransitionTo(PaymentStatus::Cancelled));
        self::assertFalse(PaymentStatus::Processing->canTransitionTo(PaymentStatus::Pending));
    }
}
