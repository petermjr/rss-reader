<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Feed;
use App\Models\FeedEntry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Exception;
use SimplePie\SimplePie;

class FeedService
{
    private EntityManager $entityManager;
    private EntityRepository $feedRepository;
    private EntityRepository $feedEntryRepository;
    private SimplePie $simplePie;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->feedRepository = $entityManager->getRepository(Feed::class);
        $this->feedEntryRepository = $entityManager->getRepository(FeedEntry::class);
        $this->simplePie = new SimplePie();
    }

    public function getPosts(array $queryParams): array
    {
        $queryBuilder = $this->createPostsQueryBuilder();
        return (new FeedPaginator($queryBuilder))->paginate($queryParams);
    }

    private function createPostsQueryBuilder(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('e', 'f.title as HIDDEN feedTitle')
            ->from(FeedEntry::class, 'e')
            ->join('e.feed', 'f')
            ->orderBy('e.publishedAt', 'DESC');
    }

    private function createFeedEntry(Feed $feed, array $item): ?FeedEntry
    {
        if (empty($item['url'])) {
            return null;
        }

        return new FeedEntry(
            $feed,
            $item['title'],
            $item['url'],
            $item['description'],
            $item['published_at'],
            $item['enclosure_url'] ?? null,
            $item['enclosure_type'] ?? null,
            $item['enclosure_length'] ?? null
        );
    }

    public function saveFeedData(array $feedData): array
    {
        try {
            // Check if feed already exists
            $existingFeed = $this->feedRepository->findOneBy(['url' => $feedData['url']]);
            if ($existingFeed) {
                return [
                    'status' => 400,
                    'error' => 'Feed already exists'
                ];
            }

            // Create new feed
            $feed = new Feed(
                $feedData['title'],
                $feedData['url'],
                $feedData['description'],
                $feedData['last_updated']
            );
            $this->entityManager->persist($feed);

            // Create feed entries
            foreach ($feedData['items'] as $item) {
                $entry = $this->createFeedEntry($feed, $item);
                if ($entry) {
                    $feed->addEntry($entry);
                    $this->entityManager->persist($entry);
                }
            }

            $this->entityManager->flush();

            return [
                'status' => 200,
                'feed' => $feed->toArray()
            ];
        } catch (Exception $e) {
            return [
                'status' => 500,
                'error' => 'Failed to save feed: ' . $e->getMessage()
            ];
        }
    }

    public function processFeedsAsync(array $urls): array
    {
        $results = [];
        $processes = [];
        $tempDir = sys_get_temp_dir();
        $scriptPath = __DIR__ . '/../Console/ProcessFeed.php';
        
        // Start a process for each URL
        foreach ($urls as $url) {
            $tempFile = $tempDir . '/feed_' . md5($url) . '.json';
            
            // Execute the script in the background
            $command = sprintf(
                'php %s %s %s > /dev/null 2>&1 & echo $!',
                escapeshellarg($scriptPath),
                escapeshellarg($url),
                escapeshellarg($tempFile)
            );
            
            $pid = exec($command);
            $processes[$url] = [
                'pid' => $pid,
                'tempFile' => $tempFile
            ];
        }
        
        // Wait for all processes to complete
        foreach ($processes as $url => $process) {
            while (file_exists("/proc/{$process['pid']}")) {
                usleep(100000); // Sleep for 0.1 seconds
            }
            
            // Read the result
            if (file_exists($process['tempFile'])) {
                $result = json_decode(file_get_contents($process['tempFile']), true);
                
                // If feed was successfully processed, save it to database
                if ($result['status'] === 200 && isset($result['feed'])) {
                    $saveResult = $this->saveFeedData($result['feed']);
                    $result = array_merge($result, $saveResult);
                }
                
                $results[] = $result;
                
                // Clean up temporary file
                unlink($process['tempFile']);
            }
        }
        
        return $results;
    }
} 