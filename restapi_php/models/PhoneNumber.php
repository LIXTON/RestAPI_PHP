<?php
class PhoneNumber {
    const TABLE_NAME = "phonenumbers";
    const MAX_SIZE = 15;
    const MIN_SIZE = 10;
    const CREATE = "create";
    const UPDATE = "update";
    const DELETE = "delete";
    private $conn;
    
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
        $query = "INSERT INTO " . self::TABLE_NAME . " " .
                 "SET " .
                    "phone = :phone, " .
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
                 "FROM " . self::TABLE_NAME . " " .
                 "WHERE phone LIKE :phone ";
        $phone = "%" . $this->phone . "%";
        if ($this->contactId != null) {
            $query .= "AND contact_id = :contactId";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":phone", $phone);
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
        
        $stmt->closeCursor();
        
        return $result;
    }
    
    public function update() {
        $query = "UPDATE " . self::TABLE_NAME . " " .
                 "SET " .
                    "phone = :phone, " .
                    "contact_id = :contactId " .
                 "WHERE " .
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
        $query = "DELETE FROM " . self::TABLE_NAME . " WHERE phone = :phone";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":phone", $this->phone);
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function deleteByContact() {
        $query = "DELETE FROM " . self::TABLE_NAME . " WHERE contact_id = :contactId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":contactId", $this->contactId);
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    private function isOldPhoneCorrect() {
        $query = "SELECT phone " . 
                 " FROM " . self::TABLE_NAME . " " . 
                 "WHERE contact_id = :contactId " . 
                 "AND phone = :phone";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":contactId", $this->contactId);
        $stmt->bindParam(":phone", $this->oldPhone);
        if ($stmt->execute()) {
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return true;
            }
        } else {
            $this->error[] = "Verifictaion of old phone: Something went wrong. Try again later.";
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
            $this->validatePhone();
        }
        if ($scenario === self::UPDATE) {
            if (!empty($this->oldPhone)) {
                $this->validateOldPhone();
            }
            if (!empty($this->phone)) {
                $this->validatePhone();
            }
        }
        
        return $this->error;
    }
    
    private function validatePhone() {
        if (is_string($this->phone) || is_numeric($this->phone)) {
            $id = $this->contactId;
            $this->contactId = null;
            $this->phone = htmlspecialchars(strip_tags(str_replace(" ", "", $this->phone)));
            if (!preg_match('/^\-?\d+$/', $this->phone)) {
                $this->error[] = "Invalid phone";
            } else if (strlen($this->phone) > PhoneNumber::MAX_SIZE) {
                $this->error[] = "Phone Number: " . $this->phone . " is too big";
            } else if (strlen($this->phone) < PhoneNumber::MIN_SIZE) {
                $this->error[] = "Phone Number: " . $this->phone . " is too small";
            } else if (!empty($this->read())) {
                $this->error[] = "Phone Number: " . $this->phone . " already exist. Use another.";
            }
            $this->contactId = $id;
        } else {
            $this->error[] = "Invalid phone";
        }
    }
    
    private function validateOldPhone() {
        if (is_string($this->oldPhone) || is_numeric($this->oldPhone)) {
            $this->oldPhone = htmlspecialchars(strip_tags(str_replace(" ", "", $this->oldPhone)));
            if (!preg_match('/^\-?\d+$/', $this->oldPhone)) {
                $this->error[] = "Invalid previous phone";
            } else if (strlen($this->oldPhone) > PhoneNumber::MAX_SIZE) {
                $this->error[] = "Previous phone: " . $this->oldPhone . " is too big";
            } else if (strlen($this->oldPhone) < PhoneNumber::MIN_SIZE) {
                $this->error[] = "Previous phone: " . $this->oldPhone . " is too small";
            } else if (!$this->isOldPhoneCorrect()) {
                $this->error[] = "The previous phone number: " . $this->oldPhone . " doesn't match with the contact.";
            }
        } else {
            $this->error[] = "Invalid previous phone";
        }
    }
}