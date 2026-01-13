<?php

include_once '../init.php';

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig    = getTwig();
$manager = getMongoDbManager();
$redis   = getRedisClient();
$es      = getElasticClient();
$q = $_GET['q'] ?? null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;

$cacheKey = 'books_page_' . $page . '_q_' . md5($q ?? '');

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
            'q'          => $q,
            'cached'     => true,
        ]);
        exit;
    }
}

$ids = null;

if ($q && $es) {
    try {
        $response = $es->search([
            'index' => $_ENV['ELASTIC_INDEX'],
            'body'  => [
                'query' => [
                    'bool' => [
                        'should' => [
                            [
                                'match' => [
                                    'title' => [
                                        'query'     => $q,
                                        'fuzziness' => 'AUTO',
                                        'boost'     => 2
                                    ]
                                ]
                            ],
                            [
                                'match' => [
                                    'author' => [
                                        'query'     => $q,
                                        'fuzziness' => 'AUTO',
                                        'boost'     => 1
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $ids = array_map(
            fn ($hit) => new MongoDB\BSON\ObjectId($hit['_id']),
            $response['hits']['hits']
        );

    } catch (ClientResponseException|ServerResponseException $e) {}
}

$pipeline = [];

if ($ids !== null) {
    $pipeline[] = [
        '$match' => [
            '_id' => ['$in' => $ids]
        ]
    ];
}

$pipeline[] = [
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
];

$result = $manager
    ->selectCollection($_ENV['MDB_DB'])
    ->aggregate($pipeline)
    ->toArray();

$list  = $result[0]['data'] ?? [];
$total = $result[0]['total'][0]['count'] ?? 0;
$totalPages = (int) ceil($total / $perPage);

$redis?->setex($cacheKey, 20, json_encode([
    'list'       => $list,
    'total'      => $total,
    'totalPages' => $totalPages,
]));

try {
    echo $twig->render('index.html.twig', [
        'list'       => $list,
        'page'       => $page,
        'totalPages' => $totalPages,
        'perPage'    => $perPage,
        'total'      => $total,
        'q'          => $q,
        'cached'     => false,
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}
