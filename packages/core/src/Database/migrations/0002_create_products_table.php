<?php

declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            thumbnail VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            brand VARCHAR(255) NULL,
            category VARCHAR(255) NOT NULL,
            discount_percentage DECIMAL(5,2) NOT NULL,
            import_date DATETIME NOT NULL,
            remote_id BIGINT NOT NULL UNIQUE
        ) ENGINE=INNODB;");
    }
};
