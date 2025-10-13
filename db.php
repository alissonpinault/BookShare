<?php

declare(strict_types=1);

$services = require __DIR__ . '/src/bootstrap.php';
$pdo = $services['pdo'];
$mongoDB = $services['mongoDB'] ?? null;

return $services;
