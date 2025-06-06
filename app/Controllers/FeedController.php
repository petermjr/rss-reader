<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Feed;
use App\Services\FeedService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FeedController
{
    private EntityManager $entityManager;
    private FeedService $feedService;
    private EntityRepository $feedRepository;

    public function __construct(EntityManager $entityManager, FeedService $feedService)
    {
        $this->entityManager = $entityManager;
        $this->feedService = $feedService;
        $this->feedRepository = $entityManager->getRepository(Feed::class);
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

        // Process feeds asynchronously and save results
        $results = $this->feedService->processFeedsAsync($data['urls']);
        $hasErrors = false;

        // Check if any results had errors
        foreach ($results as $result) {
            if ($result['status'] >= 400) {
                $hasErrors = true;
                break;
            }
        }

        $response->getBody()->write(json_encode([
            'results' => $results,
            'status' => $hasErrors ? 400 : 201
        ]));
        return $response->withStatus($hasErrors ? 400 : 201)->withHeader('Content-Type', 'application/json');
    }

    public function refresh(Request $request, Response $response, array $args): Response
    {
        $feed = $this->feedRepository->find((int)$args['id']);
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

        $results = $this->feedService->processFeedsAsync([$feed->getUrl()]);
        $result = $results[0] ?? [
            'status' => 500,
            'error' => 'Failed to process feed'
        ];

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $feed = $this->feedRepository->find((int)$args['id']);
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

    public function getPosts(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $result = $this->feedService->getPosts($queryParams);

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}