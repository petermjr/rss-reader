<?php

declare(strict_types=1);

use App\Controllers\FeedController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->group('/api', function (Group $group) {
        // RSS Feed routes
        $group->group('/feeds', function (Group $group) {
            $group->get('', [FeedController::class, 'index']);
            $group->post('', [FeedController::class, 'store']);
            $group->post('/{id}/refresh', [FeedController::class, 'refresh']);
            $group->delete('/{id}', [FeedController::class, 'delete']);
        });

        // Posts route
        $group->get('/posts', [FeedController::class, 'getPosts']);
    });
};
