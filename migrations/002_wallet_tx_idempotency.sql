ALTER TABLE wallet_transactions
    ADD COLUMN idempotency_key VARCHAR(64) NULL,
    ADD UNIQUE KEY uniq_idempotency (idempotency_key);
