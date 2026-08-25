<?php
namespace App\Service;

use App\Infrastructure\Database;
use App\Exception\PaymentException;

class WalletService
{
    public function __construct(private Database $db) {}

    public function deposit(int $walletId, int $amountCents): array
    {
        if ($walletId <= 0 || $amountCents <= 0) {
            throw new PaymentException('wallet_id and amount_cents must be positive integers');
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->pdo()->prepare(
                'SELECT * FROM wallets WHERE id = :id FOR UPDATE'
            );
            $stmt->execute(['id' => $walletId]);
            $wallet = $stmt->fetch();
            if ($wallet === false) {
                throw new PaymentException('wallet not found');
            }

            $balanceCents = (int) $wallet['balance_cents'];
            if ($balanceCents > 0 && $amountCents > PHP_INT_MAX - $balanceCents) {
                throw new PaymentException('balance overflow');
            }
            $balanceAfterCents = $balanceCents + $amountCents;

            $stmt = $this->db->pdo()->prepare(
                'UPDATE wallets SET balance_cents = balance_cents + :amount WHERE id = :id'
            );
            $stmt->execute(['amount' => $amountCents, 'id' => $walletId]);

            $stmt = $this->db->pdo()->prepare(
                'INSERT INTO wallet_transactions (wallet_id, amount_cents, type, balance_after_cents)
                 VALUES (:wallet_id, :amount_cents, :type, :balance_after_cents)'
            );
            $stmt->execute([
                'wallet_id'           => $walletId,
                'amount_cents'        => $amountCents,
                'type'                => 'deposit',
                'balance_after_cents' => $balanceAfterCents,
            ]);

            $wallet = $this->findById($walletId);
            if ($wallet === null) {
                throw new PaymentException('Failed to load updated wallet', 500);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return $wallet;
    }

    public function withdraw(int $walletId, int $amountCents): array
    {
        // TODO: Реализовать
        // 1. Валидация
        // 2. SELECT ... FOR UPDATE
        // 3. Проверка sufficient funds
        // 4. UPDATE balance_cents = balance_cents - amount
        // 5. INSERT в wallet_transactions
    }

    private function findById(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM wallets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->mapWallet($row);
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
