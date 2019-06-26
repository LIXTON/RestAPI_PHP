# RestAPI_PHP
This RESTful API is designed to imitate a Phone Number book. It is a usual CRUD.
Each operation need some appropriate data input and it will explain as follows

## Create operation
This operation is a POST and you need to send the following json format:
```
{
    "firstName" : "value",
    "surName" : "value",
    "emails" : [
        "value 1", 
        "value 2", 
        ...,
        "value n"
    ],
    "phoneNumbers" : [
        "value 1", 
        "value 2", 
        ...,
        "value n"
   ]
}
```
Each value is required and phoneNumbers and emails must have at least on value.
PhoneNumbers should be a list of phone numbers, it has to be only numbers between 10-15, and emails should be a list of emails, not greater than 100 of length.
FirstName and SurName should not be greater than 100 in length and has to be a string

## Read operation
This operation is a POST and works also as a searcher you can set it in the following json format:
```
{
    "firstName" : "value",
    "surName" : "value",
    "email" : "value",
    "phoneNumber" : "value"
}
```
Each value must be a string, except for phoneNumber it can be a number too, and also is optional. In other words you don't need to add all of it. It will filter the search if you set the input. 
If you don't set the input it will search all the contacts.

## ReadOne operation
This operation is a GET and as its name refers it will search only for a single record contact you just need to send a numeric value with the parameter name id

## Update operation
This operation is a PULL and GET, you need to send the id parameter in GET and for PULL setup the json format as follows:
```
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
```
FirstName and SurName are required.
For emails and phoneNumbers it has a special behaivor. 
- First of all oldPhone and oldEmail refers to the previous value it has before the update and phone and email is the value you want to replace.
- If you set oldPhone and/or oldEmail to null and phone and/or email is not empty it will create them
- If you set phone and/or email to null and oldPhone and/or oldEmail is not empty it will delete the record

## Delete operation
This is a DELETE operation and as it names refers you only need to send the id parameter of the contact you want to delete

## Additional Info
If the system catch any error it will display each of them. Most of them are related to verification of the input.
