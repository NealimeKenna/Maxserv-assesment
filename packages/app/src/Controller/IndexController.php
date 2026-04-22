<?php

declare(strict_types=1);

namespace MaxServ\App\Controller;

use MaxServ\Core\Database\Connection;
use MaxServ\Core\Render\TemplateRenderer;
use PDO;
use Symfony\Component\HttpFoundation\Request;
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
        $request = Request::createFromGlobals();
        $filters = $request->query->all('filters');

        $pdo = $this->connection->getConnection();

        $sql = "SELECT thumbnail, title, price, discount_percentage, brand, category FROM products";
        $where = [];
        $params = [];

        $allowedFilters = ['brand', 'category'];

        foreach ($filters as $key => $value) {
            if (in_array($key, $allowedFilters) && $value) {
                $where[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $brands = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);
        $categories = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

        echo $this->templateRenderer->render('index.html.twig', [
            'products' => $products,
            'brands' => $brands,
            'categories' => $categories,
            'filters' => $filters
        ]);
    }
}
