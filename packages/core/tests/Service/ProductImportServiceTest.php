<?php

declare(strict_types=1);

namespace MaxServ\Core\Tests\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use MaxServ\Core\Database\Connection;
use MaxServ\Core\Service\ProductImportService;
use PHPUnit\Framework\TestCase;

class ProductImportServiceTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = new Connection(
            getenv('DB_HOST'),
            getenv('DB_USER'),
            getenv('DB_PASSWORD'),
            getenv('DB_DATABASE')
        );

        /** @noinspection SqlWithoutWhere */
        $this->connection->getConnection()->exec("DELETE FROM `products`");
    }

    /**
     * @throws GuzzleException
     */
    public function testImportNextBatch(): void
    {
        $mockClient = $this->createMock(Client::class);
        
        $productsData = [
            'products' => [
                [
                    'id' => 1,
                    'title' => 'Product 1',
                    'description' => 'Description 1',
                    'price' => 10.5,
                    'discountPercentage' => 5.0,
                    'brand' => 'Brand 1',
                    'category' => 'Category 1',
                    'thumbnail' => 'thumb1.jpg'
                ],
                [
                    'id' => 2,
                    'title' => 'Product 2',
                    'description' => 'Description 2',
                    'price' => 20.0,
                    'discountPercentage' => 10.0,
                    'brand' => 'Brand 2',
                    'category' => 'Category 2',
                    'thumbnail' => 'thumb2.jpg'
                ]
            ]
        ];

        $mockClient->expects($this->once())
            ->method('get')
            ->with('https://dummyjson.com/products', [
                'query' => [
                    'limit' => 100,
                    'skip' => 0,
                ]
            ])
            ->willReturn(new Response(200, [], json_encode($productsData)));

        $service = new ProductImportService($this->connection, $mockClient);
        $result = $service->importNextBatch();

        $this->assertEquals(2, $result['imported']);
        $this->assertEmpty($result['skipped']);

        $pdo = $this->connection->getConnection();
        $stmt = $pdo->query("SELECT * FROM products ORDER BY remote_id");
        $results = $stmt->fetchAll();

        $this->assertCount(2, $results);
        $this->assertEquals('Product 1', $results[0]['title']);
        $this->assertEquals(1, $results[0]['remote_id']);
        $this->assertEquals('Product 2', $results[1]['title']);
        $this->assertEquals(2, $results[1]['remote_id']);
    }

    /**
     * @throws GuzzleException
     */
    public function testImportNextBatchSkipsExisting(): void
    {
        $pdo = $this->connection->getConnection();
        $pdo->exec("INSERT INTO products (thumbnail, title, price, brand, category, discount_percentage, import_date, remote_id)
                    VALUES ('t', 'Old Product', 10, 'B', 'C', 0, NOW(), 5)");

        $mockClient = $this->createMock(Client::class);
        
        $productsData = [
            'products' => [
                [
                    'id' => 6,
                    'title' => 'Product 6',
                    'price' => 10,
                    'discountPercentage' => 0,
                    'brand' => 'B',
                    'category' => 'C',
                    'thumbnail' => 't'
                ]
            ]
        ];

        $mockClient->expects($this->once())
            ->method('get')
            ->with('https://dummyjson.com/products', [
                'query' => [
                    'limit' => 100,
                    'skip' => 5,
                ]
            ])
            ->willReturn(new Response(200, [], json_encode($productsData)));

        $service = new ProductImportService($this->connection, $mockClient);
        $result = $service->importNextBatch();

        $this->assertEquals(1, $result['imported']);
        $this->assertEmpty($result['skipped']);
        
        $stmt = $pdo->query("SELECT MAX(remote_id) FROM products");
        $this->assertEquals(6, $stmt->fetchColumn());
    }

    /**
     * @throws GuzzleException
     */
    public function testImportNextBatchSkipsInvalid(): void
    {
        $mockClient = $this->createMock(Client::class);
        
        $productsData = [
            'products' => [
                [
                    'id' => 10,
                    'title' => 'Valid Product',
                    'price' => 10,
                    'discountPercentage' => 0,
                    'brand' => 'B',
                    'category' => 'C',
                    'thumbnail' => 't'
                ],
                [
                    'id' => 11,
                    // Missing title
                    'price' => 20,
                    'discountPercentage' => 0,
                    'brand' => 'B',
                    'category' => 'C',
                    'thumbnail' => 't'
                ],
                [
                    // Missing id
                    'title' => 'Invalid Product',
                    'price' => 30,
                    'discountPercentage' => 0,
                    'brand' => 'B',
                    'category' => 'C',
                    'thumbnail' => 't'
                ]
            ]
        ];

        $mockClient->expects($this->once())
            ->method('get')
            ->willReturn(new Response(200, [], json_encode($productsData)));

        $service = new ProductImportService($this->connection, $mockClient);
        $result = $service->importNextBatch();

        $this->assertEquals(1, $result['imported']);
        $this->assertCount(2, $result['skipped']);
        $this->assertEquals(11, $result['skipped'][0]['id']);
        $this->assertStringContainsString('title', $result['skipped'][0]['reason']);
        $this->assertEquals('unknown', $result['skipped'][1]['id']);
        $this->assertStringContainsString('id', $result['skipped'][1]['reason']);
        
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->query("SELECT * FROM products");
        $results = $stmt->fetchAll();

        $this->assertCount(1, $results);
        $this->assertEquals('Valid Product', $results[0]['title']);
    }
}
