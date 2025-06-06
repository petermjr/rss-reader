<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use App\Services\FeedService;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManager;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        EntityManager::class => function (ContainerInterface $c) {
            $doctrineConfig = require __DIR__ . '/../config/doctrine.php';
            return $doctrineConfig();
        },
        FeedService::class => function (ContainerInterface $c) {
            return new FeedService($c->get(EntityManager::class));
        }
    ]);
};
