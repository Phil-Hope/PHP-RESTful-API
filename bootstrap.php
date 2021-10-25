<?php

require __DIR__.'/vendor/autoload.php';
use Dotenv\Dotenv;
use Services\DBModel;

$dotenv = new Dotenv(__DIR__);
$dotenv->safeLoad();

$pdoConnection = (new DBModel())->connect();