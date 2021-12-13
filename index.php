<?php

use Controllers\EmployerController;
use Controllers\LoginController;
use Controllers\RegisterController;
use Services\Database;
use Controllers\JobSeekerController;

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
       case 'jobSeekers':
           $uid = null;
           if (isset($uri[2])) {
               $uid = (string) $uri[2];
           }
           $jobSeeker = new JobSeekerController($connection->connect(), $requestMethod, $uid);
           try {
               $jobSeeker->processRequest();
           } catch (Exception $e) {
           }
           break;
       case 'register':
           if (isset($uri[2])) {
               header("HTTP/1.1 404 Not Found");
           } else {
               $register = new RegisterController($connection->connect(), $requestMethod);
               $register->processRequest();
           }
           break;
       case 'login':
           if (isset($uri[2])) {
               header("HTTP/1.1 404 Not Found");
           } else {
               $login = new LoginController($connection->connect(), $requestMethod);
               $login->processRequest();
           }
           break;
       case 'employers':
           $uid = null;
           if (isset($uri[2])) {
               $uid = (string) $uri[2];
           }
           $employer = new EmployerController($connection->connect(), $requestMethod, $uid);
           try {
               $employer->processRequest();
           } catch (Exception $e) {
           }
           break;
       default: header("HTTP/1.1 404 Not Found");
           exit();
   }
}
