<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Feed;
use App\Models\FeedEntry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use SimplePie\SimplePie;
use Exception;
use App\Responses\FeedPaginatedResponse;

class FeedService
{
    private EntityManager $entityManager;
    private EntityRepository $feedRepository;
    private EntityRepository $feedEntryRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->feedRepository = $entityManager->getRepository(Feed::class);
        $this->feedEntryRepository = $entityManager->getRepository(FeedEntry::class);
    }

    public function getPosts(array $queryParams): array
    {
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = max(1, (int) ($queryParams['perPage'] ?? 10));
        $offset = ($page - 1) * $perPage;

        $queryBuilder = $this->createPostsQueryBuilder();
        $this->applyPostsFilters($queryBuilder, $queryParams);
        $this->applyPagination($queryBuilder, $perPage, $offset);

        $total = $this->getTotalPostsCount($queryParams);
        $entries = $queryBuilder->getQuery()->getResult();
        
        return (new FeedPaginatedResponse(
            $entries,
            $total,
            $perPage,
            $page
        ))->toArray();
    }

    private function createPostsQueryBuilder(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('e', 'f.title as HIDDEN feedTitle')
            ->from(FeedEntry::class, 'e')
            ->join('e.feed', 'f')
            ->orderBy('e.publishedAt', 'DESC');
    }

    private function applyPostsFilters(QueryBuilder $queryBuilder, array $queryParams): void
    {
        if (!empty($queryParams['feedId'])) {
            $queryBuilder->andWhere('e.feed = :feedId')
                ->setParameter('feedId', (int) $queryParams['feedId']);
        }

        if (!empty($queryParams['startDate'])) {
            $queryBuilder->andWhere('e.publishedAt >= :startDate')
                ->setParameter('startDate', new \DateTime($queryParams['startDate']));
        }

        if (!empty($queryParams['endDate'])) {
            $queryBuilder->andWhere('e.publishedAt <= :endDate')
                ->setParameter('endDate', new \DateTime($queryParams['endDate']));
        }

        if (!empty($queryParams['search'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('e.title', ':search'),
                    $queryBuilder->expr()->like('e.description', ':search')
                )
            )->setParameter('search', '%' . $queryParams['search'] . '%');
        }

        if (isset($queryParams['isRead'])) {
            $queryBuilder->andWhere('e.isRead = :isRead')
                ->setParameter('isRead', (bool) $queryParams['isRead']);
        }
    }

    private function applyPagination(QueryBuilder $queryBuilder, int $perPage, int $offset): void
    {
        $queryBuilder
            ->setMaxResults($perPage)
            ->setFirstResult($offset);
    }

    private function getTotalPostsCount(array $queryParams): int
    {
        $countQueryBuilder = $this->createPostsQueryBuilder();
        $this->applyPostsFilters($countQueryBuilder, $queryParams);
        
        $countQueryBuilder
            ->select('COUNT(e.id)')
            ->resetDQLPart('orderBy')
            ->setMaxResults(null)
            ->setFirstResult(null);

        return (int) $countQueryBuilder->getQuery()->getSingleScalarResult();
    }

    public function saveFeedEntries(Feed $feed, SimplePie $simplePie): void
    {
        // Remove existing entries
        foreach ($feed->getEntries() as $entry) {
            $this->entityManager->remove($entry);
        }
        $feed->getEntries()->clear();

        // Save new entries
        foreach ($simplePie->get_items() as $item) {
            $enclosure = $item->get_enclosure();
            $link = $item->get_link();
            
            // If link is empty and we have an enclosure, use the enclosure link
            if (empty($link) && $enclosure) {
                $link = $enclosure->get_link();
            }
            
            // Skip if we still don't have a valid link
            if (empty($link)) {
                continue;
            }

            $entry = new FeedEntry(
                $feed,
                $item->get_title() ?: 'Untitled Entry',
                $link,
                $item->get_description() ?: '',
                $item->get_date('Y-m-d H:i:s') ?: date('Y-m-d H:i:s'),
                $enclosure ? $enclosure->get_link() : null,
                $enclosure ? $enclosure->get_type() : null,
                $enclosure ? $enclosure->get_length() : null
            );
            $feed->addEntry($entry);
            $this->entityManager->persist($entry);
        }
    }

    public function refreshFeed(Feed $feed): array
    {
        try {
            $simplePie = new SimplePie();
            $simplePie->set_feed_url($feed->getUrl());
            $simplePie->enable_cache(false);
            $simplePie->init();

            if ($simplePie->error()) {
                return [
                    'status' => 'error',
                    'error' => 'Failed to fetch feed',
                    'feed' => $feed->toArray()
                ];
            }

            // Update feed information
            $feed->setTitle($simplePie->get_title() ?: 'Untitled Feed');
            $feed->setDescription($simplePie->get_description() ?: '');
            $feed->setLastUpdated(date('Y-m-d H:i:s'));
            $this->entityManager->persist($feed);

            // Process new entries
            $newEntriesCount = 0;
            foreach ($simplePie->get_items() as $item) {
                $link = $item->get_link();
                if (empty($link) && $item->get_enclosure()) {
                    $link = $item->get_enclosure()->get_link();
                }
                
                if (empty($link)) {
                    continue;
                }

                // Check if entry already exists
                $existingEntry = $this->feedEntryRepository->findOneBy(['url' => $link]);
                if ($existingEntry) {
                    continue;
                }

                $entry = new FeedEntry(
                    $feed,
                    $item->get_title() ?: 'Untitled Entry',
                    $link,
                    $item->get_description() ?: '',
                    $item->get_date('Y-m-d H:i:s') ?: date('Y-m-d H:i:s'),
                    $item->get_enclosure() ? $item->get_enclosure()->get_link() : null,
                    $item->get_enclosure() ? $item->get_enclosure()->get_type() : null,
                    $item->get_enclosure() ? $item->get_enclosure()->get_length() : null
                );
                $this->entityManager->persist($entry);
                $newEntriesCount++;
            }
            $this->entityManager->flush();

            return [
                'status' => 'success',
                'message' => "Feed refreshed successfully. Added {$newEntriesCount} new entries.",
                'feed' => $feed->toArray()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => 'Failed to refresh feed: ' . $e->getMessage(),
                'feed' => $feed->toArray()
            ];
        }
    }

    public function createFeed(string $url): array
    {
        // Check if feed already exists
        $existingFeed = $this->feedRepository->findOneBy(['url' => $url]);
        if ($existingFeed) {
            return [
                'error' => 'Feed already exists',
                'status' => 400
            ];
        }

        try {
            $simplePie = new SimplePie();
            $simplePie->set_feed_url($url);
            $simplePie->enable_cache(false);
            $simplePie->init();

            if ($simplePie->error()) {
                return [
                    'error' => 'Invalid feed URL',
                    'status' => 400
                ];
            }

            $newFeed = new Feed(
                $simplePie->get_title() ?: 'Untitled Feed',
                $url,
                $simplePie->get_description() ?: '',
                date('Y-m-d H:i:s')
            );
            $this->entityManager->persist($newFeed);
            $this->entityManager->flush();

            // Process feed entries
            $this->saveFeedEntries($newFeed, $simplePie);
            $this->entityManager->flush();

            return [
                'feed' => $newFeed->toArray(),
                'status' => 200
            ];
        } catch (Exception $e) {
            return [
                'error' => 'Failed to process feed: ' . $e->getMessage(),
                'status' => 500
            ];
        }
    }

    public function updateFeed(Feed $feed): array
    {
        try {
            $simplePie = new SimplePie();
            $simplePie->set_feed_url($feed->getUrl());
            $simplePie->enable_cache(false);
            $simplePie->init();
            $simplePie->handle_content_type();

            if ($simplePie->error()) {
                return [
                    'error' => 'Failed to fetch feed',
                    'status' => 400
                ];
            }

            $this->entityManager->persist($feed);
            $this->saveFeedEntries($feed, $simplePie);
            $this->entityManager->flush();

            return [
                'message' => 'Feed updated successfully',
                'status' => 200
            ];
        } catch (Exception $e) {
            return [
                'error' => 'Failed to update feed: ' . $e->getMessage(),
                'status' => 500
            ];
        }
    }
} 