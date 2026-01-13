<?php

include_once __DIR__.'/../init.php';

$mongo = getMongoDbManager();
$es = getElasticClient();

$collection = $mongo->selectCollection($_ENV['MDB_DB']);
$books = $collection->find();

$index = $_ENV['ELASTIC_INDEX'];

try {
    $es->indices()->delete(['index' => $index]);
} catch (Exception $e) {}

$es->indices()->create([
    'index' => $index,
    'body' => [
        'settings' => [
            'analysis' => [
                'analyzer' => [
                    'default' => [
                        'type' => 'standard'
                    ]
                ]
            ]
        ],
        'mappings' => [
            'properties' => [
                'title' => [
                    'type' => 'text',
                    'analyzer' => 'standard'
                ],
                'author' => [
                    'type' => 'text',
                    'analyzer' => 'standard'
                ]
            ]
        ]
    ]
]);

foreach ($books as $book) {
    $es->index([
        'index' => $index,
        'id'    => (string) $book['_id'],
        'body'  => [
            'title'  => $book['titre'] ?? '',    // correspond au champ Mongo
            'author' => $book['auteur'] ?? ''   // correspond au champ Mongo
        ]
    ]);
}

echo "Indexation terminée\n";



return 1;