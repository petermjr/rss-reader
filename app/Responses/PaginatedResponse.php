<?php

declare(strict_types=1);

namespace App\Responses;

abstract class PaginatedResponse
{
    /**
     * @param array $items The items to be paginated
     * @param int $total Total number of items
     * @param int $perPage Items per page
     * @param int $currentPage Current page number
     */
    public function __construct(
        protected array $items,
        protected int $total,
        protected int $perPage,
        protected int $currentPage
    ) {
    }

    public function toArray(): array
    {
        return [
            'entries' => $this->transformItems(),
            'pagination' => [
                'total' => $this->total,
                'perPage' => $this->perPage,
                'currentPage' => $this->currentPage,
                'lastPage' => ceil($this->total / $this->perPage)
            ]
        ];
    }

    abstract protected function transformItems(): array;
} 