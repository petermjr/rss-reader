<?php

declare(strict_types=1);

use App\Database\Database;

return function () {
    $sql = "CREATE TABLE IF NOT EXISTS feed_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        feed_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        url TEXT NOT NULL,
        description TEXT,
        published_at DATETIME NOT NULL,
        enclosure_url VARCHAR(255) NULL,
        enclosure_type VARCHAR(100) NULL,
        enclosure_length INT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (feed_id) REFERENCES feeds(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    Database::execute($sql);
}; 