<?php

$autoloadFile = dirname(__FILE__, 2) . '/vendor/autoload.php';
if (!is_readable($autoloadFile)) {
    echo $autoloadFile;

    throw new RuntimeException('Unable to find composer autoload files. Run `composer install` and try again');
}

require_once $autoloadFile;
