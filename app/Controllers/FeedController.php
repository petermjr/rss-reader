<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Feed;
use App\Models\FeedEntry;
use App\Services\FeedService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SimplePie\SimplePie;
use Exception;

class FeedController
{
    private SimplePie $simplePie;
    private EntityManager $entityManager;
    private FeedService $feedService;
    private EntityRepository $feedRepository;
    private EntityRepository $feedEntryRepository;

    public function __construct(SimplePie $simplePie, EntityManager $entityManager, FeedService $feedService)
    {
        $this->simplePie = $simplePie;
        $this->entityManager = $entityManager;
        $this->feedService = $feedService;
        $this->feedRepository = $entityManager->getRepository(Feed::class);
        $this->feedEntryRepository = $entityManager->getRepository(FeedEntry::class);
    }

    public function index(Request $request, Response $response): Response
    {
        $feeds = $this->feedRepository->findAll();
        $response->getBody()->write(json_encode(['feeds' => array_map(fn($feed) => $feed->toArray(), $feeds)]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (!isset($data['urls']) || !is_array($data['urls'])) {
            $response->getBody()->write(json_encode(['error' => 'URLs array is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $results = [];
        $hasErrors = false;

        foreach ($data['urls'] as $url) {
            $result = $this->feedService->createFeed($url);
            $results[] = [
                'url' => $url,
                'status' => $result['status'],
                'message' => $result['message'] ?? $result['error'] ?? 'Unknown error',
                'feed' => $result['feed'] ?? null
            ];

            if ($result['status'] >= 400) {
                $hasErrors = true;
            }
        }
        
        $response->getBody()->write(json_encode([
            'results' => $results,
            'status' => $hasErrors ? 400 : 201
        ]));
        return $response->withStatus($hasErrors ? 400 : 201)->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $feed = $this->feedRepository->find((int)$args['id']);
        
        if (!$feed) {
            $response->getBody()->write(json_encode(['error' => 'Feed not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $result = $this->feedService->updateFeed($feed);
        
        $response->getBody()->write(json_encode($result));
        return $response->withStatus($result['status'])->withHeader('Content-Type', 'application/json');
    }

    public function refresh(Request $request, Response $response, array $args): Response
    {
        $feed = $this->feedRepository->find((int) $args['id']);
        if (!$feed) {
            $response->getBody()->write(json_encode(['error' => 'Feed not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Send initial response with loading state
        $response->getBody()->write(json_encode([
            'status' => 'loading',
            'message' => 'Refreshing feed...',
            'feed' => $feed->toArray()
        ]));
        $response = $response->withHeader('Content-Type', 'application/json');

        $result = $this->feedService->refreshFeed($feed);
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $feed = $this->feedRepository->find((int)$args['id']);
        
        if (!$feed) {
            return $response->withStatus(404);
        }

        $this->entityManager->remove($feed);
        $this->entityManager->flush();

        $response->getBody()->write(json_encode(['message' => 'Feed deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getPosts(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $result = $this->feedService->getPosts($queryParams);
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $feed = $this->feedRepository->find((int) $args['id']);
        if (!$feed) {
            $response->getBody()->write(json_encode(['error' => 'Feed not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        try {
            $this->entityManager->remove($feed);
            $this->entityManager->flush();
        
            $response->getBody()->write(json_encode(['message' => 'Feed deleted successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Failed to delete feed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}