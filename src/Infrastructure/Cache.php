<?php
namespace App\Infrastructure;

use Memcached;

class Cache
{
    private Memcached $mc;

    public function __construct(string $host)
    {
        $this->mc = new Memcached();
        $this->mc->addServer($host, 11211);
    }

    public function get(string $key): mixed
    {
        $v = $this->mc->get($key);
        return $this->mc->getResultCode() === Memcached::RES_SUCCESS ? $v : null;
    }

    public function set(string $key, mixed $value, int $ttl = 300): bool
    {
        return $this->mc->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->mc->delete($key);
    }

    /** @return array<string, mixed>|false */
    public function getStats(): array|false
    {
        $stats = $this->mc->getStats();
        return is_array($stats) ? $stats : false;
    }
}