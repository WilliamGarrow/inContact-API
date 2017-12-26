# inContact API
This is the final PHP class and support functions for a project interfacing with the inContact API now on the production server. I also included the file used in development that connected with a MySQL database to help anyone getting started implementing this API. 

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
        $app_string = '<YOUR APP STRING>';
        $auth_key = base64_encode($app_string);
        $url = 'https://api.incontact.com/InContactAuthorizationServer/Token';
        $post_json = '{
            "grant_type" : "password",
            "username" : "<YOUR USERNAME>",
            "password" : "<YOUR PASSWORD>",
            "scope" : ""
        }';
...
```

## Usage - DB example
Same setup as above with the added component of connecting to a database. 
```php
protected function get_source($phone){
        global $userdb;

        // check phone against db data
        $sql = "SELECT id, lead_source FROM <YOUR DB TABLE> WHERE phone_clean = '$phone' LIMIT 0, 1";
        $row = $userdb->get_row($sql);
        $lead_source = $row->lead_source;

        // if blank, insert into the db for updating with a lead source later
...
```

Establish the criteria that you want to test and set a new IncontactAPI and pass the date range with startDate, endDate, and criteria as lead_data. var_dump for verification and troubleshooting. 
```php
$criteria = array(
    'fromAddr' => '<YOUR FROM PHONE NUMBER>',
    'teamId' => '<YOUR TEAM ID>'
...

$toPhone = '<YOUR TO PHONE NUMBER>';
$startDate = '<START DATE>';
$endDate = '<END DATE>';
$instance = new IncontactAPI($toPhone, $startDate, $endDate, $criteria);
$lead_data = $instance->get_call();
var_dump($lead_data);
```


### inContact Documentation

[Requesting Events](https://developer.incontact.com/Documentation/RequestingEvents)

[Reporting API Completed_Contacts](https://developer.incontact.com/API/ReportingAPI#!/Reporting/Completed_Contact_Details)
