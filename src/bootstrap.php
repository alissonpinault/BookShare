<?php

declare(strict_types=1);

use Bookshare\Config\Database;

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

if (function_exists('mb_http_output')) {
    mb_http_output('UTF-8');
}

$pdo = Database::getPdo();
$mongoDB = Database::getMongoDatabase();

return [
    'pdo' => $pdo,
    'mongoDB' => $mongoDB,
];
