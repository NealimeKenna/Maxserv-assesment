<?php

declare(strict_types=1);

namespace MaxServ\Core\Service;

use MaxServ\Core\Database\Connection;
use PDO;
use PDOException;

readonly class MigrateService
{
    public function __construct(
        private Connection $connection,
        private string     $migrationPath
    ) {
    }

    public function migrate(?string $database = null): array
    {
        $connection = $this->connection;
        if ($database !== null) {
            $connection = $this->connection->withDatabase($database);
        }

        $appliedMigrations = $this->getAppliedMigrations($connection);
        $migrationFiles = $this->getMigrationFiles();

        $executed = [];

        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.php');

            if (!in_array($migrationName, $appliedMigrations)) {
                $this->runMigration($connection, $file, $migrationName);
                $executed[] = $migrationName;
            }
        }

        return $executed;
    }

    private function getAppliedMigrations(Connection $connection): array
    {
        try {
            $stmt = $connection->getConnection()->query("SELECT migration FROM migrations");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException) {
            return [];
        }
    }

    private function getMigrationFiles(): array
    {
        $files = glob($this->migrationPath . '/*.php');
        sort($files);
        return $files;
    }

    private function runMigration(Connection $connection, string $file, string $migrationName): void
    {
        $migration = require_once $file;
        
        if (is_object($migration) && method_exists($migration, 'up')) {
            $migration->up($connection->getConnection());

            $stmt = $connection->getConnection()->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migrationName]);
        }
    }
}
