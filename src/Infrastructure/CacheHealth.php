<?php
namespace App\Infrastructure;

final class CacheHealth
{
    /**
     * @param array<string, mixed>|false|null $raw Result of Memcached::getStats()
     * @return array{
     *     status: string,
     *     uptime?: int,
     *     curr_items?: int,
     *     cmd_get?: int,
     *     cmd_set?: int,
     *     get_hits?: int,
     *     get_misses?: int,
     *     hit_rate?: float|null
     * }
     */
    public static function summarize(array|false|null $raw): array
    {
        if (!is_array($raw) || $raw === []) {
            return ['status' => 'error'];
        }

        $server = reset($raw);
        if (!is_array($server) || !isset($server['pid'])) {
            return ['status' => 'error'];
        }

        $cmdGet = (int) ($server['cmd_get'] ?? 0);
        $hits   = (int) ($server['get_hits'] ?? 0);

        return [
            'status'     => 'ok',
            'uptime'     => (int) ($server['uptime'] ?? 0),
            'curr_items' => (int) ($server['curr_items'] ?? 0),
            'cmd_get'    => $cmdGet,
            'cmd_set'    => (int) ($server['cmd_set'] ?? 0),
            'get_hits'   => $hits,
            'get_misses' => (int) ($server['get_misses'] ?? 0),
            'hit_rate'   => $cmdGet > 0 ? round($hits / $cmdGet, 4) : null,
        ];
    }
}
