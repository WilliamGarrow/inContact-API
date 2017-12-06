<?php
// database connection already loaded in application

class IncontactAPI{

    protected $startDate;
    protected $endDate;
    protected $access_token;
    protected $resource_server_base_uri;
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

        if (is_array($criteria)){
            $this->criteria = array();

            foreach ($valid_fields as $valid_field){
                if (isset($criteria[$valid_field])){
                    $this->criteria[$valid_field] = $criteria[$valid_field];
                }
            }
        }

        $this->generate_ictoken();
    }

    function get_calls($retry=false){
        $access_token = $this->access_token;
        $resource_server_base_uri = $this->resource_server_base_uri;

        // pass as a bearer token to a particular API
        $api_base_url = $resource_server_base_uri . 'services/v8.0/';
        $api_scope = 'contacts/completed';  // TODO: find all the values

        // filter the list with qs params
        // set the startDate to 30 days in the past to catch calls initially captured by the answering service
        $qs = '?startDate=' . rawurlencode($this->startDate . 'T00:00:00-05:00') . '&endDate=' . rawurlencode($this->endDate . 'T23:59:59-05:00');
        $qs .= '&mediaTypeId=4';  // mediaTypeId 4 is phone calls only

        if (is_array($this->criteria)){
            $qs .= '&' . http_build_query($this->criteria);
        }
        $url = $api_base_url . $api_scope . $qs;
        // echo $url . '<br>';

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
        //  echo $result . '<br>';

        $call_data = array();
        if ($http_code != 200){
            if ($http_code == 401 && !$retry){
                $this->generate_ictoken();
                $this->get_calls(true);
            }
        } else {
            $response_array = json_decode($result, true);
            if (isset($response_array['completedContacts'])){
                $call_data = $response_array['completedContacts'];
            }
        }

        return $call_data;
    }

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
        $this->resource_server_base_uri = $response_array['resource_server_base_uri'];

        // also set a timer
        $this->token_time = time();
    }
}