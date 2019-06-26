<?php
class Database{
 
    // specify your own database credentials
    private $host = "localhost";
    private $db_name = "rest_api";
    private $username = "root";
    private $password = "123";
    public $conn;
    public $error;
 
    public function __construct() {
        $this->error = array("msg" => "");
    }
    
    // get the database connection
    public function getConnection(){
 
        $this->conn = null;
 
        try{
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        }catch(PDOException $exception){
            $error["msg"] .= "Connection error: " . $exception->getMessage();
        }
 
        return $this->conn;
    }
}
?>