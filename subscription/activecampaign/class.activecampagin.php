<?php

/**
 * Class to grab Active Campaign forms, parse them to see if they have a name field, email field, and any required fields
 * we can not support to build a list of usable forms to submit to. It then allows you to submit to those lists
 *
 *
 * @author Brandon Braner - Leadpages
 * @version 1.0
 */

require_once('rapidology_activecampaign_v1.php');
require_once('rapidology_activecampaign_v2.php');

class rapidology_active_campagin
{
    /**
     * @var string
     * @description url to post the form to
     */
    protected $url = '';

    /**
     * @var string
     * @description api_key from activate campaign
     */
    protected $api_key = '';

    /**
     * @var string
     * @description action for the call to make. options are form_getforms(retrieves forms) or form_html(retrieves single form html)
     */
    protected $api_action = '';

    /**
     * @var string
     * @description output format of results. defaulting to json
     */
    protected $api_output = 'json';

    /**
     * @var int
     * @description id of the form to run through form_html
     */
    protected $form_id = 0;


    /**
     * @var bool
     * @description states if the form is qualified to be displayed
     */
    protected $qualified_form = false;

    /**
     * @var array
     * @description field names that are currently supported in the api, this needs changed if more are added as if its not in here, the form will be disqualified
     */
    protected $supported_fields = array(
      'fullname',
      'email'
    );

    /**
     * @var string
     * @description url that the form should submit to
     */
    protected $form_action = '';

    /**
     * @var array
     * @description checked against to see if the field is supported. if it is, it will disqualify the form.
     */
    protected $unsupported_fields = array(
      'captcha',
      'required'
    );
    /**
     * @var array
     * @description do not believe this is actually used, but leaving here for good messure
     */
    protected $supported_types = array(
      'text',
      'email',
      'firstname',
      'lastname',
      'fullname'
    );
    /**
     * @var bool
     * @description if a fullname field is on the field this will be true.
     */
    protected $fullname = false;
    /**
     * @var bool
     * @description sets to true if an email field is found, if it is not it will disqualify the form.... this is an email capture plugin after all.
     */
    protected $email = false;


    public function __construct($url, $api_key)
    {

        $this->url     = $url;
        $this->api_key = $api_key;
    }

    public function http_request()
    {
        $params = array(
          'api_key'    => $this->api_key,
          'api_action' => $this->api_action,
          'api_output' => $this->api_output,
          'extra'      => 0,
        );
        //add on the form id if it is > 0 for the get_html action
        if ($this->form_id > 0) {
            $id     = array(
              'id' => $this->form_id
            );
            $params = array_merge($params, $id);
        }

        // This section takes the input fields and converts them to the proper format
        $query = "";
        foreach ($params as $key => $value) {
            $query .= $key . '=' . urlencode($value) . '&';
        }
        $query = rtrim($query, '& ');

        // clean up the url
        $url = rtrim($this->url, '/ ');

        //make sure curl exists
        if (!function_exists('curl_init')) {
            die('CURL not supported. (introduced in PHP 4.0.2)');
        }

        // If JSON is used, check if json_decode is present (PHP 5.2.0+)
        if ($params['api_output'] == 'json' && !function_exists('json_decode')) {
            die('JSON not supported. (introduced in PHP 5.2.0)');
        }

        $api = $url . '/admin/api.php?' . $query;

        // define a final API request - GET
        $request = curl_init($api); // initiate curl object
        curl_setopt($request, CURLOPT_HEADER,
          0); // set to 0 to eliminate header info from response
        curl_setopt($request, CURLOPT_RETURNTRANSFER,
          1); // Returns response data instead of TRUE(1)
        //curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment if you get no gateway response and are using HTTPS
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);

        $response = (string)curl_exec($request); // execute curl fetch and store results in $response

        // additional options may be required depending upon your server configuration
        // you can find documentation on curl options at http://www.php.net/curl_setopt
        curl_close($request); // close curl object

        if (!$response) {
            die('Nothing was returned. Do you have a connection to Email Marketing server?');
        }

        return $response;
    }

    public function rapidology_get_ac_forms()
    {
        $this->api_action = 'form_getforms';
        $results          = json_decode($this->http_request());
        $forms            = array();
        $i                = 0;
        if ($results->result_code) {
            //unset unneeded rows that add blank lines to $forms
            unset($results->result_code);
            unset($results->result_message);
            unset($results->result_output);
            foreach ($results as $row) {
                $forms[$i]['id']            = $row->id;
                $forms[$i]['name']          = $row->name;
                $forms[$i]['subscriptions'] = $row->subscriptions;
                $forms[$i]['lists']         = $row->lists;
                $i++;
            }
        } else {
            $error_array = array(
              'status'  => 'error',
              'message' => 'Error retrieving lists, please check your credientals and try again'
            );
            return $error_array;
        }
        return $forms;
    }

    /**
     * @param $forms
     *
     * @return mixed | multideminsional array
     * @description  this is the method you will call to parse the form ids, pull back all html, run through it via apidology_qualify_form
     * once that is doen it should return an array of form information used to store in the rapidology database with the id, name, subscription count, and all the field information needed to
     * resend the form
     */
    public function rapidology_get_ac_html($forms)
    {
        $i = 0;
        foreach ($forms as $form) {
            $this->form_id    = $form['id'];
            $this->api_action = 'form_html';
            $results          = $this->http_request();

            //run each form through qualify form to make sure rapidology can use it
            $qualified = $this->rapidology_qualify_form($results);
            if ($qualified) {
                $valid_forms[$form['id']]           = $form;
                $valid_forms[$form['id']]['fields'] = $qualified;
                $i++;
            } else {
                $invalid_forms[$form['id']]['not_qualfied'] = 'true';
            }
        }

        return $valid_forms;
    }

    public function rapidology_qualify_form($response)
    {
        $form = new DOMDocument;
        $form->loadHTML($response);

        $xpath = new DOMXPath($form);
        $divs  = $xpath->query('//div[contains(@class,"_form_element")]');
        //set version to 2 if the form is coming form the new form builder
        if ($divs->length > 0) {
            $version = 2;
            $formQualifier = new rapidology_active_campaign_v2($this->url, $this->api_key);
            return $formQualifier->v1_validate_html($xpath);
        } else {
            $version = 1;
            $formQualifier = new rapidology_active_campaign_v1($this->url, $this->api_key);
            return $formQualifier->v1_validate_html($xpath);
        }



    }

    public function rapidology_submit_ac_form(
      $form_id,
      $first_name,
      $last_name,
      $email,
      $lists_array,
      $url
    ) {

        $this->api_action          = 'contact_add';
        $params                    = array(
          'api_key'    => $this->api_key,
          'api_action' => $this->api_action,
          'api_output' => $this->api_output,
        );
        $names_array               = rapidology_name_splitter($first_name,
          $last_name);
        $first_name                = $names_array['name'];
        $last_name                 = $names_array['last_name'];
        $post_fields               = array();
        $post_fields['first_name'] = $first_name;
        $post_fields['last_name']  = $last_name;
        $post_fields['email']      = $email;
        foreach ($lists_array as $list) {
            $post_fields["p[$list]"]                 = $list;
            $post_fields['status']                   = 1;
            $post_fields["instantresponders[$list]"] = 0;
        }
        $post_fields['form'] = $form_id;

        // This section takes the input fields and converts them to the proper format
        $query = "";
        foreach ($params as $key => $value) {
            $query .= $key . '=' . urlencode($value) . '&';
        }
        $query = rtrim($query, '& ');

        // This section takes the input data and converts it to the proper format
        $data = "";
        foreach ($post_fields as $key => $value) {
            $data .= $key . '=' . urlencode($value) . '&';
        }
        $data = rtrim($data, '& ');
        $url  = rtrim($this->url, '/ ');
        if (!function_exists('curl_init')) {
            die('CURL not supported. (introduced in PHP 4.0.2)');
        }
        // If JSON is used, check if json_decode is present (PHP 5.2.0+)
        if ($params['api_output'] == 'json' && !function_exists('json_decode')) {
            die('JSON not supported. (introduced in PHP 5.2.0)');
        }

        // define a final API request - GET
        $api     = $url . '/admin/api.php?' . $query;
        $request = curl_init($api); // initiate curl object
        curl_setopt($request, CURLOPT_HEADER,
          0); // set to 0 to eliminate header info from response
        curl_setopt($request, CURLOPT_RETURNTRANSFER,
          1); // Returns response data instead of TRUE(1)
        curl_setopt($request, CURLOPT_POSTFIELDS,
          $data); // use HTTP POST to send form data
        //curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment if you get no gateway response and are using HTTPS
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
        $response = (string)curl_exec($request); // execute curl post and store results in $response
        // additional options may be required depending upon your server configuration
        // you can find documentation on curl options at http://www.php.net/curl_setopt
        curl_close($request); // close curl object

        if (!$response) {
            die('Nothing was returned. Do you have a connection to Email Marketing server?');
        }
        $results = json_decode($response);
        $success = array();
        if ($results->result_code) {
            $success['result']        = 'success';
            $success['message']       = 'success';
            $success['subscriber_id'] = $results->subscriber_id;
        } else {
            $success['result']  = 'error';
            $success['message'] = 'There seems to be an issue with your form. Please check it for invalid fields.';
        }
        return $success;
    }


    /**
     * @param $url
     * @param $api_key
     * @param $contact_id
     *
     * @description Used to remove user from active campagin. Made for integration test to remove user when test is complete
     *
     */
    function removeUser($contact_id)
    {

        // By default, this sample code is designed to get the result from your ActiveCampaign installation and print out the result
        $url = $this->url;

        $params = array(
          'api_key'    => $this->api_key,
          'api_action' => 'contact_delete',
          'api_output' => 'json',
          'id'         => $contact_id,
        );

// This section takes the input fields and converts them to the proper format
        $query = "";
        foreach ($params as $key => $value) {
            $query .= $key . '=' . urlencode($value) . '&';
        }
        $query = rtrim($query, '& ');

// clean up the url
        $url = rtrim($url, '/ ');

// This sample code uses the CURL library for php to establish a connection,
// submit your data, and show (print out) the response.
        if (!function_exists('curl_init')) {
            die('CURL not supported. (introduced in PHP 4.0.2)');
        }

// If JSON is used, check if json_decode is present (PHP 5.2.0+)
        if ($params['api_output'] == 'json' && !function_exists('json_decode')) {
            die('JSON not supported. (introduced in PHP 5.2.0)');
        }

// define a final API request - GET
        $api = $url . '/admin/api.php?' . $query;

        $request = curl_init($api); // initiate curl object
        curl_setopt($request, CURLOPT_HEADER,
          0); // set to 0 to eliminate header info from response
        curl_setopt($request, CURLOPT_RETURNTRANSFER,
          1); // Returns response data instead of TRUE(1)
//curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment if you get no gateway response and are using HTTPS
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);

        $response = (string)curl_exec($request); // execute curl fetch and store results in $response

// additional options may be required depending upon your server configuration
// you can find documentation on curl options at http://www.php.net/curl_setopt
        curl_close($request); // close curl object

        if (!$response) {
            die('Nothing was returned. Do you have a connection to Email Marketing server?');
        }

        $result = json_decode($response);
        return $result;
    }

    function contact_view_email($email)
    {
        $url = $this->url;


        $params = array(
          'api_key'    => $this->api_key,
          'api_action' => 'contact_view_email',
          'api_output' => 'json',
          'email'      => $email,
        );

        // This section takes the input fields and converts them to the proper format
        $query = "";
        foreach ($params as $key => $value) {
            $query .= $key . '=' . urlencode($value) . '&';
        }
        $query = rtrim($query, '& ');

        // clean up the url
        $url = rtrim($url, '/ ');

        // define a final API request - GET
        $api = $url . '/admin/api.php?' . $query;

        $request = curl_init($api); // initiate curl object
        curl_setopt($request, CURLOPT_HEADER,
          0); // set to 0 to eliminate header info from response
        curl_setopt($request, CURLOPT_RETURNTRANSFER,
          1); // Returns response data instead of TRUE(1)
        //curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment if you get no gateway response and are using HTTPS
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);

        $response = (string)curl_exec($request); // execute curl fetch and store results in $response

        // additional options may be required depending upon your server configuration
        // you can find documentation on curl options at http://www.php.net/curl_setopt
        curl_close($request); // close curl object


        $result = json_decode($response);
        if (isset($result->id)) {
            return $result->id;
        } else {
            return 'false';
        }
    }

    function update_contact(
      $contact_id,
      $first_name,
      $last_name,
      $email,
      $lists_array,
      $url
    ) {
        // By default, this sample code is designed to get the result from your ActiveCampaign installation and print out the result
        $url = $this->url;


        $params = array(
          'api_key'    => $this->api_key,
          'api_action' => 'contact_edit',
          'api_output' => 'json',
        );

        // here we define the data we are posting in order to perform an update
        $post_fields = array(
          'id'         => $contact_id, // example contact ID to modify
          'email'      => $email,
          'first_name' => $first_name,
          'last_name'  => $last_name,
        );
        foreach ($lists_array as $list) {
            $post_fields["p[$list]"]                 = $list;
            $post_fields['status']                   = 1;
            $post_fields["instantresponders[$list]"] = 0;
        }

        // This section takes the input fields and converts them to the proper format
        $query = "";
        foreach ($params as $key => $value) {
            $query .= $key . '=' . urlencode($value) . '&';
        }
        $query = rtrim($query, '& ');

        // This section takes the input data and converts it to the proper format
        $data = "";
        foreach ($post_fields as $key => $value) {
            $data .= $key . '=' . urlencode($value) . '&';
        }
        $data = rtrim($data, '& ');

        // clean up the url
        $url = rtrim($url, '/ ');

        // define a final API request - GET
        $api = $url . '/admin/api.php?' . $query;

        $request = curl_init($api); // initiate curl object
        curl_setopt($request, CURLOPT_HEADER,
          0); // set to 0 to eliminate header info from response
        curl_setopt($request, CURLOPT_RETURNTRANSFER,
          1); // Returns response data instead of TRUE(1)
        curl_setopt($request, CURLOPT_POSTFIELDS,
          $data); // use HTTP POST to send form data
        //curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment if you get no gateway response and are using HTTPS
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);

        $response = (string)curl_exec($request); // execute curl fetch and store results in $response

        curl_close($request); // close curl object


        $result = json_decode($response);
        return ($result->result_code);


    }
}

?>