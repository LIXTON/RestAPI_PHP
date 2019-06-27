<?php
/*
    REQUIRE ID FROM GET
    JSON INPUT:
    {
        "firstName" : "value",
        "surName" : "value",
        "emails" : [
            {
                "oldEmail" : "value",
                "email" : "value"
            },
            {
                "oldEmail" : "value",
                "email" : "value"
            },
            ...
        ],
        "phoneNumbers" : [
            {
                "oldPhone" : "value",
                "phone" : "value"
            },
            {
                "oldPhone" : "value",
                "phone" : "value"
            },
            ...
        ]
    }
    MUST HAVE EACH VALUE
    phone and email MUST HAVE AT LEAST ONE VALUE IN ARRAY
    EACH phone and email MUST HAVE THEIR VALUES
*/

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// get database connection
include_once '../config/Database.php';
 
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

$contact->contactId = isset($_GET['id']) ? $_GET['id'] : die();

// get posted data
$data = json_decode(file_get_contents("php://input"));

$data = empty($data) ? new StdClass:$data;

$isValid = true;

if (!empty($data->emails) && is_array($data->emails)) {
    foreach($data->emails as $element) {
        if (!property_exists($element, "oldEmail") || !property_exists($element, "email")) {
            $isValid = false;
            break;
        }
    }
} else {
    $isValid = false;
}

if (!empty($data->phoneNumbers) && is_array($data->phoneNumbers)) {
    foreach($data->phoneNumbers as $element) {
        if (!property_exists($element, "oldPhone") || !property_exists($element, "phone")) {
            $isValid = false;
            break;
        }
    }
} else {
    $isValid = false;
}

// make sure data is not empty
if (
    !empty($data->firstName) &&
    !empty($data->surName)
) {
    // set product property values
    $contact->firstName = $data->firstName;
    $contact->surName = $data->surName;
    if (!empty($data->emails) && is_array($data->emails)) {
        foreach($data->emails as $e) {
            $email = new Email($db);
            $email->contactId = $contact->contactId;
            if (!empty($e->oldEmail)) {
                $email->oldEmail = $e->oldEmail;
            }
            if (!empty($e->email)) {
                $email->email = $e->email;
            }
            $contact->emails[] = $email;
        }
    }
    
    if (!empty($data->phoneNumbers) && is_array($data->phoneNumbers)) {
        foreach($data->phoneNumbers as $e) {
            $phone = new PhoneNumber($db);
            $phone->contactId = $contact->contactId;
            if (!empty($e->oldPhone)) {
                $phone->oldPhone = $e->oldPhone;
            }
            if (!empty($e->phone)) {
                $phone->phone = $e->phone;
            }
            $contact->phoneNumbers[] = $phone;
        }
    }
    
    $contact->validate(Contact::UPDATE);
    if (!empty($contact->error)) {
        // set response code - 400 bad request
        http_response_code(400);
        
        die(json_encode(array("message" => $contact->error)));
    }
    
    if ($contact->update()) {
        $isError = false;
        foreach($contact->emails as $e) {
            $lastWord = "update";
            if (!empty($e->email) && !empty($e->oldEmail)) {
                $isError = $isError || !$e->update();
            } else if (empty($e->oldEmail) && !empty($e->email)) {
                $isError = $isError || !$e->create();
                $lastWord = "create";
            } else if (!empty($e->oldEmail) && empty($e->email)) {
                $e->email = $e->oldEmail;
                $isError = $isError || !$e->deleteByEmail();
                $lastWord = "delete";
            }
            if ($isError) {
                $contact->error[$e->email] = "Email: " . $e->email . " unable to " . $lastWord;
            }
        }
        foreach($contact->phoneNumbers as $e) {
            $lastWord = "update";
            if (!empty($e->phone) && !empty($e->oldPhone)) {
                $isError = $isError || !$e->update();
            } else if (empty($e->oldPhone) && !empty($e->phone)) {
                $isError = $isError || !$e->create();
                $lastWord = "create";
            } else if (!empty($e->oldPhone) && empty($e->phone)) {
                $e->phone = $e->oldPhone;
                $isError = $isError || !$e->deleteByPhone();
                $lastWord = "delete";
            }
            if ($isError) {
                $contact->error[$e->phone] = "Phone Number: " . $e->phone . " unable to " . $lastWord;
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