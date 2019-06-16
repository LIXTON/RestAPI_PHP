<?php
class Contact {
    private const TABLE_NAME = "Contacts";
    private const MAX_SIZE_NAME = 100;
    private conn;
    
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
        $query = "INSERT INTO " . Contact::TABLE_NAME . " " .
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
                    "a.sur_name   AS surName, " .
                 "FROM " . Contact::TABLE_NAME . " AS a " . 
                 "WHERE contactId = :contactId ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":contactId", $this->contactId);
        if ($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC)
            $c->firstName = $row["firstName"];
            $c->surName = $row["surName"];
            
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
                 "FROM " . Contact::TABLE_NAME . " AS a " . 
                 "LEFT JOIN Emails AS b ON contactId = b.contact_id " .
                 "LEFT JOIN PhoneNumbers AS c ON contactId = c.contact_id " . 
                 "WHERE firstName LIKE %:firstName% " .
                 "AND surName LIKE %:surName% " .
                 "AND email LIKE %:email% " . 
                 "AND phone LIKE %:phone% ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":firstName", $filter->firstName);
        $stmt->bindParam(":surName", $filter->surName);
        $stmt->bindParam(":email", $filter->email);
        $stmt->bindParam(":phone", $filter->phone);
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
        $query = "UPDATE " . Contact::TABLE_NAME . " " .
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
        $query = "DELETE FROM " . Contact::TABLE_NAME . " WHERE contact_id = :contactId";
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
            $this->error[] = "ContactId is not an Integer.";
        }
        
        $this->firstName = htmlspecialchars(strip_tags($this->firstName));
        if (strlen($this->firstName) > Contact::MAX_SIZE_NAME) {
            $this->error[] = "First Name is too big.";
        }
        
        $this->surName = htmlspecialchars(strip_tags($this->surName));
        if (strlen($this->surName) > Contact::MAX_SIZE_NAME) {
            $this->error[] = "Sru Name is too big.";
        }
        
        if (is_array($this->emails)) {
            if (!empty($this->emails)) {
                foreach($this->emails as $e) {
                    if ($e instanceof Email) {
                        $this->error[$e->email] = $e->validate();
                    } else {
                        $this->error[] = "Invalid instance of Email.";
                    }
                }
            } else {
                $this->error[] = "Must has at least one email.";
            }
        }
        if (is_array($this->phoneNumbers)) {
            if (!empty($this->phoneNumbers)) {
                foreach($this->emails as $e) {
                    if ($e instanceof PhoneNumber) {
                        $this->error[$e->phone] = $e->validate();
                    } else {
                        $this->error[] = "Invalid instance of PhoneNumber.";
                    }
                }
            } else {
                $this->errors[] = "Must has at least one phone."
            }
        }
        
        return $this->error;
    }
}