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
} catch (PDOException $e) {
    echo "Could not create test database: " . $e->getMessage() . "\n";
    exit(1);
}
