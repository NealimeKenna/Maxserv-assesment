<?php

declare(strict_types=1);

namespace MaxServ\Core\Traits;

use Symfony\Component\HttpFoundation\Request;

trait Filterable
{
    protected function applyFilters(Request $request, array $allowedFilters, string &$sql, array &$params): void
    {
        $filters = $request->query->all('filters') ?? [];
        $where = [];

        foreach ($filters as $key => $value) {
            if (in_array($key, $allowedFilters) && $value) {
                $where[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
    }
}
