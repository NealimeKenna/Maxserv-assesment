<?php

declare(strict_types=1);

namespace MaxServ\core\tests\Traits;

use MaxServ\Core\Traits\Filterable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class FilterableTest extends TestCase
{
    use Filterable;

    public function testApplyFiltersWithNoFilters(): void
    {
        $request = new Request();
        $sql = "SELECT * FROM products";
        $params = [];
        $allowedFilters = ['brand', 'category'];

        $this->applyFilters($request, $allowedFilters, $sql, $params);

        $this->assertEquals("SELECT * FROM products", $sql);
        $this->assertEmpty($params);
    }

    public function testApplyFiltersWithValidFilters(): void
    {
        $request = new Request(['filters' => ['brand' => 'Apple', 'category' => 'smartphones']]);
        $sql = "SELECT * FROM products";
        $params = [];
        $allowedFilters = ['brand', 'category'];

        $this->applyFilters($request, $allowedFilters, $sql, $params);

        $this->assertStringContainsString(" WHERE ", $sql);
        $this->assertStringContainsString("brand = :brand", $sql);
        $this->assertStringContainsString("category = :category", $sql);
        $this->assertEquals('Apple', $params['brand']);
        $this->assertEquals('smartphones', $params['category']);
    }

    public function testApplyFiltersWithInvalidFilters(): void
    {
        $request = new Request(['filters' => ['invalid' => 'value', 'brand' => 'Apple']]);
        $sql = "SELECT * FROM products";
        $params = [];
        $allowedFilters = ['brand'];

        $this->applyFilters($request, $allowedFilters, $sql, $params);

        $this->assertEquals("SELECT * FROM products WHERE brand = :brand", $sql);
        $this->assertEquals(['brand' => 'Apple'], $params);
    }

    public function testApplyFiltersWithEmptyValue(): void
    {
        $request = new Request(['filters' => ['brand' => '']]);
        $sql = "SELECT * FROM products";
        $params = [];
        $allowedFilters = ['brand'];

        $this->applyFilters($request, $allowedFilters, $sql, $params);

        $this->assertEquals("SELECT * FROM products", $sql);
        $this->assertEmpty($params);
    }
}
