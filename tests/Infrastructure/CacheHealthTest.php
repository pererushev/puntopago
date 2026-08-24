<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use App\Infrastructure\CacheHealth;
use PHPUnit\Framework\TestCase;

final class CacheHealthTest extends TestCase
{
    public function testSummarizeReturnsErrorWhenStatsMissing(): void
    {
        self::assertSame(['status' => 'error'], CacheHealth::summarize(null));
        self::assertSame(['status' => 'error'], CacheHealth::summarize(false));
        self::assertSame(['status' => 'error'], CacheHealth::summarize([]));
        self::assertSame(['status' => 'error'], CacheHealth::summarize(['memcached:11211' => false]));
    }

    public function testSummarizeMapsMemcachedStats(): void
    {
        $summary = CacheHealth::summarize([
            'memcached:11211' => [
                'pid'        => 1,
                'uptime'     => 120,
                'curr_items' => 3,
                'cmd_get'    => 10,
                'cmd_set'    => 4,
                'get_hits'   => 8,
                'get_misses' => 2,
            ],
        ]);

        self::assertSame('ok', $summary['status']);
        self::assertSame(120, $summary['uptime']);
        self::assertSame(3, $summary['curr_items']);
        self::assertSame(10, $summary['cmd_get']);
        self::assertSame(4, $summary['cmd_set']);
        self::assertSame(8, $summary['get_hits']);
        self::assertSame(2, $summary['get_misses']);
        self::assertSame(0.8, $summary['hit_rate']);
    }

    public function testHitRateIsNullWhenThereWereNoGets(): void
    {
        $summary = CacheHealth::summarize([
            'memcached:11211' => [
                'pid'        => 1,
                'uptime'     => 1,
                'curr_items' => 0,
                'cmd_get'    => 0,
                'cmd_set'    => 0,
                'get_hits'   => 0,
                'get_misses' => 0,
            ],
        ]);

        self::assertSame('ok', $summary['status']);
        self::assertNull($summary['hit_rate']);
    }
}
