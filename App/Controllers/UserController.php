<?php


namespace Controllers;


use Exception;
use PDO;
use PDOException;
use Services\Validation;

class UserController
{
    private PDO $pdo;
    private $requestMethod;
    private ?int $uid;

    /**
     * JobSeekerController constructor.
     * When this class object is instantiated it creates a new Database
     * And initializes the return PDO object to the local $pdo variable
     * @param PDO $pdo
     * @param mixed $requestMethod
     * @param int|null $uid
     */
    public function __construct(PDO $pdo, $requestMethod, ?int $uid)
    {
        $this->requestMethod = $requestMethod;
        $this->uid = $uid;
        $this->pdo = $pdo;
    }

    /**
     * Processes the request type
     * then executes the applicable function
     * @throws Exception
     */
    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                if ($this->uid) {
                    $response = $this->getUserById($this->uid);
                } else {
                    $response = $this->getAllUsers();
                }
                break;
            case 'POST':
                $response = $this->addNewUser();
                break;
            case 'PUT':
                $response = $this->updateUser($this->uid);
                break;
            case 'DELETE':
                $response = $this->deleteUser($this->uid);
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
     * @param string $email
     * @return mixed
     * Checks to make sure that the requested users data exists
     *
     */
    public function find(string $email)
    {
        $query = "
      SELECT
        firstName, lastName, email, contactNumber
      FROM
        sports.users
      WHERE email = :email;
    ";

        try {
            $statement = $this->pdo->prepare($query);
            $statement->execute(array('email' => $email));
            return $statement->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    /**
     * Fetches all users
     * @return array
     */
    public function getAllUsers(): array
    {
        try {
            $sql = "SELECT * FROM sports.users";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);
        return $response;
    }

    /**
     * Creates a new user
     *
     * @return array
     * @throws Exception
     */
    public function addNewUser(): array
    {
        $validate = new Validation();
        $input = (array)json_decode(file_get_contents('php://input'), TRUE);
        if ($validate->validatePost($input)) {
            return $validate->unprocessableEntityResponse();
        }

        $sql = "INSERT INTO sports.users (firstName, lastName, contactNumber, email)
                     VALUES (:firstName, :lastName, :contactNumber, :email)";
        $firstName = $validate->inputValidation($input['firstName']);
        $lastName = $validate->inputValidation($input['lastName']);
        $contactNumber = $validate->inputValidation($input['contactNumber']);
        $email = $validate->inputValidation($input['email']);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':firstName', $firstName);
            $stmt->bindParam(':lastName', $lastName);
            $stmt->bindParam(':contactNumber', $contactNumber);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
        $response['status_code_header'] = 'HTTP/1.1 201 Created';
        $response['body'] = json_encode(['message' => 'User Created']);
        return $response;
    }

    /**
     * Fetches a user by int $userID
     *
     * @param string $email
     * @return array
     */
    public function getUserById(string $email): array
    {
        try {
            $sql = "SELECT * FROM sports.users WHERE email = :email";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $data = $stmt->fetch();
            return [
                'status' => true,
                'data' => $data
            ];
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    /**
     * @param string $email
     * @return array
     */
    public function updateUser(string $email): array
    {
        $validate = new Validation();
        $result = $this->find($email);

        if(! $result) {
            return $validate->notFoundResponse();
        }

        $input = (array) json_decode(file_get_contents('php://input'), TRUE);

        if(! $validate->validatePost($input)) {
            return $validate->unprocessableEntityResponse();
        }
        $firstName = $validate->inputValidation($input['firstName']);
        $lastName = $validate->inputValidation($input['lastName']);
        $contactNumber = $validate->inputValidation($input['contactNumber']);
        $email = $validate->inputValidation($input['email']);

        try {
            $sql = 'UPDATE sports.users
                     SET firstName = :firstName,
                         lastName = :lastName,
                         contactNumber = :contactNumber
                     WHERE email = :email';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':firstName', $firstName);
            $stmt->bindParam(':lastName', $lastName);
            $stmt->bindParam(':contactNumber', $contactNumber);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

        } catch (PDOException $e) {
            exit($e->getMessage());
        }
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode(['message' => 'User Updated!']);
        return $response;
    }

    /**
     * @param string $email
     * @return array
     */
    public function deleteUser(string $email): array
    {
        $result = $this->find($email);
        if(! $result) {
            (new Validation())->notFoundResponse();
        }
        try {
            $sql = 'DELETE FROM sports.users WHERE email = :email';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode(['message' => 'User Deleted!']);
        return $response;
    }

}