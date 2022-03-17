<?php

use Controllers\UserController;
use Services\Database;

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

$requestMethod = $_SERVER["REQUEST_METHOD"];

if (isset($uri)) {
   switch ($uri[1]) {
       case 'user':
           $uid = null;
           if (isset($uri[2])) {
               $uid = (string) $uri[2];
           }
           $employer = new UserController($connection->connect(), $requestMethod, $uid);
           try {
               $employer->processRequest();
           } catch (Exception $e) {
           }
           break;
       default: header("HTTP/1.1 404 Not Found");
           exit();
   }
}
