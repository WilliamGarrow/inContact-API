<?php


class IncontactAPI{

    protected $startDate;
    protected $endDate;

    protected $access_token;
    // protected $refresh_token;
    protected $resource_server_base_uri;
    // protected $refresh_token_server_uri;
    protected $token_time;
    protected $criteria;

    function __construct($startDate, $endDate, $criteria=null){
        $valid_fields = array(
            'toAddr',
            'updatedSince',
            'fields',
            'skip',
            'top',
            'orderBy',
            'skillId',
            'campaignId',
            'agentId',
            'teamId',
            'fromAddr',
            'isLogged',
            'isRefused',
            'isTakeover',
            'tags',
            'analyticsProcessed',
        );
        $this->startDate = date('Y-m-d', strtotime($startDate));
        $this->endDate = date('Y-m-d', strtotime($endDate));
        if(is_array($criteria)){
            $this->criteria = array();
            foreach($valid_fields as $valid_field){
                if(isset($criteria[$valid_field])){
                    $this->criteria[$valid_field] = $criteria[$valid_field];
                }
            }
        }

        $this->generate_ictoken();
    }

    function get_calls($retry=false) {
        $access_token = $this->access_token;
        // $refresh_token = $this->refresh_token;
        $resource_server_base_uri = $this->resource_server_base_uri;
        // $refresh_token_server_uri = $this->refresh_token_server_uri;

        // to make an API call, pass as a bearer token to a particular API
        $api_base_url = $resource_server_base_uri . 'services/v8.0/';
        $api_scope = 'contacts/completed';
        // add qs params to filter the list, set the startDate to 30 days in the past to catch calls that may have initially hit the answering service
        $qs = '?startDate=' . rawurlencode($this->startDate . 'T00:00:00-05:00') . '&endDate=' . rawurlencode($this->endDate . 'T23:59:59-05:00');
        $qs .= '&mediaTypeId=4'; // filters only phone calls

        if(is_array($this->criteria)){
            $qs .= '&' . http_build_query($this->criteria);
        }
        $url = $api_base_url . $api_scope . $qs;
//        var_dump($url);
//        exit;

        $headers = array();
        $headers[] = 'Authorization: bearer ' . $access_token;
        $headers[] = 'Content-type: application/x-www-form-urlencoded; charset=utf-8';
        $headers[] = 'Accept: application/json, text/javascript, */*; q=0.01';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
//        var_dump($result);

$call_data = array();
        if ($http_code != 200) {
            if ($http_code == 401 && !$retry) {
                $this->generate_ictoken();
                $this->get_calls(true);
            }
        } else {
            $response_array = json_decode($result, true);
//            echo 'Call data: ' . var_dump($response_array, true) . '<br>';
            if (isset($response_array['completedContacts'])) {
                $call_data = $response_array['completedContacts'];
            }
        }

        return $call_data;
    }

    protected function generate_ictoken() {
        $app_string = '<APPSTRING>';
        $auth_key = base64_encode($app_string);
        $url = 'https://api.incontact.com/InContactAuthorizationServer/Token';
        $post_json = '{
		"grant_type" : "password",
		"username" : "<USERNAME>",
		"password" : "<PASSWORD>",
		"scope" : ""
	}';

        $headers = array();
        $headers[] = 'Authorization: basic ' . $auth_key;
        $headers[] = 'Content-Type: application/json; charset=UTF-8';
        $headers[] = 'Accept: application/json, text/javascript, */*; q=0.01 ';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        $result = curl_exec($ch);
        curl_close($ch);

        // convert the JSON response
        $response_array = json_decode($result, true);

        // group the token and base URI
        $this->access_token = $response_array['access_token'];
        // $this->refresh_token = $response_array['refresh_token'];
        $this->resource_server_base_uri = $response_array['resource_server_base_uri'];
        // $this->refresh_token_server_uri = $response_array['refresh_token_server_uri'];

        // also set a timer
        $this->token_time = time();
    }
}

$criteria = array(
//    'fromAddr' => '9995551234',
    'teamId' => '<TEAMID>'
);
$toPhone = '8885551212';  // 8885551234, 8885551235, 8885551236, 8885551237, etc.
$startDate = 'November 27, 2017';
$endDate = 'November 30, 2017';
$instance = new IncontactAPI($toPhone, $startDate, $endDate, $criteria);
$lead_data = $instance->get_calls();
var_dump($lead_data);
