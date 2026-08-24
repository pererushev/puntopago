<?php
namespace App\Infrastructure;

use App\Config\DatabaseConfig;
use PDO;

class Database
{
    private PDO $pdo;

    public function __construct(DatabaseConfig $config)
    {
        $this->pdo = new PDO($config->dsn(), $config->user, $config->password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public function pdo(): PDO { return $this->pdo; }

    public function beginTransaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void           { $this->pdo->commit(); }
    public function rollBack(): void         { $this->pdo->rollBack(); }
}