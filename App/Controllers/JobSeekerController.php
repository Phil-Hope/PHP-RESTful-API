<?php


namespace Controllers;

use Exception;
use PDO;
use PDOException;
use Services\Validation;

/**
 * Class JobSeekerController
 * @package Repository
 */
class JobSeekerController
{
    private PDO $pdo;
    private $requestMethod;
    private ?string $uid;

    /**
     * JobSeekerController constructor.
     * When this class object is instantiated it creates a new Database
     * And initializes the return PDO object to the local $pdo variable
     * @param PDO $pdo
     * @param mixed $requestMethod
     * @param string|null $uid
     */
    public function __construct(PDO $pdo, $requestMethod, ?string $uid)
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
    public function processRequest(): array
    {
        switch ($this->requestMethod) {
            case 'GET':
                if ($this->uid) {
                    $response = $this->fetchJobSeekerById($this->uid);
                } else {
                    $response = $this->fetchAllJobSeekers();
                }
                break;
            case 'POST':
                $response = $this->addNewJobSeeker();
                break;
            case 'PUT':
                $response = $this->updateJobSeeker($this->uid);
                break;
            case 'DELETE':
                $response = $this->deleteJobSeeker($this->uid);
                break;
            default:
                $response = (new Validation())->notFoundResponse();
                break;
        }
        $response['status_code_header'] = 'HTTP/1.1 400 Bad Request Error';
        $response['body'] = json_encode(['message' => 'Request Failed: ']);
        return $response;
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
        technoworld.jobseeker
      WHERE uid = uid;
    ";
        try {
            $statement = $this->pdo->prepare($query);
            $statement->execute(array('uid' => $uid));
            $response = $statement->fetch(PDO::FETCH_ASSOC);
            header($response);
            exit();
        } catch (PDOException $e) {
            $response['status_code_header'] = 'HTTP/1.1 400 Bad Request Error';
            $response['body'] = json_encode(['message' => 'Request Failed: '.$e->getMessage()]);
            echo $response;
            exit();
        }
    }

    /**
     * Fetches all users
     * @return array
     */
    public function fetchAllJobSeekers(): array
    {
        try {
            $sql = "SELECT * FROM technoworld.jobseeker";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $response['status_code_header'] = 'HTTP/1.1 500 Internal Server Error';
            $response['body'] = json_encode(['message' => 'Request Failed: '.$e->getMessage()]);
            return $response;
        }
        header('HTTP/1.1 200 OK');
        echo $response['body'] = json_encode($result);
        exit();
    }

    /**
     * Creates a new user
     *
     * @return array
     * @throws Exception
     */
    public function addNewJobSeeker(): array
    {
        $validate = new Validation();
        $input = (array)json_decode(file_get_contents('php://input'), TRUE);
        if ($validate->validatePost($input)) {
            return $validate->unprocessableEntityResponse();
        }

        $sql = "INSERT INTO technoworld.jobseeker (uid, first_name, last_name, date_joined, contact_number, email)
                     VALUES (:uid, :firstName, :lastName, :dateJoined, :contactNumber, :email)";
        $uid = uniqid();
        $email = $validate->inputValidation($input['email']);
        $contactNumber = $validate->inputValidation($input['contactNumber']);
        $firstName = $validate->inputValidation($input['firstName']);
        $lastName = $validate->inputValidation($input['lastName']);
        $date = date_create();
        $dateJoined = date_format($date, 'Y-m-d H:i:s');
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':uid', $uid);
            $stmt->bindParam(':firstName', $firstName);
            $stmt->bindParam(':lastName', $lastName);
            $stmt->bindParam(':dateJoined', $dateJoined);
            $stmt->bindParam(':contactNumber', $contactNumber);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
        } catch (PDOException $e) {
            $response['status_code_header'] = 'HTTP/1.1 500 Internal Server Error';
            $response['body'] = json_encode(['message' => 'Create JobSeeker Failed: '.$e->getMessage()]);
            return $response;
        }

        $response['status_code_header'] = 'HTTP/1.1 201 Created';
        $response['body'] = json_encode(['message' => 'User Created']);
        return $response;
    }

    /**
     * Fetches a jobSeeker by string $uid
     *
     * @param $uid
     * @return array
     */
    public function fetchJobSeekerById($uid): array
    {
        try {
            $sql = "SELECT * FROM technoworld.jobseeker WHERE uid = :uid";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':uid', $uid);
            $stmt->execute();
            $data = $stmt->fetch();
            return [
                'status' => true,
                'data' => $data
            ];
        } catch (PDOException $e) {
            $response['status_code_header'] = 'HTTP/1.1 500 Internal Server Error';
            $response['body'] = json_encode(['message' => 'Request Failed: '.$e->getMessage()]);
            return $response;
        }
    }

    /**
     * @param $userID
     * @return array
     */
    public function updateJobSeeker($userID): array
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
        $uid = $validate->inputValidation($input['uid']);
        try {
            $sql = 'UPDATE technoworld.jobseeker
                     SET first_name= :firstName,
                         last_name= :lastName,
                         contact_number= :contactNumber,
                         email= :email
                     WHERE uid= :uid';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':uid', $uid);
            $stmt->bindParam(':firstName', $firstName);
            $stmt->bindParam(':lastName', $lastName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':contactNumber', $contactNumber);
            $stmt->execute();
        } catch (PDOException $e) {
            $response['status_code_header'] = 'HTTP/1.1 500 Internal Server Error';
            $response['body'] = json_encode(['message' => 'Request Failed: '.$e->getMessage()]);
            return $response;
        }
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode(['message' => 'User Updated!']);
        return $response;
    }

    /**
     * @param $uid
     * @return array
     */
    public function deleteJobSeeker($uid): array
    {
        $result = $this->find($uid);
        if(! $result) {
            (new Validation())->notFoundResponse();
        }
        try {
            $sql = 'DELETE FROM technoworld.jobseeker WHERE uid = :uid';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':userID', $uid);
            $stmt->execute();
        } catch (PDOException $e) {
            $response['status_code_header'] = 'HTTP/1.1 500 Internal Server Error';
            $response['body'] = json_encode(['message' => 'Request Failed: '.$e->getMessage()]);
            return $response;
        }
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode(['message' => 'User Deleted!']);
        return $response;
    }

}