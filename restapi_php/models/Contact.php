<?php
class Contact {
    const TABLE_NAME = "contacts";
    const MAX_SIZE_NAME = 100;
    const MIN_SIZE_NAME = 0;
    const CREATE = "create";
    const UPDATE = "update";
    const DELETE = "delete";
    private $conn;
    
    public $error;
    
    public $contactId;
    public $firstName;
    public $surName;
    public $emails;
    public $phoneNumbers;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->error = array();
    }
    
    public function create() {
        $query = "INSERT INTO " . self::TABLE_NAME . " " .
                 "SET " .
                    "first_name = :firstName, " .
                    "sur_name = :surName ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":firstName", $this->firstName);
        $stmt->bindParam(":surName", $this->surName);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return null;
    }
    
    public function readOne() {
        $query = "SELECT " .
                    "a.first_name AS firstName, " .
                    "a.sur_name   AS surName " .
                 "FROM " . self::TABLE_NAME . " AS a " . 
                 "WHERE a.contact_id = :contactId ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":contactId", $this->contactId);
        if ($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->firstName = $row["firstName"];
            $this->surName = $row["surName"];
            
            return true;
        }
        
        return false;
    }
    
    public function read($filter) {
        $result = array();
        $query = "SELECT " .
                    "a.contact_id AS contactId, " .
                    "a.first_name AS firstName, " .
                    "a.sur_name   AS surName, " .
                    "b.email      AS email, " .
                    "c.phone      AS phone " .
                 "FROM " . self::TABLE_NAME . " AS a " . 
                 "LEFT JOIN emails AS b ON a.contact_id = b.contact_id " .
                 "LEFT JOIN phonenumbers AS c ON a.contact_id = c.contact_id " . 
                 "WHERE a.first_name LIKE :firstName " .
                 "AND a.sur_name LIKE :surName " .
                 "AND b.email LIKE :email " . 
                 "AND c.phone LIKE :phone ";
        
        $filter->firstName = "%" . $filter->firstName . "%";
        $filter->surName = "%" . $filter->surName . "%";
        $filter->email = "%" . $filter->email . "%";
        $filter->phoneNumber = "%" . $filter->phoneNumber . "%";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":firstName", $filter->firstName);
        $stmt->bindParam(":surName", $filter->surName);
        $stmt->bindParam(":email", $filter->email);
        $stmt->bindParam(":phone", $filter->phoneNumber);
        if ($stmt->execute()) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row["contactId"] != $this->contactId) {
                    $this->contactId = $row["contactId"];
                    $this->firstName = $row["firstName"];
                    $this->surName = $row["surName"];
                    array_push($result, clone $this);
                }
            }
        } else {
            return null;
        }
        
        return $result;
    }
    
    public function update() {
        $query = "UPDATE " . self::TABLE_NAME . " " .
                 "SET " .
                    "first_name = :firstName, " .
                    "sur_name = :surName " .
                 "WHERE " .
                    "contact_id = :contactId ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":firstName", $this->firstName);
        $stmt->bindParam(":surName", $this->surName);
        $stmt->bindParam(":contactId", $this->contactId);
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function delete() {
        $query = "DELETE FROM " . self::TABLE_NAME . " WHERE contact_id = :contactId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":contactId", $this->contactId);
        if ($stmt->execute()) {
            return true;
        }
        
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
        
        if (is_string($this->firstName)) {
            $this->firstName = $this->sanitizeString($this->firstName);
            if (strlen($this->firstName) > Contact::MAX_SIZE_NAME) {
                $this->error[] = "First Name is too big.";
            } else if (strlen($this->firstName) <= Contact::MIN_SIZE_NAME) {
                $this->error[] = "First Name is too small.";
            }
        } else {
            $this->error[] = "First Name is not a proper name.";
        }
        
        if (is_string($this->surName)) {
            $this->surName = $this->sanitizeString($this->surName);
            if (strlen($this->surName) > Contact::MAX_SIZE_NAME) {
                $this->error[] = "Sur Name is too big.";
            } else if (strlen($this->surName) <= Contact::MIN_SIZE_NAME) {
                $this->error[] = "Sur Name is too small.";
            }
        } else {
            $this->error[] = "Sur Name is not a proper name.";
        }
        
        if ($scenario === self::CREATE || $scenario !== self::UPDATE) {
            if (!empty($this->emails) && is_array($this->emails)) {
                foreach($this->emails as $key => $email) {
                    $key = "email " . $key;
                    if ($email instanceof Email) {
                        $error = $email->validate($scenario);
                        if (!empty($error)) {
                            $this->error[$key] = $error;
                        }
                    } else {
                        $this->error[$key] = ["Invalid instance email"];
                    }
                }
            } else {
                $this->error[] = "Must has at least one email";
            }
            
            if (!empty($this->phoneNumbers) && is_array($this->phoneNumbers)) {
                foreach($this->phoneNumbers as $key => $phone) {
                    $key = "phone " . $key;
                    if ($phone instanceof PhoneNumber) {
                        $error = $phone->validate($scenario);
                        if (!empty($error)) {
                            $this->error[$key] = $error;
                        }
                    } else {
                        $this->error[$key] = ["Invalid instance phone"];
                    }
                }
            } else {
                $this->error[] = "Must has at least one phone number";
            }
        }
        
        if ($scenario === self::UPDATE) {
            $countDelete = 0;
            if (is_array($this->emails)) {
                foreach($this->emails as $key => $email) {
                    $key = "email " . $key;
                    if ($email instanceof Email) {
                        $error = $email->validate($scenario);
                        if (!empty($error)) {
                            $this->error[$key] = $error;
                        }
                        $countDelete = empty($email->email) ? $countDelete++:$countDelete;
                    } else {
                        $this->error[$key] = ["Invalid instance email"];
                    }
                }
                if ($countDelete > 0 && $countDelete == count($this->emails)) {
                    $this->error[] = "An email must remain";
                    $countDelete = 0;
                }
            } else {
                $this->emails = array();
            }
            
            if (is_array($this->phoneNumbers)) {
                foreach($this->phoneNumbers as $key => $phone) {
                    $key = "phone " . $key;
                    if ($phone instanceof PhoneNumber) {
                        $error = $phone->validate($scenario);
                        if (!empty($error)) {
                            $this->error[$key] = $error;
                        }
                        $countDelete = empty($phone->phone) ? $countDelete++:$countDelete;
                    } else {
                        $this->error[$key] = ["Invalid instance phone"];
                    }
                }
                if ($countDelete > 0 && $countDelete == count($this->phoneNumbers)) {
                    $this->error[] = "A phone number must remain";
                    $countDelete = 0;
                }
            } else {
                $this->phoneNumbers = array();
            }
        }
        
        return $this->error;
    }
    
    private function sanitizeString($result) {
        $result = htmlspecialchars(strip_tags($result));
        $result = trim(preg_replace('/\s+/', ' ', $result));
        return $result;
    }
}