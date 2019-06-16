<?php
class PhoneNumber {
    private const TABLE_NAME = "PhoneNumbers";
    private const MAX_SIZE = 15;
    private conn;
    
    public $error;
    
    public $oldPhone;
    public $phone;
    public $contactId;
    //public $created;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->error = array();
    }
    
    public function create() {
        $query = "INSERT INTO " . PhoneNumber::TABLE_NAME . " " .
                 "SET " .
                    "phone = :phone " .
                    "contact_id = :contactId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":phone", $this->phone);
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
                    "phone " .
                 "FROM " . PhoneNumber::TABLE_NAME . " " .
                 "WHERE phone LIKE %:phone% ";
        if ($this->contactId != null) {
            $query .= "AND contact_id = :contactId";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":phone", $this->phone);
        if ($this->contactId != null) {
            $stmt->bindParam(":contactId", $this->contactId);
        }
        if ($stmt->execute()) {
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->contactId = $row["contactId"];
                $this->phone = $row["phone"];
                $this->oldPhone = $row["phone"];
                array_push($result, clone $this);
            }
        } else {
            return null;
        }
        return $result;
    }
    
    public function update() {
        $query = "UPDATE " . PhoneNumber::TABLE_NAME . " " .
                 "SET " .
                    "phone = :phone, " .
                    "contact_id = :contactId " .
                 "WHERE "
                    "phone = :oldPhone";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":contactId", $this->contactId);
        $stmt->bindParam(":oldPhone", $this->oldPhone);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    public function deleteByPhone() {
        $query = "DELETE FROM " . PhoneNumber::TABLE_NAME . " WHERE phone = :phone";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":phone", $this->phone);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    public function deleteByContact() {
        $query = "DELETE FROM " . PhoneNumber::TABLE_NAME . " WHERE contact_id = :contactId";
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
        
        $this->phone = htmlspecialchars(strip_tags(str_replace(" ", "", $this->phone)));
        if (strlen($this->phone) > PhoneNumber::MAX_SIZE) {
            $this->error[] = "Email is too big";
        } else if (!filter_ver($this->phone, FILTER_VALIDATE_INT)) {
            $this->error[] = "Invalid phone";
        }
        
        $this->oldPhone = htmlspecialchars(strip_tags(str_replace(" ", "", $this->oldPhone)));
        if (strlen($this->oldPhone) > PhoneNumber::MAX_SIZE) {
            $this->error[] = "Previous email is too big";
        } else if (!filter_ver($this->oldPhone, FILTER_VALIDATE_INT) && $this->oldPhone != null) {
            $this->error[] = "Invalid previous email";
        }
        
        return $this->error;
    }
}