<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$host = getenv('DB_HOST');
$password = getenv('DB_ROOT_PASSWORD');
$rootUser = getenv('DB_ROOT_USER');
$database = getenv('DB_DATABASE');
$appUser = getenv('DB_USER');

try {
    $pdo = new PDO("mysql:host=$host", $rootUser, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` ");
    $pdo->exec("GRANT ALL PRIVILEGES ON `$database`.* TO '$appUser'@'%'");
    $pdo->exec("FLUSH PRIVILEGES");

    $migrationPath = APPLICATION_ROOT . '/packages/core/src/Database/migrations';
    $files = glob($migrationPath . '/*.php');
    sort($files);

    $pdoTest = new PDO("mysql:host=$host;dbname=$database", $rootUser, $password);
    $pdoTest->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    foreach ($files as $file) {
        $migrationName = basename($file, '.php');
        $alreadyApplied = false;

        try {
            $stmt = $pdoTest->prepare("SELECT 1 FROM migrations WHERE migration = ?");
            $stmt->execute([$migrationName]);
            $alreadyApplied = (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {

        }

        if (!$alreadyApplied) {
            $migration = require $file;

            if (is_object($migration) && method_exists($migration, 'up')) {
                try {
                    $migration->up($pdoTest);

                    $pdoTest->exec("CREATE TABLE IF NOT EXISTS migrations (migration VARCHAR(255) PRIMARY KEY) ENGINE=INNODB");
                    $stmt = $pdoTest->prepare("INSERT INTO migrations (migration) VALUES (?)");
                    $stmt->execute([$migrationName]);
                } catch (PDOException $e) {
                    if (str_contains($e->getMessage(), 'Duplicate column name') || str_contains($e->getMessage(), 'already exists')) {
                         continue;
                    }

                    throw $e;
                }
            }
        }
    }
} catch (PDOException $e) {
    echo "Could not create test database: " . $e->getMessage() . "\n";
    exit(1);
}
