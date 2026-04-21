<?php

declare(strict_types=1);

namespace MaxServ\packages\core\tests\Database;

use MaxServ\Core\Database\Connection;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    private string $host;
    private string $user;
    private string $password;
    private string $database;

    protected function setUp(): void
    {
        $this->host = getenv('DB_HOST');
        $this->user = getenv('DB_USER');
        $this->password = getenv('DB_PASSWORD');
        $this->database = getenv('DB_DATABASE');
    }

    public function testConnectionInstantiatesAndReturnsPdo(): void
    {
        $connection = new Connection(
            $this->host,
            $this->user,
            $this->password,
            $this->database
        );

        $pdo = $connection->getConnection();

        $this->assertEquals('mysql', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    public function testConnectionThrowsExceptionOnInvalidCredentials(): void
    {
        $this->expectException(PDOException::class);

        try {
            new Connection(
                $this->host,
                'invalid_user',
                'invalid_password',
                $this->database
            );
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
}
