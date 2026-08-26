<?php
namespace App\Service;

use App\Infrastructure\Database;
use App\Exception\ErrorCode;
use App\Exception\PaymentException;

class WalletService
{
    private const DUPLICATE_KEY = 1062;

    public function __construct(private Database $db) {}

    public function deposit(int $walletId, int $amountCents, string $idempotencyKey): array
    {
        if ($walletId <= 0) {
            throw new PaymentException('wallet_id must be a positive integer');
        }
        if ($amountCents <= 0) {
            throw new PaymentException('amount_cents must be a positive integer');
        }

        $existing = $this->findTransactionByIdempotencyKey($idempotencyKey);
        if ($existing !== null) {
            $this->assertSameTransaction($existing, $walletId, $amountCents, 'deposit');
            return $this->requireWallet($walletId);
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->pdo()->prepare(
                'SELECT id, user_id, currency, balance_cents, created_at, updated_at
                 FROM wallets WHERE id = :id FOR UPDATE'
            );
            $stmt->execute(['id' => $walletId]);
            $wallet = $stmt->fetch();
            if ($wallet === false) {
                throw new PaymentException('wallet not found', ErrorCode::WalletNotFound);
            }

            $balanceCents = (int) $wallet['balance_cents'];
            if ($balanceCents > 0 && $amountCents > PHP_INT_MAX - $balanceCents) {
                throw new PaymentException('balance overflow', ErrorCode::BalanceOverflow);
            }
            $balanceAfterCents = $balanceCents + $amountCents;

            $stmt = $this->db->pdo()->prepare(
                'INSERT INTO wallet_transactions (wallet_id, amount_cents, type, balance_after_cents, idempotency_key)
                 VALUES (:wallet_id, :amount_cents, :type, :balance_after_cents, :idempotency_key)'
            );
            $stmt->execute([
                'wallet_id'           => $walletId,
                'amount_cents'        => $amountCents,
                'type'                => 'deposit',
                'balance_after_cents' => $balanceAfterCents,
                'idempotency_key'     => $idempotencyKey,
            ]);

            $stmt = $this->db->pdo()->prepare(
                'UPDATE wallets SET balance_cents = balance_cents + :amount WHERE id = :id'
            );
            $stmt->execute(['amount' => $amountCents, 'id' => $walletId]);

            $wallet = $this->findById($walletId);
            if ($wallet === null) {
                throw new PaymentException('Failed to load updated wallet', ErrorCode::InternalError);
            }

            $this->db->commit();
        } catch (\PDOException $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }

            if ((int) ($e->errorInfo[1] ?? 0) !== self::DUPLICATE_KEY) {
                throw $e;
            }

            $existing = $this->findTransactionByIdempotencyKey($idempotencyKey);
            if ($existing === null) {
                throw $e;
            }

            $this->assertSameTransaction($existing, $walletId, $amountCents, 'deposit');

            return $this->requireWallet($walletId);
        } catch (\Throwable $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return $wallet;
    }

    public function withdraw(int $walletId, int $amountCents, string $idempotencyKey): array
    {
        if ($walletId <= 0) {
            throw new PaymentException('wallet_id must be a positive integer');
        }
        if ($amountCents <= 0) {
            throw new PaymentException('amount_cents must be a positive integer');
        }

        $existing = $this->findTransactionByIdempotencyKey($idempotencyKey);
        if ($existing !== null) {
            $this->assertSameTransaction($existing, $walletId, $amountCents, 'withdraw');
            return $this->requireWallet($walletId);
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->pdo()->prepare(
                'SELECT id, user_id, currency, balance_cents, created_at, updated_at
                 FROM wallets WHERE id = :id FOR UPDATE'
            );
            $stmt->execute(['id' => $walletId]);
            $wallet = $stmt->fetch();
            if ($wallet === false) {
                throw new PaymentException('wallet not found', ErrorCode::WalletNotFound);
            }

            $existing = $this->findTransactionByIdempotencyKey($idempotencyKey, forUpdate: true);
            if ($existing !== null) {
                $this->assertSameTransaction($existing, $walletId, $amountCents, 'withdraw');
                $this->db->commit();
                return $this->mapWallet($wallet);
            }

            $balanceCents = (int) $wallet['balance_cents'];
            if ($balanceCents < $amountCents) {
                throw new PaymentException('insufficient funds', ErrorCode::InsufficientFunds);
            }
            $balanceAfterCents = $balanceCents - $amountCents;

            $stmt = $this->db->pdo()->prepare(
                'INSERT INTO wallet_transactions (wallet_id, amount_cents, type, balance_after_cents, idempotency_key)
                 VALUES (:wallet_id, :amount_cents, :type, :balance_after_cents, :idempotency_key)'
            );
            $stmt->execute([
                'wallet_id'           => $walletId,
                'amount_cents'        => $amountCents,
                'type'                => 'withdraw',
                'balance_after_cents' => $balanceAfterCents,
                'idempotency_key'     => $idempotencyKey,
            ]);

            $stmt = $this->db->pdo()->prepare(
                'UPDATE wallets
                 SET balance_cents = balance_cents - :amount
                 WHERE id = :id AND balance_cents >= :min_balance'
            );
            $stmt->execute([
                'amount'      => $amountCents,
                'id'          => $walletId,
                'min_balance' => $amountCents,
            ]);
            if ($stmt->rowCount() === 0) {
                throw new PaymentException('insufficient funds', ErrorCode::InsufficientFunds);
            }

            $wallet = $this->findById($walletId);
            if ($wallet === null) {
                throw new PaymentException('Failed to load updated wallet', ErrorCode::InternalError);
            }

            $this->db->commit();
        } catch (\PDOException $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }

            if ((int) ($e->errorInfo[1] ?? 0) !== self::DUPLICATE_KEY) {
                throw $e;
            }

            $existing = $this->findTransactionByIdempotencyKey($idempotencyKey);
            if ($existing === null) {
                throw $e;
            }

            $this->assertSameTransaction($existing, $walletId, $amountCents, 'withdraw');

            return $this->requireWallet($walletId);
        } catch (\Throwable $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return $wallet;
    }

    private function requireWallet(int $walletId): array
    {
        $wallet = $this->findById($walletId);
        if ($wallet === null) {
            throw new PaymentException('wallet not found', ErrorCode::WalletNotFound);
        }

        return $wallet;
    }

    private function findById(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT id, user_id, currency, balance_cents, created_at, updated_at
             FROM wallets WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->mapWallet($row);
    }

    private function findTransactionByIdempotencyKey(string $key, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT wallet_id, amount_cents, type FROM wallet_transactions WHERE idempotency_key = :key LIMIT 1';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    private function assertSameTransaction(array $transaction, int $walletId, int $amountCents, string $type): void
    {
        if (
            (int) $transaction['wallet_id'] !== $walletId
            || (int) $transaction['amount_cents'] !== $amountCents
            || $transaction['type'] !== $type
        ) {
            throw new PaymentException('Idempotency-Key already used with different parameters', ErrorCode::IdempotencyConflict);
        }
    }

    private function mapWallet(array $row): array
    {
        return [
            'id'            => (int) $row['id'],
            'user_id'       => (int) $row['user_id'],
            'currency'      => $row['currency'],
            'balance_cents' => (int) $row['balance_cents'],
            'created_at'    => $row['created_at'],
            'updated_at'    => $row['updated_at'],
        ];
    }
}
