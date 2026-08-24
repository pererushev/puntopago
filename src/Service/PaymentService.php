<?php
namespace App\Service;

use App\Infrastructure\Database;
use App\Infrastructure\Cache;
use App\Domain\PaymentStatus;
use App\Exception\PaymentException;

class PaymentService
{
    private const CACHE_PREFIX = 'payment:';
    private const CACHE_TTL = 300;
    private const DUPLICATE_KEY = 1062;

    public function __construct(private Database $db, private Cache $cache) {}

    public function createPayment(string $idempotencyKey, int $userId, int $amountCents, string $currency, string $description): array
    {
        if ($userId <= 0 || $amountCents <= 0) {
            throw new PaymentException('user_id and amount_cents must be positive integers');
        }

        $cacheKey = self::CACHE_PREFIX . $idempotencyKey;

        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            $this->assertSameRequest($cached, $userId, $amountCents, $currency);
            return $cached;
        }

        $existing = $this->findByIdempotencyKey($idempotencyKey);
        if ($existing !== null) {
            $this->assertSameRequest($existing, $userId, $amountCents, $currency);
            $this->cache->set($cacheKey, $existing, self::CACHE_TTL);
            return $existing;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->pdo()->prepare(
                'INSERT INTO payments (idempotency_key, user_id, amount_cents, currency, description, status)
                 VALUES (:idempotency_key, :user_id, :amount_cents, :currency, :description, :status)'
            );
            $stmt->execute([
                'idempotency_key' => $idempotencyKey,
                'user_id'         => $userId,
                'amount_cents'    => $amountCents,
                'currency'        => $currency,
                'description'     => $description === '' ? null : $description,
                'status'          => PaymentStatus::Pending->value,
            ]);

            $payment = $this->findById((int) $this->db->pdo()->lastInsertId());
            if ($payment === null) {
                $this->db->rollBack();
                throw new PaymentException('Failed to load created payment', 500);
            }

            $this->db->commit();
        } catch (\PDOException $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }

            if ((int) ($e->errorInfo[1] ?? 0) !== self::DUPLICATE_KEY) {
                throw $e;
            }

            $payment = $this->findByIdempotencyKey($idempotencyKey);
            if ($payment === null) {
                throw $e;
            }

            $this->assertSameRequest($payment, $userId, $amountCents, $currency);
            $this->cache->set($cacheKey, $payment, self::CACHE_TTL);

            return $payment;
        }

        $this->cache->set($cacheKey, $payment, self::CACHE_TTL);

        return $payment;
    }

    public function handleWebhook(array $payload, string $signature, string $secret): array
    {
        // TODO: Реализовать
        // 1. Проверка HMAC-SHA256 подписи через hash_equals
        // 2. Маппинг статусов провайдера → PaymentStatus
        // 3. SELECT ... FOR UPDATE
        // 4. Проверка canTransitionTo()
        // 5. UPDATE статуса
    }

    private function findByIdempotencyKey(string $key): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM payments WHERE idempotency_key = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->mapPayment($row);
    }

    private function findById(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->mapPayment($row);
    }

    private function assertSameRequest(array $payment, int $userId, int $amountCents, string $currency): void
    {
        if (
            (int) $payment['user_id'] !== $userId
            || (int) $payment['amount_cents'] !== $amountCents
            || $payment['currency'] !== $currency
        ) {
            throw new PaymentException('Idempotency-Key already used with different parameters', 409);
        }
    }

    private function mapPayment(array $row): array
    {
        return [
            'id'              => (int) $row['id'],
            'idempotency_key' => $row['idempotency_key'],
            'user_id'         => (int) $row['user_id'],
            'amount_cents'    => (int) $row['amount_cents'],
            'currency'        => $row['currency'],
            'description'     => $row['description'],
            'status'          => $row['status'],
            'provider_ref'    => $row['provider_ref'],
            'created_at'      => $row['created_at'],
            'updated_at'      => $row['updated_at'],
        ];
    }
}