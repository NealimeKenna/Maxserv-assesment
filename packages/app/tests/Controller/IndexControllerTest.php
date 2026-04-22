<?php

declare(strict_types=1);

namespace MaxServ\app\tests\Controller;

use MaxServ\App\Controller\IndexController;
use MaxServ\Core\Database\Connection;
use MaxServ\Core\Render\TemplateRenderer;
use PHPUnit\Framework\TestCase;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class IndexControllerTest extends TestCase
{
    private Connection $connection;
    private TemplateRenderer $templateRenderer;

    protected function setUp(): void
    {
        $this->connection = new Connection(
            getenv('DB_HOST'),
            getenv('DB_USER'),
            getenv('DB_PASSWORD'),
            getenv('DB_DATABASE')
        );

        $pdo = $this->connection->getConnection();

        /** @noinspection SqlWithoutWhere */
        $pdo->exec("DELETE FROM products");

        $this->templateRenderer = $this->createMock(TemplateRenderer::class);
    }

    private function insertProduct(string $title, float $price, ?string $brand, string $category): void
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("INSERT INTO products (thumbnail, title, price, brand, category, discount_percentage, import_date, remote_id)
                               VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
        static $remoteId = 1;
        $stmt->execute(['thumb.jpg', $title, $price, $brand, $category, 10.0, $remoteId++]);
    }

    private function assertTemplateRendered(callable $callback): void
    {
        $this->templateRenderer->expects($this->once())
            ->method('render')
            ->with('index.html.twig', $this->callback($callback))
            ->willReturn('rendered_content');
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    private function executeIndexAndAssertRendered(): void
    {
        $controller = new IndexController($this->templateRenderer, $this->connection);

        ob_start();
        $controller->index();
        $output = ob_get_clean();

        $this->assertEquals('rendered_content', $output);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function testIndexDisplaysProducts(): void
    {
        $this->insertProduct('Product A', 100.0, 'Brand X', 'Cat 1');
        $this->insertProduct('Product B', 200.0, 'Brand Y', 'Cat 2');

        $this->assertTemplateRendered(function ($args) {
            return count($args['products']) === 2 &&
                   $args['brands'] === ['Brand X', 'Brand Y'] &&
                   $args['categories'] === ['Cat 1', 'Cat 2'];
        });

        $this->executeIndexAndAssertRendered();
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function testIndexFiltersByBrand(): void
    {
        $this->insertProduct('Product A', 100.0, 'Brand X', 'Cat 1');
        $this->insertProduct('Product B', 200.0, 'Brand Y', 'Cat 2');

        $_GET['filters'] = ['brand' => 'Brand X'];

        $this->assertTemplateRendered(function ($args) {
            return count($args['products']) === 1 &&
                   $args['products'][0]['title'] === 'Product A';
        });

        $this->executeIndexAndAssertRendered();
        unset($_GET['filters']);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function testIndexFiltersByCategory(): void
    {
        $this->insertProduct('Product A', 100.0, 'Brand X', 'Cat 1');
        $this->insertProduct('Product B', 200.0, 'Brand Y', 'Cat 2');

        $_GET['filters'] = ['category' => 'Cat 2'];

        $this->assertTemplateRendered(function ($args) {
            return count($args['products']) === 1 &&
                   $args['products'][0]['title'] === 'Product B';
        });

        $this->executeIndexAndAssertRendered();
        unset($_GET['filters']);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function testIndexSortsByPriceAsc(): void
    {
        $this->insertProduct('Product A', 200.0, 'Brand X', 'Cat 1');
        $this->insertProduct('Product B', 100.0, 'Brand Y', 'Cat 2');

        $_GET['sort'] = ['field' => 'price', 'order' => 'asc'];

        $this->assertTemplateRendered(function ($args) {
            return count($args['products']) === 2 &&
                   $args['products'][0]['title'] === 'Product B' &&
                   $args['products'][1]['title'] === 'Product A';
        });

        $this->executeIndexAndAssertRendered();
        unset($_GET['sort']);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function testIndexSortsByPriceDesc(): void
    {
        $this->insertProduct('Product A', 100.0, 'Brand X', 'Cat 1');
        $this->insertProduct('Product B', 200.0, 'Brand Y', 'Cat 2');

        $_GET['sort'] = ['field' => 'price', 'order' => 'desc'];

        $this->assertTemplateRendered(function ($args) {
            return count($args['products']) === 2 &&
                   $args['products'][0]['title'] === 'Product B' &&
                   $args['products'][1]['title'] === 'Product A';
        });

        $this->executeIndexAndAssertRendered();
        unset($_GET['sort']);
    }
}
