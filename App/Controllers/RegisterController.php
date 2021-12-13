<?php


namespace Controllers;


use PDO;
use PDOException;
use Services\Validation;

/**
 * Class RegisterController
 * @package Controllers
 */
class RegisterController
{
    /**
     * @var PDO
     */
    private PDO $pdo;
    /**
     * @var string
     */
    private string $requestMethod;

    /**
     * RegisterController constructor.
     * @param $pdo
     * @param $requestMethod
     */
    public function __construct($pdo, $requestMethod) {
        $this->pdo = $pdo;
        $this->requestMethod = $requestMethod;
    }

    function processRequest() {
        switch($this->requestMethod) {
            case 'POST': $response = $this->registerUser();
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

    /**
     * @return array
     */
    function registerUser(): array
    {
        $validate = new Validation();
        $input = (array)json_decode(file_get_contents('php://input'), TRUE);
        if ($validate->validatePost($input)) {
            return $validate->unprocessableEntityResponse();
        }
        $email = $validate->inputValidation($input['email']);
        $password = $validate->inputValidation($input['password']);
        $role = $validate->inputValidation($input['role']);
        $hashed = password_hash($password, PASSWORD_ARGON2I);
        $sql = "INSERT INTO technoworld.login (email, password, role)
                VALUES (:email, :password, :role)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed);
            $stmt->bindParam(':role', $role);
            $stmt->execute();
        } catch (PDOException $PDOException) {
            $response['status_code_header'] = 'HTTP/1.1 500 Internal Server Error';
            $response['body'] = json_encode(['message' => 'Request Failed: '.$PDOException->getMessage()]);
            return $response;
        }
        $response['status_code_header'] = 'HTTP/1.1 201 Created';
        $response['body'] = json_encode(['message' => 'Registration Successful']);
        return $response;
    }
}