<?php
namespace App\Controller;

use App\Infrastructure\Cache;
use App\Infrastructure\CacheHealth;
use App\Infrastructure\Database;

class HealthController
{
    public function __construct(private Database $db, private Cache $cache) {}

    public function show(): void
    {
        $memcached = CacheHealth::summarize($this->cache->getStats());
        $dbOk      = $this->db->ping();
        $ok        = $memcached['status'] === 'ok' && $dbOk;

        http_response_code($ok ? 200 : 503);
        echo json_encode([
            'status' => $ok ? 'ok' : 'error',
            'checks' => [
                'database'  => ['status' => $dbOk ? 'ok' : 'error'],
                'memcached' => $memcached,
            ],
        ]);
    }
}
