<?php

declare(strict_types=1);

use App\Database\Database;

return function () {
    $sql = "ALTER TABLE feed_entries
        ADD COLUMN enclosure_url VARCHAR(255) NULL,
        ADD COLUMN enclosure_type VARCHAR(100) NULL,
        ADD COLUMN enclosure_length INT NULL";

    Database::execute($sql);
}; 