<?php

namespace Services;

use PDO;
use PDOException;

require $_SERVER['DOCUMENT_ROOT'].'/bootstrap.php';

/**
 * Class DBModel
 * @package Services
 */
class DBModel {

    /**
     * @var PDO
     */
    private PDO $dbConnection;

    /**
     * DBModel constructor.
     */
    public function __construct()
    {
        $host = $_ENV['DB_HOST'];
        $port = $_ENV['DB_PORT'];
        $db   = $_ENV['DB_DATABASE'];
        $user = $_ENV['DB_USERNAME'];
        $pass = $_ENV['DB_PASSWORD'];

        try {
            $this->dbConnection = new PDO(
                "mysql:host=$host;port=$port;dbname=$db",
                $user,
                $pass
            );
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    /**
     * @return PDO
     */
    public function connect(): PDO
    {
        return $this->dbConnection;
    }
}