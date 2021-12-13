<?php


namespace Controllers;


use Exception;
use PDO;
use PDOException;
use Services\Validation;

class EmployerController
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
                    $response = $this->fetchEmployerById($this->uid);
                } else {
                    $response = $this->fetchAllEmployers();
                }
                break;
            case 'POST':
                $response = $this->addNewEmployer();
                break;
            case 'PUT':
                $response = $this->updateEmployer($this->uid);
                break;
            case 'DELETE':
                $response = $this->deleteEmployer($this->uid);
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
     * @param $uid
     * @return mixed
     * Checks to make sure that the requested jobseeker data exists
     *
     */
    public function find($uid)
    {
        $query = "
      SELECT
        first_name, last_name, email, contact_number
      FROM
        technoworld.employer
      WHERE uid = :uid;
    ";

        try {
            $statement = $this->pdo->prepare($query);
            $statement->execute(array('uid' => $uid));
            return $statement->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    /**
     * Fetches all users
     * @return array
     */
    public function fetchAllEmployers(): array
    {
        try {
            $sql = "SELECT * FROM technoworld.jobseeker";
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
    public function addNewEmployer(): array
    {
        $validate = new Validation();
        $input = (array)json_decode(file_get_contents('php://input'), TRUE);
        if ($validate->validatePost($input)) {
            return $validate->unprocessableEntityResponse();
        }

        $sql = "INSERT INTO technoworld.employer (uid, first_name, last_name, company_name, contact_number, email)
                     VALUES (:uid, :firstName, :lastName, :companyName, :contactNumber, :email)";
        $uid = uniqid();
        $firstName = $validate->inputValidation($input['firstName']);
        $lastName = $validate->inputValidation($input['lastName']);
        $companyName = $validate->inputValidation($input['companyName']);
        $contactNumber = $validate->inputValidation($input['contactNumber']);
        $email = $validate->inputValidation($input['email']);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':uid', $uid);
            $stmt->bindParam(':firstName', $firstName);
            $stmt->bindParam(':lastName', $lastName);
            $stmt->bindParam(':companyName', $companyName);
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
     * @param $uid
     * @return array
     */
    public function fetchEmployerById($uid): array
    {
        try {
            $sql = "SELECT * FROM technoworld.employer WHERE uid = :uid";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':uid', $uid);
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
    public function updateEmployer($userID): array
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
        $companyName = $validate->inputValidation($input['companyName']);
        $contactNumber = $validate->inputValidation($input['contactNumber']);
        $email = $validate->inputValidation($input['email']);

        try {
            $sql = 'UPDATE technoworld.employer
                     SET first_name= :firstName,
                         last_name= :lastName,
                         company_name= :companyName,
                         contact_number= :contactNumber,
                         email= :email
                     WHERE uid';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':firstName', $firstName);
            $stmt->bindParam(':lastName', $lastName);
            $stmt->bindParam('companyName', $companyName);
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
     * @param $uid
     * @return array
     */
    public function deleteEmployer($uid): array
    {
        $result = $this->find($uid);
        if(! $result) {
            (new Validation())->notFoundResponse();
        }
        try {
            $sql = 'DELETE FROM technoworld.employer WHERE uid = :uid';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':uid', $uid);
            $stmt->execute();
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode(['message' => 'User Deleted!']);
        return $response;
    }

}