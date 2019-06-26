<?php
class Database{
 
    // specify your own database credentials
    private $host = "remotemysql.com";
    private $port = "3306";
    private $db_name = "Q8iLAx5esI";//"rest_api";
    private $username = "Q8iLAx5esI";//"root";
    private $password = "an4aszYpmJ";
    public $conn;
    public $error;
 
    public function __construct() {
        $this->error = array("msg" => "");
    }
    
    // get the database connection
    public function getConnection(){
 
        $this->conn = null;
 
        try{
            $this->conn = new PDO("mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        }catch(PDOException $exception){
            $error["msg"] .= "Connection error: " . $exception->getMessage();
        }
 
        return $this->conn;
    }
}
?>