<?php

declare(strict_types=1);

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'feed_entries')]
class FeedEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Feed::class, inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'feed_id', referencedColumnName: 'id', nullable: false)]
    private Feed $feed;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $url;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(name: 'published_at', type: 'datetime')]
    private \DateTime $publishedAt;

    #[ORM\Column(name: 'enclosure_url', type: 'string', length: 255, nullable: true)]
    private ?string $enclosureUrl = null;

    #[ORM\Column(name: 'enclosure_type', type: 'string', length: 100, nullable: true)]
    private ?string $enclosureType = null;

    #[ORM\Column(name: 'enclosure_length', type: 'integer', nullable: true)]
    private ?int $enclosureLength = null;

    public function __construct(
        Feed $feed,
        string $title,
        string $url,
        string $description,
        string $publishedAt,
        ?string $enclosureUrl = null,
        ?string $enclosureType = null,
        ?int $enclosureLength = null
    ) {
        $this->feed = $feed;
        $this->title = $title;
        $this->url = $url;
        $this->description = $description;
        $this->publishedAt = new \DateTime($publishedAt);
        $this->enclosureUrl = $enclosureUrl;
        $this->enclosureType = $enclosureType;
        $this->enclosureLength = $enclosureLength;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFeed(): Feed
    {
        return $this->feed;
    }

    public function setFeed(?Feed $feed): void
    {
        $this->feed = $feed;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPublishedAt(): \DateTime
    {
        return $this->publishedAt;
    }

    public function getEnclosureUrl(): ?string
    {
        return $this->enclosureUrl;
    }

    public function getEnclosureType(): ?string
    {
        return $this->enclosureType;
    }

    public function getEnclosureLength(): ?int
    {
        return $this->enclosureLength;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'feed_id' => $this->feed->getId(),
            'title' => $this->title,
            'url' => $this->url,
            'description' => $this->description,
            'published_at' => $this->publishedAt->format('Y-m-d H:i:s'),
            'enclosure_url' => $this->enclosureUrl,
            'enclosure_type' => $this->enclosureType,
            'enclosure_length' => $this->enclosureLength
        ];
    }
} 