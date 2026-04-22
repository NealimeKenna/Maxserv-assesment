<?php

declare(strict_types=1);

namespace MaxServ\Core\Traits;

use Symfony\Component\HttpFoundation\Request;

trait Sortable
{
    protected function applySorting(
        Request $request,
        array $allowedSortFields,
        string &$sql,
        string $defaultField = 'id',
        string $defaultOrder = 'DESC'
    ): void {
        $sort = $request->query->all('sort');
        $field = $sort['field'] ?? $defaultField;
        $order = strtoupper($sort['order'] ?? $defaultOrder);

        if (!in_array($field, $allowedSortFields)) {
            $field = $defaultField;
        }

        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = $defaultOrder;
        }

        $sql .= " ORDER BY $field" . ($order === 'ASC' ? '' : " $order");
    }
}
