<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaginatorInterface;
use App\Responses\FeedPaginatedResponse;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class FeedPaginator implements PaginatorInterface
{
    private QueryBuilder $queryBuilder;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function paginate(array $queryParams): array
    {
        $page = max(1, (int)($queryParams['page'] ?? 1));
        $perPage = max(1, (int)($queryParams['perPage'] ?? 10));
        $offset = ($page - 1) * $perPage;

        $this->applyPostsFilters($queryParams);
        $this->applyPagination($perPage, $offset);

        $total = $this->getTotalPostsCount();
        $entries = $this->queryBuilder->getQuery()->getResult();
        
        return $this->createPaginatedResponse($entries, $total, $perPage, $page);
    }

    private function applyPostsFilters(array $queryParams): void
    {
        if (!empty($queryParams['feedId'])) {
            $this->queryBuilder->andWhere('e.feed = :feedId')
                ->setParameter('feedId', (int)$queryParams['feedId']);
        }

        if (!empty($queryParams['startDate'])) {
            $this->queryBuilder->andWhere('e.publishedAt >= :startDate')
                ->setParameter('startDate', new \DateTime($queryParams['startDate']));
        }

        if (!empty($queryParams['endDate'])) {
            $this->queryBuilder->andWhere('e.publishedAt <= :endDate')
                ->setParameter('endDate', new \DateTime($queryParams['endDate']));
        }

        if (!empty($queryParams['search'])) {
            $this->queryBuilder->andWhere(
                $this->queryBuilder->expr()->orX(
                    $this->queryBuilder->expr()->like('e.title', ':search'),
                    $this->queryBuilder->expr()->like('e.description', ':search')
                )
            )->setParameter('search', '%' . $queryParams['search'] . '%');
        }

        if (isset($queryParams['isRead'])) {
            $this->queryBuilder->andWhere('e.isRead = :isRead')
                ->setParameter('isRead', (bool)$queryParams['isRead']);
        }
    }

    private function applyPagination(int $perPage, int $offset): void
    {
        $this->queryBuilder
            ->setMaxResults($perPage)
            ->setFirstResult($offset);
    }

    private function getTotalPostsCount(): int
    {
        $countQueryBuilder = clone $this->queryBuilder;
        $countQueryBuilder
            ->select('COUNT(e.id)')
            ->resetDQLPart('orderBy')
            ->setMaxResults(null)
            ->setFirstResult(null);

        return (int)$countQueryBuilder->getQuery()->getSingleScalarResult();
    }

    private function createPaginatedResponse(array $entries, int $total, int $perPage, int $page): array
    {
        return (new FeedPaginatedResponse(
            $entries,
            $total,
            $perPage,
            $page
        ))->toArray();
    }
}