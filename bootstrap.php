<?php

require __DIR__.'/vendor/autoload.php';
use Dotenv\Dotenv;
use Services\Database;

$dotenv = new Dotenv(__DIR__);
$dotenv->safeLoad();

$pdoConnection = (new Database())->connect();