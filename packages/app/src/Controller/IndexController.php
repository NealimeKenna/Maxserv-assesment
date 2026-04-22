<?php

declare(strict_types=1);

namespace MaxServ\App\Controller;

use MaxServ\Core\Database\Connection;
use MaxServ\Core\Render\TemplateRenderer;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

readonly class IndexController
{
    public function __construct(
        private TemplateRenderer $templateRenderer,
        private Connection $connection
    ) {
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function index(): void
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->query("SELECT thumbnail, title, price, discount_percentage, brand, category FROM products ORDER BY id DESC");
        $products = $stmt->fetchAll();

        echo $this->templateRenderer->render('index.html.twig', [
            'products' => $products
        ]);
    }
}
