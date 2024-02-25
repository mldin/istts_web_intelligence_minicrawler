<?php

class Connection 
{
    // Credential
    private $host = 'localhost';
	private $dbname = 'db_pergi_kuliner';
	private $username = 'root';
	private $password = 'admin123';

    // Reusable Connection
    private $conn;

    // Create connection first on create class
    public function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        try {
            // Connect to mysql
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);

            // Check if error
            if ($this->conn->connect_errno)
                throw new Exception($this->conn->connect_error);

        } catch (Exception $e) {
            // Check throw error
            echo "Failed to connect to MySQL: " . $e->getMessage();
            exit();
        }
    }

    public function connection()
    {
        // Return connection
        return $this->conn;
    }
}

?>