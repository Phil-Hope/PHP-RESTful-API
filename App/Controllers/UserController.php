<?php


namespace Controllers;

use PDO;
use PDOException;
use Services\Validation;

/**
 * Class UserController
 * @package Repository
 */
class UserController
{
    private PDO $pdo;
    private $requestMethod;
    private ?int $userID;

    /**
     * UserController constructor.
     * When this class object is instantiated it creates a new DBModel
     * And initializes the return PDO object to the local $pdo variable
     * @param PDO $pdo
     * @param mixed $requestMethod
     * @param ?int $userID
     */
    public function __construct(PDO $pdo, $requestMethod, ?int $userID)
    {
        $this->requestMethod = $requestMethod;
        $this->userID = $userID;
        $this->pdo = $pdo;
    }

    /**
     * Processes the request type
     * then executes the applicable function
     */
    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                if ($this->userID) {
                    $response = $this->fetchSingleUser($this->userID);
                } else {
                    $response = $this->fetchAllUsers();
                }
                break;
            case 'POST':
                $response = $this->addNewUser();
                break;
            case 'PUT':
                $response = $this->updateUserDetails($this->userID);
                break;
            case 'DELETE':
                $response = $this->deleteUserById($this->userID);
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
     * @param $id
     * @return mixed
     * Checks to make sure that the requested user data exists
     *
     */
    public function find($id)
    {
        $query = "
      SELECT
        userID, firstName, lastName, email, contactNumber
      FROM
        sports.users
      WHERE userID = :id;
    ";

        try {
            $statement = $this->pdo->prepare($query);
            $statement->execute(array('id' => $id));
            return $statement->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    /**
     * Fetches all users
     * @return array|false|string
     */
    public function fetchAllUsers()
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
     */
    public function addNewUser(): array
    {
        $validate = new Validation();
        $input = (array)json_decode(file_get_contents('php://input'), TRUE);
        if ($validate->validatePost($input)) {
            return $validate->unprocessableEntityResponse();
        }

        $sql = "INSERT INTO sports.users (lastName, email, contactNumber, firstName)
                     VALUES (:lastName, :email, :contactNumber, :firstName)";

        $email = $validate->inputValidation($input['email']);
        $contactNumber = $validate->inputValidation($input['contactNumber']);
        $firstName = $validate->inputValidation($input['firstName']);
        $lastName = $validate->inputValidation($input['lastName']);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':lastName', $lastName);
            $stmt->bindParam(':firstName', $firstName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':contactNumber', $contactNumber);
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
     * @param $userID
     * @return array
     */
    public function fetchSingleUser($userID): array
    {
        try {
            $sql = "SELECT * FROM sports.users WHERE userID = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $userID);
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
     * @param $userID
     * @return array
     */
    public function updateUserDetails($userID): array
    {
        $validate = new Validation();
        $result = $this->find($userID);

        if(! $result) {
            return $validate->notFoundResponse();
        }

        $input = (array) json_decode(file_get_contents('php://input'), TRUE);

        if(! $validate->validatePost($input)) {
            return $validate->unprocessableEntityResponse();
        }
        $firstName = $validate->inputValidation($input['firstName']);
        $lastName = $validate->inputValidation($input['lastName']);
        $email = $validate->inputValidation($input['email']);
        $contactNumber = $validate->inputValidation($input['contactNumber']);

        try {
            $sql = 'UPDATE sports.users
                     SET firstName= :firstName,
                         lastName= :lastName,
                         contactNumber= :contactNumber,
                         email= :email
                     WHERE userID';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':firstName', $firstName);
            $stmt->bindParam(':lastName', $lastName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':contactNumber', $contactNumber);
            $stmt->execute();

        } catch (PDOException $e) {
            exit($e->getMessage());
        }
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode(['message' => 'User Updated!']);
        return $response;
    }

    /**
     * @param $userID
     * @return array
     */
    public function deleteUserById($userID): array
    {
        $result = $this->find($userID);
        if(! $result) {
            (new Validation())->notFoundResponse();
        }
        try {
            $sql = 'DELETE FROM sports.users WHERE userID = :userID';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':userID', $userID);
            $stmt->execute();
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode(['message' => 'User Deleted!']);
        return $response;
    }

}