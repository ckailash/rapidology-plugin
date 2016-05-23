<?php

include(RAD_RAPIDOLOGY_PLUGIN_DIR.'/includes/classes/integrations/class.rapidology-salesforce.php');

class IntegrationsTestSalesforce extends WP_UnitTestCase {

  function setup() {
	$path = dirname(dirname(dirname(dirname(dirname(plugin_dir_path(__FILE__))))));
	include($path.'/testCreds.php');
	$this->instance 		= $testCreds->salesforce->instance;
	$this->integration 		= new $this->instance();
	$this->url				= $testCreds->salesforce->url; // url just needs to be the suddomain of the url you see when you login to salesforce. Method fills out the rest
	$this->url_invalid		= $testCreds->salesforce->url_invalid;
	$this->version			= $testCreds->salesforce->version; //needs to be a float
	$this->client_key		= $testCreds->salesforce->client_key;
	$this->client_secret	= $testCreds->salesforce->client_secret;
	$this->username_sf		= $testCreds->salesforce->username_sf;
	$this->password_sf		= $testCreds->salesforce->password_sf;
	$this->token			= $testCreds->salesforce->token;
	$this->token_invalid	= $testCreds->salesforce->token_invalid;
	$this->name 			= $testCreds->salesforce->name;
	$this->fistname			= $testCreds->salesforce->fistname;
	$this->last_name		= $testCreds->salesforce->last_name;
	$this->email			= $testCreds->salesforce->email;
	$this->list_id			= $testCreds->salesforce->list_id;
  }

  /**
   * @group success
   */
  function test_get_salesforce_campaigns_success(){
	//ensure object is created and is instance of the correct provider
	$this->assertInstanceOf($this->instance, $this->integration);
	//setup items needed to make http call Should we mock this out?
	$result = $this->integration->get_salesforce_campagins($this->url, $this->version, $this->client_key, $this->client_secret, $this->username_sf, $this->password_sf, $this->token, $this->name);
	$expectedResult = 'success';
	$this->assertEquals($expectedResult, $result, $result);//passed result in as error as that is what the plugin will return
  }

  /**
   * @group failing
   */
  function test_get_salesforce_cmpaigns_fail_invalid_token(){
	//ensure object is created and is instance of the correct provider
	$this->assertInstanceOf($this->instance, $this->integration);
	//setup items needed to make http call Should we mock this out?
	$result = $this->integration->get_salesforce_campagins($this->url, $this->version, $this->client_key, $this->client_secret, $this->username_sf, $this->password_sf, $this->token_invalid, $this->name);
	$expectedResult = 'success';
	$this->assertNotEquals($expectedResult, $result, $result);//passed result in as error as that is what the plugin will return
  }
  /**
   * @group failing
   */
  function test_get_salesforce_cmpaigns_fail_invalid_url(){
	//ensure object is created and is instance of the correct provider
	$this->assertInstanceOf($this->instance, $this->integration);
	//setup items needed to make http call Should we mock this out?
	$result = $this->integration->get_salesforce_campagins($this->url_invalid, $this->version, $this->client_key, $this->client_secret, $this->username_sf, $this->password_sf, $this->token, $this->name);
	$expectedResult = 'success';
	$this->assertNotEquals($expectedResult, $result, $result);//passed result in as error as that is what the plugin will return
  }

  /**
   * @group success
   */
  function test_subscribe_salesforce_success(){
	//ensure object is created and is instance of the correct provider
	$this->assertInstanceOf($this->instance, $this->integration);
	//setup items needed to make http call Should we mock this out?
	$result = $this->integration->subscribe_salesforce($this->url, $this->version, $this->client_key, $this->client_secret, $this->username_sf, $this->password_sf, $this->token, $this->name, $this->last_name, $this->email, $this->list_id);
	$expectedResult = 'success';
	$this->assertEquals($expectedResult, $result, $result);//passed result in as error as that is what the plugin will return
  }
}

