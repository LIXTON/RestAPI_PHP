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

if (!empty($database->errors)) {
    // set response code - 503 service unavailable
    http_response_code(503);
    
    die(json_encode($database->errors));
}

$contact = new Contact($db);
$email = new Email($db);
$phone = new PhoneNumber($db);

// get posted data
$data = json_decode(file_get_contents("php://input"));

$data->firstName = empty($data->firstName) ? null:htmlspecialchars(strip_tags($data->firstName));
$data->surName = empty($data->surName) ? null:htmlspecialchars(strip_tags($data->surName));
$data->email = empty($data->email) ? null:filter_var($data->email, FILTER_SANITIZE_EMAIL);
$data->phone = empty($data->phone) ? null:htmlspecialchars(strip_tags( filter_var(str_replace(" ", "", $data->phone), FILTER_SANITIZE_NUMBER_INT) ));

$result = $contact->read($data);

if ($result == null) {
    // set response code - 503 service unavailable
    http_response_code(503);
    
    die(json_encode(array("message" => "Unable to read Contacts.")));
}

if (!empty($result)) {
    $isError = false;
    foreach($result as $r) {
        $email->contactId = $r->contactId;
        $phone->contactId = $r->contactId;
        $r->emails = $email->read();
        $r->phoneNumbers = $phone->read();
        
        $isError = $r->emails == null || $r->phoneNumbers == null ? true:$isError;
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