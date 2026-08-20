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
  -d '{"amount_cents": 5000}'

# 2. Списать $20
curl -X POST http://localhost:8080/wallets/1/withdraw \
  -H "Content-Type: application/json" \
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
# Сначала посчитай подпись: echo -n '{"payment_id":1,"status":"success"}' | openssl dgst -sha256 -hmac "super-secret-key-123"
curl -X POST http://localhost:8080/webhooks/payment \
  -H "Content-Type: application/json" \
  -H "X-Signature: <тут хэш>" \
  -d '{"payment_id":1,"status":"success","idempotency_key":"pay-abc-123"}'