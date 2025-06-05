<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Feed;
use App\Models\FeedEntry;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SimplePie\SimplePie;

class FeedController
{
    private SimplePie $simplePie;

    public function __construct()
    {
        $this->simplePie = new SimplePie();
    }

    public function index(Request $request, Response $response): Response
    {
        $feeds = Feed::all();
        $response->getBody()->write(json_encode(array_map(fn($feed) => $feed->toArray(), $feeds)));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $feed = Feed::find((int)$args['id']);
        
        if (!$feed) {
            return $response->withStatus(404);
        }

        $response->getBody()->write(json_encode($feed->toArray()));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        if (!isset($data['url'])) {
            return $response->withStatus(400);
        }

        $this->simplePie->set_feed_url($data['url']);
        $this->simplePie->init();
        $this->simplePie->handle_content_type();

        $feed = new Feed(
            $this->simplePie->get_title(),
            $data['url'],
            $this->simplePie->get_description(),
            date('Y-m-d H:i:s')
        );

        $feed->save();

        $response->getBody()->write(json_encode($feed->toArray()));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $feed = Feed::find((int)$args['id']);
        
        if (!$feed) {
            return $response->withStatus(404);
        }

        $this->simplePie->set_feed_url($feed->url);
        $this->simplePie->init();
        $this->simplePie->handle_content_type();

        $feed->save();

        $response->getBody()->write(json_encode(['message' => 'Feed updated successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $feed = Feed::find((int)$args['id']);
        
        if (!$feed) {
            return $response->withStatus(404);
        }

        $feed->delete();

        $response->getBody()->write(json_encode(['message' => 'Feed deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    }
} 