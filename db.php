<?php
use MongoDB\Client;
use MongoDB\Exception\Exception as MongoDBException;

// --------------------
// Connexion MySQL (Heroku via JawsDB ou fallback local)
// --------------------
$mysqlConfig = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'name' => getenv('DB_NAME') ?: 'bookshare',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];

if (($cleardbUrl = getenv('JAWSDB_URL'))) {
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
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', $mysqlConfig['host'], $mysqlConfig['name'], $mysqlConfig['charset']),
        $mysqlConfig['user'],
        $mysqlConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Erreur MySQL : ' . $e->getMessage());
}

// --------------------
// Connexion MongoDB (optionnelle)
// --------------------
$mongoClient = null;
$mongoDB = null;

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;

    $mongoUri = getenv('MONGODB_URI');
    if ($mongoUri) {
        try {
            $mongoClient = new Client($mongoUri);
            $mongoDB = $mongoClient->selectDatabase('bookshare');
        } catch (MongoDBException $e) {
            error_log('Erreur de connexion a MongoDB : ' . $e->getMessage());
        }
    } else {
        error_log('MONGODB_URI non defini; connexion MongoDB ignoree.');
    }
} else {
    error_log('Autoloader MongoDB introuvable (vendor/autoload.php).');
}
?>
