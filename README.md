# inContact API
This is the final PHP class and support functions for a project interfacing with the inContact API now on the production. I also included the file used in development that connected with a MySQL database to help anyone getting started implementing this API. 

## Usage - class IncontactAPI 
Establish the information you want to return (agent, skill, team, campaign, disposition etc.) from the completed contacts. Define the date range with startDate and endDate or return all the data by specifying updatedSince. 
```php
function __construct($startDate, $endDate, $criteria=null){
        $valid_fields = array(
            'toAddr',
            'skillId',
            'campaignId',
            'agentId',
...
```

The inContact API assumes that the access token is stored in the session
```php
protected function generate_ictoken(){
        $app_string = '<APPSTRING>';
        $auth_key = base64_encode($app_string);
        $url = 'https://api.incontact.com/InContactAuthorizationServer/Token';
        $post_json = '{
            "grant_type" : "password",
            "username" : "<USERNAME>",
            "password" : "<PASSWORD>",
            "scope" : ""
        }';
...
```


### inContact Documentation

[Requesting Events](https://developer.incontact.com/Documentation/RequestingEvents)

[Reporting API Completed_Contacts](https://developer.incontact.com/API/ReportingAPI#!/Reporting/Completed_Contact_Details)




