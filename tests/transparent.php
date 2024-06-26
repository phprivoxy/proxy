<?php

use PHPrivoxy\Core\Server;
use PHPrivoxy\Proxy\Transparent;

require_once __DIR__ . '/../vendor/autoload.php';

$handler = new Transparent();
new Server($handler);
