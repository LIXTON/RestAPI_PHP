<?php
/*
JSON INPUT:
{
    "firstName" : "value",
    "surName" : "value",
    "emails" : ["value1", "value2", ...],
    "phoneNumbers" : ["value1", "value2", ...]
}
MUST HAVE EACH VALUE
FOR phone AND email MUST HAVE AT LEAST ONE VALUE
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

// make sure data is not empty
if (
    !empty($data->firstName) &&
    !empty($data->surName) &&
    !empty($data->emails) &&
    !empty($data->phoneNumbers) &&
    is_array($data->emails) &&
    is_array($data->phoneNumbers)
) {
    // set product property values
    $contact->firstName = $data->firstName;
    $contact->surName = $data->surName;
    foreach($data->emails as $e) {
        $email = new Email($db);
        $email->email = $e;
        $contact->emails[] = $email;
    }
    foreach($data->phoneNumbers as $e) {
        $phone = new PhoneNumber($db);
        $phone->phone = $e;
        $contact->phoneNumbers[] = $phone;
    }
    
    // Validate data
    $contact->validate(Contact::CREATE);
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
            if ($isError) {
                $contact->error[$e->email] = "Email: " . $e->email . " unable to register";
            }
        }
        foreach($contact->phoneNumbers as $e) {
            $e->contactId = $contact->contactId;
            $isError = $isError || !$e->create();
            if ($isError) {
                $contact->error[$e->phone] = "Phone number: " . $e->phone . " unable to register";
            }
        }
        
        if ($isError) {
            // set response code - 503 service unavailable
            http_response_code(503);
            
            die(json_encode(array("message" => $contact->error)));
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