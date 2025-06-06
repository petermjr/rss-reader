<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

return function (): EntityManager {
    $paths = [__DIR__ . '/../app/Models'];
    $isDevMode = true;

    $dbParams = [
        'driver'   => 'pdo_mysql',
        'host'     => $_ENV['DB_HOST'] ?? 'localhost',
        'port'     => $_ENV['DB_PORT'] ?? 3306,
        'dbname'   => $_ENV['DB_NAME'] ?? 'rss_reader',
        'user'     => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? '',
        'charset'  => 'utf8mb4'
    ];

    // Create cache configuration
    $cache = $isDevMode 
        ? new ArrayAdapter() // Use array cache in dev mode
        : new FilesystemAdapter('doctrine', 0, __DIR__ . '/../var/cache'); // Use filesystem cache in prod

    $config = ORMSetup::createAttributeMetadataConfiguration($paths, $isDevMode);
    $config->setMetadataCache($cache);
    $config->setQueryCache($cache);
    $config->setResultCache($cache);

    $connection = DriverManager::getConnection($dbParams, $config);

    return new EntityManager($connection, $config);
}; 