<?php

declare(strict_types=1);

namespace MaxServ\App\Controller;

use GuzzleHttp\Exception\GuzzleException;
use MaxServ\Core\Service\ProductImportService;
use Symfony\Component\HttpFoundation\JsonResponse;

readonly class ProductController
{
    public function __construct(
        private ProductImportService $productImportService
    ) {
    }

    public function import(): JsonResponse
    {
        try {
            $result = $this->productImportService->importNextBatch();
            $response = new JsonResponse([
                'status' => 'success',
                'message' => "Imported " . $result['imported'] . " products",
                'skipped' => $result['skipped']
            ]);

            $response->send();

            return $response;
        } catch (GuzzleException $e) {
            $response = new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);

            $response->send();

            return $response;
        }
    }
}
