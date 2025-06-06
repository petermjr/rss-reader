<?php

declare(strict_types=1);

use App\Database\Database;

return function () {
    // Drop tables if they exist (in correct order due to foreign key constraints)
    Database::execute("DROP TABLE IF EXISTS feed_entries");
    Database::execute("DROP TABLE IF EXISTS feeds");
    
    $sql = "CREATE TABLE IF NOT EXISTS feeds (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        url TEXT NOT NULL,
        description TEXT,
        last_updated DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    Database::execute($sql);
}; 