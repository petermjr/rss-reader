<?php

declare(strict_types=1);

namespace App\Contracts;

interface PaginatorInterface
{
    /**
     * Paginate the query results
     *
     * @param array $queryParams Query parameters including pagination options
     * @return array Paginated response with entries and pagination metadata
     */
    public function paginate(array $queryParams): array;
} 