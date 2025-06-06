<?php

declare(strict_types=1);

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'feeds')]
class Feed
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $url;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(name: 'last_updated', type: 'datetime')]
    private \DateTime $lastUpdated;

    #[ORM\OneToMany(targetEntity: FeedEntry::class, mappedBy: 'feed', cascade: ['persist', 'remove'])]
    private Collection $entries;

    public function __construct(string $title, string $url, string $description, string $lastUpdated)
    {
        $this->title = $title;
        $this->url = $url;
        $this->description = $description;
        $this->lastUpdated = new \DateTime($lastUpdated);
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLastUpdated(): \DateTime
    {
        return $this->lastUpdated;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setLastUpdated(string $lastUpdated): void
    {
        $this->lastUpdated = new \DateTime($lastUpdated);
    }

    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(FeedEntry $entry): void
    {
        if (!$this->entries->contains($entry)) {
            $entry->setFeed($this);
            $this->entries->add($entry);
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'description' => $this->description,
            'last_updated' => $this->lastUpdated->format('Y-m-d H:i:s'),
            'entries' => $this->entries->map(fn(FeedEntry $entry) => $entry->toArray())->toArray()
        ];
    }
} 