<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redis = getRedisClient();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;

$cacheKey = "books_page_" . $page;

if ($redis) {
    $cached = $redis->get($cacheKey);
    if ($cached !== null) {
        $payload = json_decode($cached, true);

        echo $twig->render('index.html.twig', [
            'list'       => $payload['list'],
            'page'       => $page,
            'totalPages' => $payload['totalPages'],
            'perPage'    => $perPage,
            'total'      => $payload['total'],
            'cached'     => true
        ]);
        exit;
    }
}

$result = $manager->selectCollection($_ENV['MDB_DB'])->aggregate([
    [
        '$facet' => [
            'data' => [
                ['$sort' => ['_id' => -1]],
                ['$skip' => ($page - 1) * $perPage],
                ['$limit' => $perPage],
            ],
            'total' => [
                ['$count' => 'count']
            ]
        ]
    ]
])->toArray();

$list = $result[0]['data'] ?? [];
$total = $result[0]['total'][0]['count'] ?? 0;
$totalPages = ceil($total / $perPage);

$redis?->setex($cacheKey, 20, json_encode([
    'list' => $list,
    'total' => $total,
    'totalPages' => $totalPages
]));

try {
    echo $twig->render('index.html.twig', [
        'list'       => $list,
        'page'       => $page,
        'totalPages' => $totalPages,
        'perPage'    => $perPage,
        'total'      => $total,
        'cached'     => false
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}
