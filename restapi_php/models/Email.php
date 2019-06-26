<?php
class Email {
    const TABLE_NAME = "emails";
    const MAX_SIZE = 100;
    const CREATE = "create";
    const UPDATE = "update";
    const DELETE = "delete";
    private $conn;
    
    public $error;
    
    public $oldEmail;
    public $email;
    public $contactId;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->error = array();
    }
    
    public function create() {
        $query = "INSERT INTO " . Email::TABLE_NAME . " " .
                 "SET " .
                    "email = :email, " .
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
                 "WHERE email LIKE :email ";
        if ($this->contactId != null) {
            $query .= "AND contact_id = :contactId";
        }
        $email = "%" . $this->email . "%";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
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
        
        $stmt->closeCursor();
        
        return $result;
    }
    
    public function update() {
        $query = "UPDATE " . Email::TABLE_NAME . " " .
                 "SET " .
                    "email = :email, " .
                    "contact_id = :contactId " .
                 "WHERE " .
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
    
    private function isOldEmailCorrect() {
        $query = "SELECT email " . 
                 " FROM " . self::TABLE_NAME . " " . 
                 "WHERE contact_id = :contactId " . 
                 "AND email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":contactId", $this->contactId);
        $stmt->bindParam(":email", $this->oldEmail);
        if ($stmt->execute()) {
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return true;
            }
        } else {
            $this->error[] = "Verifictaion of old email: Something went wrong. Try again later.";
        }
        
        $stmt->closeCursor();
        
        return false;
    }
    
    public function validate($scenario = null) {
        $options = array(
            "options" => array(
                "min_range" => 1
            )
        );
        
        if (filter_var($this->contactId, FILTER_VALIDATE_INT, $options) || $this->contactId == null) {
            $this->contactId = (int)$this->contactId;
        } else {
            $this->error[] = "Contact ID is not a positive number.";
        }
        
        if ($scenario === self::CREATE || $scenario !== self::UPDATE) {
            $this->validateEmail();
        }
        if ($scenario === self::UPDATE) {
            if (!empty($this->oldEmail)) {
                $this->validateOldEmail();
            }
            if (!empty($this->email)) {
                $this->validateEmail();
            }
        }
        
        return $this->error;
    }
    
    private function validateEmail() {
        if (is_string($this->email)) {
            $this->email = htmlspecialchars(strip_tags($this->email));
            $id = $this->contactId;
            $this->contactId = null;
            if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
                $this->error[] = "Invalid email";
            } else if (strlen($this->email) > Email::MAX_SIZE) {
                $this->error[] = "Email: " . $this->email . " is too big";
            } else if (!empty($this->read())) {
                $this->error[] = "Email: " . $this->email . " already exist. Use another.";
            }
            $this->contactId = $id;
        } else {
            $this->error[] = "Invalid email";
        }
    }
    
    private function validateOldEmail() {
        if (is_string($this->oldEmail)) {
            $this->oldEmail = htmlspecialchars(strip_tags($this->oldEmail));
            if (!filter_var($this->oldEmail, FILTER_VALIDATE_EMAIL)) {
                $this->error[] = "Invalid previous email";
            } else if (strlen($this->email) > Email::MAX_SIZE) {
                $this->error[] = "Previous email: " . $this->oldEmail . " is too big";
            } else if (!$this->isOldEmailCorrect()) {
                $this->error[] = "The previous email: " . $this->oldEmail . " doesn't match with the contact.";
            }
        } else {
            $this->error[] = "Invalid previous email";
        }
    }
}