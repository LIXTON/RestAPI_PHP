<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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

if (is_string($data->surName) && !empty($data->firstName)) {
    $data->surName = htmlspecialchars(strip_tags($data->surName));
} else {
    $isValid = false;
}

if (is_array($data->email) && !empty($data->email)) {
    foreach($data->email as $element) {
        $isValid = $isValid && filter_var($element, FILTER_VALIDATE_EMAIL);
    }
} else {
    $isValid = false;
}

if (is_array($data->phone) && !empty($data->phone)) {
    foreach($data->phone as &$element) {
        if (is_string($element)) {
            $element = htmlspecialchars(strip_tags(str_replace(" ", "", $element)));
        }
        $isValid = $isValid && filter_var($element, FILTER_VALIDATE_INT);
    }
} else {
    $isValid = false;
}

// make sure data is not empty
if ($isValid) {
    // set product property values
    $contact->firstName = $data->firstName;
    $contact->surName = $data->surName;
    foreach($data->email as $e) {
        $email->email = $e;
        $contact->emails[] = clone $email;
    }
    foreach($data->phone as $e) {
        $phone->phone = $e;
        $contact->phones[] = clone $phone;
    }
    
    // Validate data
    $contact->validate();
    if (!empty($contact->error)) {
        // set response code - 400 bad request
        http_response_code(400);
        
        die(json_encode(array("message" => $contact->error)));
    }
    
    $contact->contactId = $contact->create();
    
    if ($contact->contactId != null) {
        $isError = false;
        
        foreach($contact->emails as $e) {
            $e->contactId = $contact->contactId;
            $isError = $isError || !$e->create();
        }
        foreach($contact->phoneNumbers as $e) {
            $e->contactId = $contact->contactId;
            $isError = $isError || !$e->create();
        }
        
        if ($isError) {
            // set response code - 503 service unavailable
            http_response_code(503);
            die(json_encode(array("message" => "Contact was created but was Unable to create some/all of the Email/Phone.")));
        }
    } else {
        // set response code - 503 service unavailable
        http_response_code(503);
        //  ERROR TXT
        die(json_encode(array("message" => "Unable to create Contact.")));
    }
 
    // set response code - 201 created
    http_response_code(201);
 
    // tell the user
    echo json_encode(array("message" => "Contact was created."));
} else { // tell the user data is incomplete
 
    // set response code - 400 bad request
    http_response_code(400);
 
    // tell the user
    echo json_encode(array("message" => "Unable to create contact. Data is incomplete."));
}
?>