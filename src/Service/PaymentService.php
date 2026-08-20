<?php
namespace App\Service;

use App\Infrastructure\Database;
use App\Infrastructure\Cache;
use App\Domain\PaymentStatus;
use App\Exception\PaymentException;

class PaymentService
{
    public function __construct(private Database $db, private Cache $cache) {}

    public function createPayment(string $idempotencyKey, int $userId, int $amountCents, string $currency, string $description): array
    {
        // TODO: Реализовать
        // 1. Проверка идемпотентности (cache → DB)
        // 2. Валидация
        // 3. INSERT в транзакции
        // 4. Обработка duplicate key (1062)
        // 5. Кэширование результата
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
}