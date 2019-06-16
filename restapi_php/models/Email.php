<?php
class PhoneNumber {
    private const TABLE_NAME = "Emails";
    private const MAX_SIZE = 100;
    private conn;
    
    public $error;
    
    public $oldEmail;
    public $email;
    public $contactId;
    //public $created;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->error = array();
    }
    
    public function create() {
        $query = "INSERT INTO " . Email::TABLE_NAME . " " .
                 "SET " .
                    "email = :email " .
                    "contact_id = :contactId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":contactId", $this->contactId);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    public function read() {
        $result = array();
        $query = "SELECT " . 
                    "contact_id AS contactId, " .
                    "email " .
                 "FROM " . Email::TABLE_NAME . " " .
                 "WHERE email LIKE %:email% ";
        if ($this->contactId != null) {
            $query .= "AND contact_id = :contactId";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        if ($this->contactId != null) {
            $stmt->bindParam(":contactId", $this->contactId);
        }
        if ($stmt->execute()) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->contactId = $row["contactId"];
                $this->email = $row["email"];
                $this->oldEmail = $row["email"];
                array_push($result, clone $this);
            }
        } else {
            return null;
        }
        return $result;
    }
    
    public function update() {
        $query = "UPDATE " . Email::TABLE_NAME . " " .
                 "SET " .
                    "email = :email, " .
                    "contact_id = :contactId " .
                 "WHERE "
                    "email = :oldEmail";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":contactId", $this->contactId);
        $stmt->bindParam(":oldEmail", $this->oldEmail);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    public function deleteByEmail() {
        $query = "DELETE FROM " . Email::TABLE_NAME . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    public function deleteByContact() {
        $query = "DELETE FROM " . Email::TABLE_NAME . " WHERE contact_id = :contactId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":contactId", $this->contactId);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    public function validate() {
        if (preg_match("/^\d+$/", $this->contactId) || $this->contactId == null) {
            $this->contactId = (int)$this->contactId;
        } else {
            $this->error[] = "ContactId is not an Integer";
        }
        
        $this->email = htmlspecialchars(strip_tags($this->email));
        if (strlen($this->email) > Email::MAX_SIZE) {
            $this->error[] = "Email is too big";
        } else if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->error[] = "Invalid email";
        }
        
        $this->oldEmail = htmlspecialchars(strip_tags($this->oldEmail));
        if (strlen($this->email) > Email::MAX_SIZE) {
            $this->error[] = "Previous email is too big";
        } else if (!filter_var($this->oldEmail, FILTER_VALIDATE_EMAIL) && $this->oldEmail != null) {
            $this->error[] = "Invalid previous email";
        }
        
        return $this->error;
    }
}