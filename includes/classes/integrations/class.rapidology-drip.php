<?php

if (!class_exists('RAD_Dashboard')) {
	require_once(RAD_RAPIDOLOGY_PLUGIN_DIR . 'rapidology.php');
}

class rapidology_drip extends RAD_Rapidology
{

	public function __contruct()
	{
		parent::__construct();
		$this->permissionsCheck();
	}

	public function draw_drip_form($form_fields, $service, $field_values)
	{
		$form_fields .= sprintf( '
					<div class="rad_dashboard_account_row">
						<label for="%1$s">%3$s</label>
						<input type="password" value="%6$s" id="%1$s">
					</div>
					<div class="rad_dashboard_account_row">
						<label for="%2$s">%4$s</label>
						<input type="text" value="%6$s" id="%2$s">
					</div>
					',

			esc_attr( 'api_key_' . $service ),#1
			esc_attr('username_'.$service),#2
			__( 'API Key', 'rapidology' ),#3
			__( 'Account number', 'rapidology' ),#4
			( '' !== $field_values && isset( $field_values['api_key_'] ) ) ? esc_html( $field_values['api_key_'] ) : '',#5
			( '' !== $field_values && isset( $field_values['username_'] ) ) ? esc_html( $field_values['username_'] ) : ''#6
		);
		return $form_fields;
	}

	/**
	 * @param $api_key
	 * @param $username this will be your account number, using username as it exists in rapidology
	 * @param $name
	 *
	 * @return \Exception|string
	 */
	function get_drip_campaigns( $api_key, $username, $name ) {
		if ( ! class_exists( 'Rapidology_Drip_Api' ) ) {
			require_once( RAD_RAPIDOLOGY_PLUGIN_DIR . 'subscription/drip/Drip_API.class.php' );
		}

		$drip = new Rapidology_Drip_Api( $api_key, false ); //true set for debug
		try {
			$error_message = 'success';
			$params		   = array();
			$params['account_id'] = $username;
			$response      = $drip->get_campaigns($params);
            if(!empty($response['code'])){
                return $response['message'];
            }
			$all_lists = array();
			foreach ($response as $obj){
				$all_lists[$obj['id']]['name'] = $obj['name'];
				$all_lists[$obj['id']]['subscribers_count'] = sanitize_text_field($obj['active_subscriber_count']);
				$all_lists[$obj['id']]['growth_week'] = sanitize_text_field( $this->calculate_growth_rate( 'drip_' . $obj['id'] ) );
			}
			$this->update_account( 'drip', sanitize_text_field( $name ), array(
				'api_key'       => sanitize_text_field( $api_key ),
				'username'      => sanitize_text_field( $username ),
				'lists'         => $all_lists,
				'is_authorized' => 'true',
			) );

			return $error_message;
		} catch ( exception $e ) {
			$error_message = $e;

			return $error_message;
		}


	}

	function drip_member_subscribe($api_key, $account_id, $email, $list_id, $name, $last_name){
        if ( ! class_exists( 'Rapidology_Drip_Api' ) ) {
            require_once( RAD_RAPIDOLOGY_PLUGIN_DIR . 'subscription/drip/Drip_API.class.php' );
        }

		$names_array = rapidology_name_splitter($name, $last_name);
		$name = $names_array['name'] .' '. $names_array['last_name'];

		$drip = new Rapidology_Drip_Api( $api_key, false ); //true set for debug
		//arguments to pass to send to emma to sign up user
		$args = array(
			'email'     => $email,
			'account_id' => $account_id,
            'campaign_id' => $list_id,
			'custom_fields' => array(
				'name' => $name
			)
		);
		try {
			$drip->subscribe_subscriber( $args );
			return $error_message = "success";
		} catch ( exception $e ) {
			$error_message = $e;

			return 'Unable to be added to list';
		}

	}
}