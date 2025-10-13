<?php

declare(strict_types=1);

use Bookshare\Config\Database;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$pdo = Database::getPdo();
$mongoDB = Database::getMongoDatabase();

return [
    'pdo' => $pdo,
    'mongoDB' => $mongoDB,
];
