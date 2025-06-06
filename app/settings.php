<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Contracts\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => \DI\create(Settings::class)->constructor([
            'displayErrorDetails' => true, // Should be set to false in production
            'logError'            => true,
            'logErrorDetails'     => true,
            'logger' => [
                'name' => 'slim-app',
                'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                'level' => Logger::DEBUG,
            ],
        ])
    ]);
};
