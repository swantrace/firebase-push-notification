<?php
class PushNotifications{
	
	static function send_notification($tokens, $notification, $data) {
		global $wpdb;	
		ini_set("memory_limit","256M");
		set_time_limit(0);

		$firebase_api_key = get_option('firebase_api_key');

		if(empty($firebase_api_key)){
		    wp_send_json_error('no_firebase_api_key');
		}
	
		define('FIREBASE_API_KEY', $firebase_api_key);   

		$url = 'https://fcm.googleapis.com/fcm/send';

		$registration_ids = $tokens;


		$raw_fields = array(
		    "notification" => $notification,
		    "data" => $data,
		    "registration_ids" => $registration_ids
		);

		$fields = wp_json_encode($raw_fields);

		$headers = array(
		    'Authorization: key=' . FIREBASE_API_KEY,
		    'Content-Type: application/json'
		);

		// Open connection
		$ch = curl_init();

		// Set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Disabling SSL Certificate support temporarly
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);        
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

		// Execute post
		$result = curl_exec($ch);
		if ($result === FALSE) {
		    wp_send_json_error('Curl failed: ' . curl_error($ch));
		} else {
			$blogtime = current_time( 'mysql' );		
			$msg_title = $notification['title'];
			$message_text = $notification['text'];

			$firebase_push_notification_logs = $wpdb->prefix . 'firebase_push_notification_logs';
			
			foreach ( $registration_ids as $registration_id )
			{
				$wpdb->insert($firebase_push_notification_logs,array('push_title' => $msg_title,'push_message' => $message_text,'push_sent' => 1,'push_send_date' => $blogtime,'devicetoken_id' =>$registration_id),array('%s','%s','%d','%s','%s'));		
			}
							
			// Close connection
			curl_close($ch);
	        wp_send_json_success(); 
	    }        
		
	}
}
?>
