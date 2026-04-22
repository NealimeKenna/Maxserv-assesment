<?php

declare(strict_types=1);

namespace MaxServ\core\tests\Traits;

use MaxServ\Core\Traits\Sortable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class SortableTest extends TestCase
{
    use Sortable;

    public function testApplySortingWithDefaults(): void
    {
        $request = new Request();
        $sql = "SELECT * FROM products";
        $allowedSortFields = ['title', 'price'];

        $this->applySorting($request, $allowedSortFields, $sql);

        $this->assertEquals("SELECT * FROM products ORDER BY id DESC", $sql);
    }

    public function testApplySortingWithValidSort(): void
    {
        $request = new Request(['sort' => ['field' => 'price', 'order' => 'asc']]);
        $sql = "SELECT * FROM products";
        $allowedSortFields = ['title', 'price'];

        $this->applySorting($request, $allowedSortFields, $sql);

        $this->assertEquals("SELECT * FROM products ORDER BY price", $sql);
    }

    public function testApplySortingWithInvalidField(): void
    {
        $request = new Request(['sort' => ['field' => 'invalid', 'order' => 'asc']]);
        $sql = "SELECT * FROM products";
        $allowedSortFields = ['title', 'price'];

        $this->applySorting($request, $allowedSortFields, $sql);

        $this->assertEquals("SELECT * FROM products ORDER BY id", $sql);
    }

    public function testApplySortingWithInvalidOrder(): void
    {
        $request = new Request(['sort' => ['field' => 'price', 'order' => 'invalid']]);
        $sql = "SELECT * FROM products";
        $allowedSortFields = ['title', 'price'];

        $this->applySorting($request, $allowedSortFields, $sql);

        $this->assertEquals("SELECT * FROM products ORDER BY price DESC", $sql);
    }

    public function testApplySortingWithCustomDefaults(): void
    {
        $request = new Request();
        $sql = "SELECT * FROM products";
        $allowedSortFields = ['title', 'price'];

        $this->applySorting($request, $allowedSortFields, $sql, 'title', 'ASC');

        $this->assertEquals("SELECT * FROM products ORDER BY title", $sql);
    }
}
