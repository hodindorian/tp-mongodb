<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/vendor/autoload.php';

use MongoDB\Database;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Predis\Client as PredisClient;

// env configuration
(Dotenv\Dotenv::createImmutable(__DIR__))->load();

function getTwig(): Environment
{
    // twig configuration
    return new Environment(new FilesystemLoader('../templates'));
}

function getMongoDbManager(): Database
{
    $client = new MongoDB\Client("mongodb://{$_ENV['MDB_USER']}:{$_ENV['MDB_PASS']}@{$_ENV['MDB_SRV']}:{$_ENV['MDB_PORT']}");
    return $client->selectDatabase($_ENV['MDB_DB']);
}


function getRedisClient(): ? PredisClient
{
    if (!isset($_ENV['REDIS_ENABLE']) || strtolower($_ENV['REDIS_ENABLE']) !== 'true') {
        return null;
    }

    $host = $_ENV['REDIS_HOST'];
    $port = (int)($_ENV['REDIS_PORT']);

    try {
        $redis = new PredisClient([
            'scheme'   => 'tcp',
            'host'     => $host,
            'port'     => $port,
        ]);
        if ($redis->ping() == 'PONG') {
            return $redis;
        } else {
            throw new Exception("Redis ping a échoué.");
        }
    } catch (Exception $e) {
        error_log("Erreur de connexion Redis : " . $e->getMessage());
        return null;
    }
}

