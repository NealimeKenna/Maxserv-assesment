<?php

declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE products ADD COLUMN raw_data JSON NULL;");
    }
};
