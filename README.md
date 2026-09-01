# PuntoPago

HTTP JSON API для кошельков и платежей: пополнение, списание, создание платежа и обработка webhook от провайдера.

Стек: PHP 8.1+, MySQL 8, Memcached. Без фреймворка — тонкий роутинг в `public/index.php`, контроллеры, сервисы и валидация запросов в `src/`. Запускается через Docker Compose.

## Возможности

- **Кошелёк** — `POST /wallets/{id}/deposit` и `POST /wallets/{id}/withdraw`. Суммы в центах. Баланс меняется в транзакции с `SELECT … FOR UPDATE`, чтобы два параллельных списания не ушли в минус.
- **Платежи** — `POST /payments` создаёт запись со статусом `pending`. Повтор с тем же `Idempotency-Key` возвращает тот же платёж; другой payload с тем же ключом — конфликт.
- **Webhook** — `POST /webhooks/payment` принимает статус провайдера. Тело проверяется HMAC-SHA256 (`X-Signature`). Статусы переводятся по явным правилам (`pending` → `processing` / `completed` / `failed` / `cancelled`, `completed` → `refunded` и т.д.).
- **Идемпотентность** — уникальный ключ в БД (платежи и операции кошелька) плюс кэш платежей в Memcached. Гонка двух одинаковых запросов ловится по duplicate key и отдаёт уже созданную запись.
- **Health** — `GET /health` проверяет MySQL и Memcached.

Деньги хранятся как `BIGINT` в центах. Ошибки отдаются JSON с кодом (`INSUFFICIENT_FUNDS`, `INVALID_SIGNATURE`, `IDEMPOTENCY_CONFLICT` и др.).

## API

| Метод | Путь | Заголовки |
| --- | --- | --- |
| `GET` | `/health` | — |
| `POST` | `/wallets/{id}/deposit` | `Idempotency-Key` |
| `POST` | `/wallets/{id}/withdraw` | `Idempotency-Key` |
| `POST` | `/payments` | `Idempotency-Key` |
| `POST` | `/webhooks/payment` | `X-Signature` |

Тело запросов — JSON. Секрет подписи webhook задаётся переменной `WEBHOOK_SECRET`.

## Структура

```
src/
  Controller/     HTTP-слой
  Service/        платежи и кошелёк
  Domain/         статусы платежа
  Http/           разбор и валидация тела запроса
  Infrastructure/ PDO и Memcached
migrations/       схема MySQL (подхватывается при первом старте контейнера)
tests/            PHPUnit
```

---

## Инструкция

# 1. Собрать контейнеры
docker compose up -d --build

# 2. Установить зависимости
docker compose exec app composer install

# 3. Проверить, что БД поднялась
docker compose exec db mysql -uroot -psecret punto_pago -e "SHOW TABLES;"

# 4. Приложение доступно на http://localhost:8080

----------------------------------------------------------

# 1. Пополнить кошелёк пользователя 1 на $50
curl -X POST http://localhost:8080/wallets/1/deposit \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: dep-abc-123" \
  -d '{"amount_cents": 5000}'

# 2. Списать $20
curl -X POST http://localhost:8080/wallets/1/withdraw \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: wd-abc-123" \
  -d '{"amount_cents": 2000}'

# 3. Создать платёж (идемпотентно!)
curl -X POST http://localhost:8080/payments \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: pay-abc-123" \
  -d '{"user_id": 1, "amount_cents": 1500, "currency": "USD", "description": "Test payment"}'

# 4. Повторный запрос с тем же ключом → вернёт тот же платёж
curl -X POST http://localhost:8080/payments \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: pay-abc-123" \
  -d '{"user_id": 1, "amount_cents": 1500, "currency": "USD", "description": "Test payment"}'

# 5. Webhook с подписью (HMAC-SHA256)
# Сначала посчитай подпись: echo -n '{"payment_id":1,"status":"success","idempotency_key":"pay-abc-123"}' | openssl dgst -sha256 -hmac "super-secret-key-123"
curl -X POST http://localhost:8080/webhooks/payment \
  -H "Content-Type: application/json" \
  -H "X-Signature: <тут хэш>" \
  -d '{"payment_id":1,"status":"success","idempotency_key":"pay-abc-123"}'
