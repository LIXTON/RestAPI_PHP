<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
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

$contact->contactId = isset($_GET['id']) ? $_GET['id'] : die();

if ($contact->delete()) {
    // set response code - 201 created
    http_response_code(201);

    // tell the user
    echo json_encode(array("message" => "Contact was deleted."));
} else {
    // set response code - 503 service unavailable
    http_response_code(503);

    die(json_encode(array("message" => "Unable to delete.")));
}
?>