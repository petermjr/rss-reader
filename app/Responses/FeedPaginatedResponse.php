<?php

declare(strict_types=1);

namespace App\Responses;

use App\Models\FeedEntry;

class FeedPaginatedResponse extends PaginatedResponse
{
    protected function transformItems(): array
    {
        return array_map(
            fn(FeedEntry $entry) => $this->transformEntry($entry),
            $this->items
        );
    }

    private function transformEntry(FeedEntry $entry): array
    {
        $data = $entry->toArray();
        $data['feed_title'] = $entry->getFeed()->getTitle();
        return $data;
    }
} 