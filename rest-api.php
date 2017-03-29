<?php
add_action('rest_api_init', 'fpn_register_token_endpoints' );

function fpn_register_token_endpoints() {
	register_rest_route( 'fpnwp', '/register', array(
        'methods' => 'GET',
        'callback' => 'fpn_add_user_devicetoken',
    ));
}

function fpn_add_user_devicetoken( $request_data ) {

	global $wpdb;	

	$parameters = $request_data->get_params();
	
	$device_token = $parameters['device_token'];

	if(empty($device_token)){
		return new WP_Error( 'rest_no_device_token_defined', __( 'You need to provice a device token.' ), array( 'status' => 400 ) ); 
	}

	$current_user_id = get_current_user_id();

	if(empty($current_user_id)){
		$current_user_id = 0;
	}

	$last_updatedate=current_time( 'mysql' ); 
	$firebase_push_notification_tokens = $wpdb->prefix . 'firebase_push_notification_tokens';		
	$duplicated_token_records = $wpdb->get_results("SELECT push_token_id FROM {$firebase_push_notification_tokens} WHERE device_token = '" . $device_token . "'");

	if($current_user_id == 0){
		
		if(!empty($duplicated_token_records)){
			$push_token_id = $duplicated_token_records[0]->push_token_id;
			$rows_affected = $wpdb->update($firebase_push_notification_tokens, array('last_updatedate' => $last_updatedate),array('push_token_id' => $push_token_id),array('%s'), array('%d'));
			if($rows_affected === false){
				$response = array(
					'success' => false,
					'push_token_id' => $push_token_id,
					'current_user_id' => 0,
					'info' => 'failed to update the datetime of the device token'
				);
				return json_encode($response);
			}
		} else {
			$rows_affected = $wpdb->insert($firebase_push_notification_tokens,array('push_token_id' => null,'device_token' => $device_token, 'user_id' => 0,'last_updatedate' => $last_updatedate),array('%d','%s','%d','%s')); 	
			$push_token_id = $wpdb->insert_id;
			if($rows_affected === false){
				$response = array(
					'success' => false,
					'push_token_id' => 0,
					'current_user_id' => 0,
					'info' => 'failed to insert device token'
				);
				return json_encode($response);
			}

		}
		$response = array(
			'success' => true,
			'push_token_id' => $push_token_id,
			'current_user_id' => 0,
			'info' => 'succeeded to insert or update device token of this anonymous user'
		);

		echo json_encode($response);

	} else {

		if(!empty($duplicated_token_records)){
			$push_token_id = $duplicated_token_records[0]->push_token_id;
			$rows_affected = $wpdb->update($firebase_push_notification_tokens, array('user_id' => $current_user_id, 'last_updatedate' => $last_updatedate),array('push_token_id' => $push_token_id),array('%d','%s'), array('%d'));
			if($rows_affected === false){
				$response = array(
					'success' => false,
					'push_token_id' => $push_token_id,
					'current_user_id' => $current_user_id,
					'info' => 'failed to update the user or datetime of the device token'
				);
				return json_encode($response);
			}
		} else {
			$rows_affected = $wpdb->insert($firebase_push_notification_tokens,array('push_token_id' => null,'device_token' => $device_token, 'user_id' => $current_user_id,'last_updatedate' => $last_updatedate),array('%d','%s','%d','%s')); 	
			$push_token_id = $wpdb->insert_id;
			if($rows_affected === false){
				$response = array(
					'success' => false,
					'push_token_id' => 0,
					'current_user_id' => $current_user_id,
					'info' => 'failed to insert device token of the user'
				);
				return json_encode($response);
			}

		}
		$response = array(
			'success' => true,
			'push_token_id' => $push_token_id,
			'current_user_id' => $current_user_id,
			'info' => 'succeeded to insert or update device token of this specific user'
		);

		echo json_encode($response);
	}

}