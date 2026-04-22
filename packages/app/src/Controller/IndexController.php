<?php

declare(strict_types=1);

namespace MaxServ\App\Controller;

use MaxServ\Core\Database\Connection;
use MaxServ\Core\Render\TemplateRenderer;
use MaxServ\Core\Traits\Filterable;
use MaxServ\Core\Traits\Sortable;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

readonly class IndexController
{
    use Filterable;
    use Sortable;

    private const ALLOWED_FILTERS = ['brand', 'category'];
    private const ALLOWED_SORT_FIELDS = ['title', 'price', 'brand', 'category'];

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
        $pdo = $this->connection->getConnection();

        $sql = "SELECT thumbnail, title, price, discount_percentage, brand, category FROM products";
        $params = [];

        $this->applyFilters($request, self::ALLOWED_FILTERS, $sql, $params);
        $this->applySorting($request, self::ALLOWED_SORT_FIELDS, $sql);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $brands = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);
        $categories = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

        echo $this->templateRenderer->render('index.html.twig', [
            'products' => $products,
            'brands' => $brands,
            'categories' => $categories,
            'filters' => $request->query->all('filters'),
            'sort' => $request->query->all('sort')
        ]);
    }
}
