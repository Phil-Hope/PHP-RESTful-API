<?php
use Services\Database;
use Controllers\UserController;

require  $_SERVER['DOCUMENT_ROOT'] .'/bootstrap.php';
session_start();
$connection = new Database();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

if ($uri[1] !== 'users') {
    if($uri[1] !== 'users'){
        header("HTTP/1.1 404 Not Found");
        exit();
    }
}

$requestMethod = $_SERVER["REQUEST_METHOD"];

if ($uri[1] == 'users') {

    // endpoints starting with `/users` for POST/PUT/DELETE results in a 404 Not Found
    if ($uri[1] == 'users' and isset($uri[2])) {
        header("HTTP/1.1 404 Not Found");
        exit();
    }

    $userID = null;
    if (isset($uri[2])) {
        $userID = (int) $uri[2];
    }

    $controller = new UserController($connection->connect(), $requestMethod, $userID);
    $controller->processRequest();
}
