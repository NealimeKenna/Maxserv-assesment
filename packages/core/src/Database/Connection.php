<?php

declare(strict_types=1);

namespace MaxServ\Core\Database;

use PDO;
use PDOException;

readonly class Connection
{
    private PDO $pdo;

    public function __construct(
        private string $host,
        private string $user,
        private string $password,
        private string $database
    ) {
        $this->pdo = $this->createPdo($this->database);
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    public function withDatabase(string $database): self
    {
        return new self($this->host, $this->user, $this->password, $database);
    }

    private function createPdo(string $database): PDO
    {
        try {
            $dsn = "mysql:host=$this->host;dbname=$database;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            return new PDO($dsn, $this->user, $this->password, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
}
