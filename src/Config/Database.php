<?php

declare(strict_types=1);

namespace Bookshare\Config;

use MongoDB\Client;
use MongoDB\Database as MongoDatabase;
use MongoDB\Exception\Exception as MongoDBException;
use PDO;
use PDOException;

class Database
{
    private const DEFAULT_MONGO_DB = 'bookshare';

    private static ?PDO $pdo = null;
    private static ?Client $mongoClient = null;

    public static function getPdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $mysqlConfig = [
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'name' => getenv('DB_NAME') ?: 'bookshare',
            'user' => getenv('DB_USER') ?: 'root',
            'pass' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
        ];

        if ($cleardbUrl = getenv('JAWSDB_URL')) {
            $parts = parse_url($cleardbUrl);
            if ($parts !== false) {
                $mysqlConfig['host'] = $parts['host'] ?? $mysqlConfig['host'];
                $mysqlConfig['user'] = $parts['user'] ?? $mysqlConfig['user'];
                $mysqlConfig['pass'] = $parts['pass'] ?? $mysqlConfig['pass'];
                $mysqlConfig['name'] = isset($parts['path']) ? ltrim($parts['path'], '/') : $mysqlConfig['name'];
            } else {
                error_log('JAWSDB_URL malformed, falling back to default MySQL configuration.');
            }
        }

        try {
            self::$pdo = new PDO(
                sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    $mysqlConfig['host'],
                    $mysqlConfig['name'],
                    $mysqlConfig['charset']
                ),
                $mysqlConfig['user'],
                $mysqlConfig['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new PDOException('Erreur MySQL : ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        return self::$pdo;
    }

    public static function getMongoClient(): ?Client
    {
        if (self::$mongoClient instanceof Client) {
            return self::$mongoClient;
        }

        $mongoUri = getenv('MONGODB_URI');
        if (!$mongoUri) {
            error_log('MONGODB_URI non defini; connexion MongoDB ignoree.');
            return null;
        }

        try {
            self::$mongoClient = new Client($mongoUri);
        } catch (MongoDBException $e) {
            error_log('Erreur de connexion a MongoDB : ' . $e->getMessage());
            self::$mongoClient = null;
        }

        return self::$mongoClient;
    }

    public static function getMongoDatabase(?string $databaseName = null): ?MongoDatabase
    {
        $client = self::getMongoClient();

        if (!$client) {
            return null;
        }

        return $client->selectDatabase($databaseName ?? self::DEFAULT_MONGO_DB);
    }
}
