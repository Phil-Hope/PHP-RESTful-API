<?php

namespace Controllers;

use PDO;
use Services\Validation;

class LoginController
{
    private PDO $pdo;
    private string $requestMethod;
    public function __construct($pdo, $requestMethod) {
        $this->pdo = $pdo;
        $this->requestMethod = $requestMethod;
    }

    function processRequest() {
        switch($this->requestMethod) {
            case 'POST': $response = $this->login();
                break;
            default:
                $response = (new Validation())->notFoundResponse();
                break;
        }
        header($response['status_code_header']);
        if ($response['body']) {
            echo $response['body'];
        }
    }

    function login() {

    }
}