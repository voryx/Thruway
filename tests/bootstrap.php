<?php

ini_set("xdebug.max_nesting_level","200");

if (file_exists($file = __DIR__.'/../vendor/autoload.php')) {
    $loader = require $file;
    $loader->addPsr4('Thruway\\', __DIR__);
} else {
    throw new RuntimeException('Install dependencies to run test suite.');
}
