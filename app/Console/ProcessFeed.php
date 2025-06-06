<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use SimplePie\SimplePie;

if ($argc < 2) {
    echo json_encode([
        'error' => 'Feed URL is required',
        'status' => 400
    ]);
    exit(1);
}

$url = $argv[1];
$outputFile = $argv[2] ?? null;

$simplePie = new SimplePie();
$simplePie->set_feed_url($url);
$simplePie->enable_cache(false);
$simplePie->init();

$result = [
    'url' => $url,
    'status' => 200,
    'feed' => null,
    'error' => null
];

try {
    if ($simplePie->error()) {
        $result['status'] = 400;
        $result['error'] = 'Invalid feed URL';
    } else {
        $result['feed'] = [
            'title' => $simplePie->get_title() ?: 'Untitled Feed',
            'url' => $url,
            'description' => $simplePie->get_description() ?: '',
            'last_updated' => date('Y-m-d H:i:s'),
            'items' => array_map(function ($item) {
                $enclosure = $item->get_enclosure();
                $link = $item->get_link();
                
                if (empty($link) && $enclosure) {
                    $link = $enclosure->get_link();
                }

                return [
                    'title' => $item->get_title() ?: 'Untitled Entry',
                    'url' => $link,
                    'description' => $item->get_description() ?: '',
                    'published_at' => $item->get_date('Y-m-d H:i:s') ?: date('Y-m-d H:i:s'),
                    'enclosure_url' => $enclosure ? $enclosure->get_link() : null,
                    'enclosure_type' => $enclosure ? $enclosure->get_type() : null,
                    'enclosure_length' => $enclosure ? $enclosure->get_length() : null
                ];
            }, $simplePie->get_items())
        ];
    }
} catch (\Exception $e) {
    $result['status'] = 500;
    $result['error'] = 'Failed to process feed: ' . $e->getMessage();
}

$output = json_encode($result);

if ($outputFile) {
    file_put_contents($outputFile, $output);
} else {
    echo $output;
} 