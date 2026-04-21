<?php

declare(strict_types=1);

namespace MaxServ\Core\Service;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use MaxServ\Core\Database\Connection;

class ProductImportService
{
    private const URL = 'https://dummyjson.com/products';

    public function __construct(
        private readonly Connection $connection,
        private readonly Client     $client
    ) {
    }

    /**
     * @throws GuzzleException
     */
    public function importNextBatch(int $limit = 100): array
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->query("SELECT MAX(remote_id) FROM products");
        $maxRemoteId = $stmt->fetchColumn();
        $skip = $maxRemoteId ? (int)$maxRemoteId : 0;

        $response = $this->client->get(self::URL, [
            'query' => [
                'limit' => $limit,
                'skip' => $skip,
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $products = $data['products'] ?? [];

        if (empty($products)) {
            return [
                'imported' => 0,
                'skipped' => []
            ];
        }

        $importDate = (new DateTime())->format('Y-m-d H:i:s');
        
        $validProducts = [];
        $skippedProducts = [];
        $requiredFields = ['id', 'thumbnail', 'title', 'price', 'category', 'discountPercentage'];

        foreach ($products as $product) {
            $missingFields = array_diff($requiredFields, array_keys($product));

            if (!empty($missingFields)) {
                $skippedProducts[] = [
                    'id' => $product['id'] ?? 'unknown',
                    'reason' => 'Missing fields: ' . implode(', ', $missingFields)
                ];

                continue;
            }
            $validProducts[] = [
                'thumbnail' => $product['thumbnail'],
                'title' => $product['title'],
                'price' => $product['price'],
                'brand' => $product['brand'] ?? null,
                'category' => $product['category'],
                'discount_percentage' => $product['discountPercentage'],
                'import_date' => $importDate,
                'remote_id' => $product['id'],
            ];
        }

        if (empty($validProducts)) {
            return [
                'imported' => 0,
                'skipped' => $skippedProducts
            ];
        }

        $columns = ['thumbnail', 'title', 'price', 'brand', 'category', 'discount_percentage', 'import_date', 'remote_id'];
        $placeholders = [];
        $values = [];

        foreach ($validProducts as $product) {
            $placeholders[] = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';

            foreach ($columns as $column) {
                $values[] = $product[$column];
            }
        }

        $sql = "INSERT INTO products (thumbnail, title, price, brand, category, discount_percentage, import_date, remote_id)
                VALUES " . implode(',', $placeholders);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        return [
            'imported' => count($validProducts),
            'skipped' => $skippedProducts
        ];
    }
}
