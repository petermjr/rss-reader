<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedEntry extends Model
{
    protected $fillable = [
        'feed_id',
        'title',
        'description',
        'link',
        'published_at'
    ];

    protected $casts = [
        'published_at' => 'datetime'
    ];

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }
} 