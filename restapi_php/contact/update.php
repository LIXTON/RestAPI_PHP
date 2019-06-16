<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// get database connection
include_once '../config/database.php';
 
// instantiate product object
include_once '../models/Contact.php';
include_once '../models/Email.php';
include_once '../models/PhoneNumber.php';

$database = new Database();
$db = $database->getConnection();

if (!empty($database->error)) {
    // set response code - 503 service unavailable
    http_response_code(503);
    
    die(json_encode($database->error));
}

$contact = new Contact($db);
$email = new Email($db);
$phone = new PhoneNumber($db);

$contact->contactId = isset($_GET['id']) ? $_GET['id'] : die();
$email->contactId = $contact->contactId;
$phone->contactId = $contact->contactId;

// get posted data
$data = json_decode(file_get_contents("php://input"));

$data->firstName = empty($data->firstName) ? null:$data->firstName;
$data->surName = empty($data->surName) ? null:$data->surName;
$data->email = empty($data->email) ? null:$data->email;
$data->phone = empty($data->phone) ? null:$data->phone;

$isValid = true;
if (is_string($data->firstName) && !empty($data->firstName)) {
    $data->firstName = htmlspecialchars(strip_tags($data->firstName));
} else {
    $isValid = false;
}

if (is_string($data->surName) && !empty($data->surName)) {
    $data->surName = htmlspecialchars(strip_tags($data->surName));
} else {
    $isValid = false;
}

if (is_array($data->email) && !empty($data->email)) {
    foreach($data->email as $element) {
        $element->email = empty($element->email) ? null:$element->email;
        $element->oldEmail = empty($element->oldEmail) ? null:$element->oldEmail;
        
        $isValid = $isValid && (filter_var($element->email, FILTER_VALIDATE_EMAIL) || $element->email == null);
        $isValid = $isValid && filter_var($element->oldEmail, FILTER_VALIDATE_EMAIL);
    }
} else {
    $isValid = false;
}

if (is_array($data->phone) && !empty($data->phone)) {
    foreach($data->phone as &$element) {
        $element->phone = empty($element->phone) ? null:$element->phone;
        $element->oldPhone = empty($element->oldPhone) ? null:$element->oldPhone;
        
        if (is_string($element)) {
            $element = htmlspecialchars(strip_tags(str_replace(" ", "", $data->phone)));
        }
        $isValid = $isValid && (filter_var($element->phone, FILTER_VALIDATE_INT) || $element->phone == null);
        $isValid = $isValid && filter_var($element->oldPhone, FILTER_VALIDATE_INT);
    }
} else {
    $isValid = false;
}

// make sure data is not empty
if ($isValid) {
    // set product property values
    $contact->firstName = $data->firstName;
    $contact->surName = $data->surName;
    $email->contactId = $contact->contactId;
    $phone->contactId = $contact->contactId;
    foreach($data->emails as $e) {
        $email->oldEmail = $e->oldEmail;
        $email->email = $e->email;
        $contact->emails[] = clone $email;
    }
    foreach($data->phoneNumbers as $e) {
        $phone->oldPhone = $e->oldPhone;
        $phone->phone = $e->phone;
        $contact->phoneNumbers[] = clone $phone;
    }
    
    array_walk($data->phones, function($element) {
        $phone->oldPhone = $element->oldPhone;
        $phone->phone = $element->phone;
        $contact->phones[] = clone $phone;
    });
    
    $contact->validate();
    if (!empty($contact->error)) {
        // set response code - 400 bad request
        http_response_code(400);
        
        die(json_encode(array("message" => $contact->error)));
    }
    
    if ($contact->update()) {
        $isError = false;
        foreach($contact->emails as $e) {
            $isError = $isError || $e->update();
        }
        foreach($contact->phoneNumbers as $e) {
            $isError = $isError || $e->update();
        }
        
        if ($isError) {
            // set response code - 503 service unavailable
            http_response_code(503);
            
            die(json_encode(array("message" => "Unable to update Phone/Email.")));
        }
    } else {
        // set response code - 503 service unavailable
        http_response_code(503);
        
        die(json_encode(array("message" => "Unable to update Contact.")));
    }
 
    // set response code - 201 created
    http_response_code(201);
 
    // tell the user
    echo json_encode(array("message" => "Contact was updated."));
} else { // tell the user data is incomplete
 
    // set response code - 400 bad request
    http_response_code(400);
 
    // tell the user
    echo json_encode(array("message" => "Unable to update contact. Data is incomplete."));
}
?>