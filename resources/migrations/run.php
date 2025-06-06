<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$migrations = [
    'create_feeds_table.php',
    'create_feed_entries_table.php',
];

foreach ($migrations as $migrationFile) {
    echo "Running migration: $migrationFile\n";
    $migration = require __DIR__ . '/' . $migrationFile;
    if (is_callable($migration)) {
        $migration();
        echo "Migration completed: $migrationFile\n";
    } else {
        echo "Error: Migration $migrationFile did not return a callable\n";
    }
} 