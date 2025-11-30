<?php

declare(strict_types=1);

use Bookshare\Services\Auth\LogoutService;

$container = require dirname(__DIR__) . '/src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    LogoutService::logout();
}

header('Location: index.php');
exit;
