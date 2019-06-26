<?php
//ONLY REQUIRE THE ID FROM GET

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
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

if (!empty(array_filter($database->error))) {
    // set response code - 503 service unavailable
    http_response_code(503);
    
    die(json_encode($database->error));
}

$contact = new Contact($db);
$email = new Email($db);
$phone = new PhoneNumber($db);

// get posted data
$contact->contactId = isset($_GET['id']) ? $_GET['id'] : die();

if (!$contact->readOne()) {
    // set response code - 503 service unavailable
    http_response_code(503);
    
    die(json_encode(array("message" => "Unable to read Contact.")));
}

if ($contact->firstName != null) {
    $isError = false;
    $email->contactId = $contact->contactId;
    $phone->contactId = $contact->contactId;
    $contact->emails = $email->read();
    $contact->phoneNumbers = $phone->read();
    
    if ($contact->emails == null || $contact->phoneNumbers == null) {
        // set response code - 503 service unavailable
        http_response_code(503);
        
        die(json_encode(array("message" => "Unable to read phones and/or emails.")));
    }
    
    // set response code - 200 OK
    http_response_code(200);
 
    // make it json format
    echo json_encode($contact);
} else {
    // set response code - 404 Not found
    http_response_code(404);
 
    // tell the user product does not exist
    echo json_encode(array("message" => "Contact does not exist."));
}
?>