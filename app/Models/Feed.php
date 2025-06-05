<?php

declare(strict_types=1);

namespace App\Models;

use App\Database\Database;

class Feed
{
    private ?int $id = null;
    private string $title;
    private string $url;
    private ?string $description;
    private ?string $lastUpdated;
    private array $entries = [];

    public function __construct(
        string $title,
        string $url,
        ?string $description = null,
        ?string $lastUpdated = null
    ) {
        $this->title = $title;
        $this->url = $url;
        $this->description = $description;
        $this->lastUpdated = $lastUpdated;
    }

    public static function find(int $id): ?self
    {
        $result = Database::query(
            "SELECT * FROM feeds WHERE id = ?",
            [$id]
        );

        if (empty($result)) {
            return null;
        }

        $feed = new self(
            $result[0]['title'],
            $result[0]['url'],
            $result[0]['description'],
            $result[0]['last_updated']
        );
        $feed->id = $result[0]['id'];
        $feed->loadEntries();
        return $feed;
    }

    public static function all(): array
    {
        $feeds = [];
        $results = Database::query("SELECT * FROM feeds");

        foreach ($results as $row) {
            $feed = new self(
                $row['title'],
                $row['url'],
                $row['description'],
                $row['last_updated']
            );
            $feed->id = $row['id'];
            $feed->loadEntries();
            $feeds[] = $feed;
        }

        return $feeds;
    }

    public function save(): void
    {
        if ($this->id === null) {
            $this->insert();
        } else {
            $this->update();
        }
    }

    private function insert(): void
    {
        $this->id = Database::execute(
            "INSERT INTO feeds (title, url, description, last_updated) VALUES (?, ?, ?, ?)",
            [$this->title, $this->url, $this->description, $this->lastUpdated]
        );
    }

    private function update(): void
    {
        Database::execute(
            "UPDATE feeds SET title = ?, url = ?, description = ?, last_updated = ? WHERE id = ?",
            [$this->title, $this->url, $this->description, $this->lastUpdated, $this->id]
        );
    }

    public function delete(): void
    {
        if ($this->id !== null) {
            Database::execute("DELETE FROM feeds WHERE id = ?", [$this->id]);
        }
    }

    private function loadEntries(): void
    {
        if ($this->id === null) {
            return;
        }

        $results = Database::query(
            "SELECT * FROM feed_entries WHERE feed_id = ?",
            [$this->id]
        );

        $this->entries = $results;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'description' => $this->description,
            'last_updated' => $this->lastUpdated,
            'entries' => $this->entries
        ];
    }
} 