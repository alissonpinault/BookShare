<?php

declare(strict_types=1);

use Bookshare\Services\Auth\LogoutService;

LogoutService::logout();

header('Location: index.php');
exit;
