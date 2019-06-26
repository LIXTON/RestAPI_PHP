<?php
/*
JSON INPUT:
{
    "firstName" : "value",
    "surName" : "value",
    "email" : "value",
    "phoneNumber" : "value"
}
DOESN'T MATTER THE ORDER OR IF SOME VALUE IS MISSING
It can be empty
*/
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

if (!empty(array_filter($database->error))) {
    // set response code - 503 service unavailable
    http_response_code(503);
    
    die(json_encode($database->error));
}

$contact = new Contact($db);

// get posted data
$data = json_decode(file_get_contents("php://input"));

$data = empty($data) ? new StdClass:$data;

if (property_exists($data, "firstName")) {
    $data->firstName = is_string($data->firstName) || is_numeric($data->firstName) ?
        htmlspecialchars(strip_tags($data->firstName)):null;
} else {
    $data->firstName = null;
}
if (property_exists($data, "surName")) {
    $data->surName = is_string($data->surName) || is_numeric($data->surName) ?
        htmlspecialchars(strip_tags($data->surName)):null;
} else {
    $data->surName = null;
}
if (property_exists($data, "email")) {
    $data->email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
} else {
    $data->email = null;
}
if (property_exists($data, "phoneNumber")) {
    $data->phoneNumber = is_string($data->phoneNumber) || is_numeric($data->phoneNumber) ?
        filter_var( htmlspecialchars( strip_tags(str_replace(" ", "", $data->phoneNumber))), FILTER_SANITIZE_NUMBER_INT):null;
} else {
    $data->phoneNumber = null;
}

$result = $contact->read($data);

if ($result === null) {
    // set response code - 503 service unavailable
    http_response_code(503);
    
    die(json_encode(array("message" => "Unable to read Contacts.")));
}

if (!empty($result)) {
    $isError = false;
    
    foreach($result as $r) {
        $email = new Email($db);
        $phone = new PhoneNumber($db);
        $email->contactId = $r->contactId;
        $phone->contactId = $r->contactId;
        $r->emails = $email->read();
        $r->phoneNumbers = $phone->read();
        
        $isError = $isError || $r->emails == null || $r->phoneNumbers == null;
    }
    
    if ($isError) {
        // set response code - 503 service unavailable
        http_response_code(503);
        
        die(json_encode(array("message" => "Unable to read Phone/Email.")));
    }
    
    // set response code - 200 OK
    http_response_code(200);
 
    // make it json format
    echo json_encode($result);
} else {
    // set response code - 404 Not found
    http_response_code(404);
 
    // tell the user product does not exist
    echo json_encode(array("message" => "Contact does not exist."));
}
?>