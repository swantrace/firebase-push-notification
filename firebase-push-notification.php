<?php
/*
Plugin Name: Firebase Push Notification
Version: 0.0.1
Author: Fred Hong
*/

require_once plugin_dir_path(__FILE__) . 'admin/class-notification-list-table.php';
require_once plugin_dir_path(__FILE__) . 'admin/settings.php';
require_once plugin_dir_path(__FILE__) . 'admin/custom-notification.php';
require_once plugin_dir_path(__FILE__) . 'class-push-notification.php';
require_once plugin_dir_path(__FILE__) . 'rest-api.php';

register_activation_hook(__FILE__, 'firebase_push_notification_install');
register_deactivation_hook(__FILE__, 'firebase_push_notification_deactivation');
register_uninstall_hook(__FILE__, 'firebase_push_notification_uninstall');

function firebase_push_notification_install(){
	global $wpdb;
	$firebase_push_notification_logs = $wpdb->prefix . 'firebase_push_notification_logs';
	$firebase_push_notification_tokens = $wpdb->prefix . 'firebase_push_notification_tokens';

	$push_notification_log_sql = "CREATE TABLE $firebase_push_notification_logs (
		`log_id` int(11) NOT NULL AUTO_INCREMENT,
		`push_title` text NOT NULL,
		`push_message` text NOT NULL,
		`push_sent` tinyint(4) NOT NULL,
		`push_send_date` datetime NOT NULL,
		`devicetoken_id` text NOT NULL,
		PRIMARY KEY (`log_id`)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $push_notification_log_sql );	

        
	$push_notification_token_sql = "CREATE TABLE $firebase_push_notification_tokens (
		`push_token_id` int(11) NOT NULL AUTO_INCREMENT,
		`device_token` text NOT NULL,
		`user_id` int(11) NOT NULL,
		`last_updatedate` datetime NOT NULL,
		PRIMARY KEY (`push_token_id`),
		UNIQUE KEY (`device_token`)
	) $charset_collate;";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $push_notification_token_sql );
}

function firebase_push_notification_deactivation(){
	update_option('firebase_api_key', '');
}

function firebase_push_notification_uninstall(){
	global $wpdb;
	$firebase_push_notification_logs = $wpdb->prefix . 'firebase_push_notification_logs';
	$firebase_push_notification_tokens = $wpdb->prefix . 'firebase_push_notification_tokens';

	$wpdb->query("DROP TABLE IF EXISTS $firebase_push_notification_logs");
	$wpdb->query("DROP TABLE IF EXISTS $firebase_push_notification_tokens");

	delete_option('firebase_api_key');
}

add_action('admin_init', 'firebase_push_notification_scripts');
function firebase_push_notification_scripts(){
	if(current_user_can('administrator')){
		
		wp_enqueue_script('jquery');
		// wp_enqueue_script('validate', plugin_dir_url(__FILE__) . 'assets/js/validate.js', array('jquery'), '1.0.0', true);
		wp_enqueue_script('jquery-ui-selectmenu');
		wp_enqueue_script('pqselect-js',plugin_dir_url(__FILE__) .'assets/js/pqselect.js', array('jquery'), '1.0.0', true);
		wp_enqueue_script('jquery-emoji-js',plugin_dir_url(__FILE__) .'assets/js/emojionearea.js', array('jquery'), '1.0.0', true);		
		wp_enqueue_script( 'fpn-js', plugin_dir_url(__FILE__) . 'assets/js/main.js', array('jquery'), '1.0.0', true );
		
		wp_enqueue_style('jquery-ui-css', plugin_dir_url(__FILE__) . 'assets/css/jquery-ui.css');
		wp_enqueue_style('jquery-emoji-css', plugin_dir_url(__FILE__) . 'assets/css/emojionearea.css');		
		wp_enqueue_style('pqselect-css', plugin_dir_url(__FILE__) . 'assets/css/pqselect.css');		
		wp_enqueue_style('fpn-css', plugin_dir_url(__FILE__) . 'assets/css/style.css');
	}
}

add_action('admin_menu', 'firebase_push_notification_admin_menu');
function firebase_push_notification_admin_menu(){
	if ( current_user_can('administrator') ){
		add_menu_page('Firebase Push Notifications', 'Firebase Push Notifications', 'administrator','firebase-push-notifications', 'firebase_push_notification_records_html');
		add_submenu_page('firebase-push-notifications', 'Settings', 'Settings', 'administrator', 'firebase-push-notifications-settings','firebase_push_notification_settings_html');	
		add_submenu_page('firebase-push-notifications', 'Custom Notification', 'Custom Notification', 'administrator', 'firebase-custom-push-notifications','firebase_custom_push_notification_html');	
	}	
}

function get_all_tokens(){
	global $wpdb;
	$firebase_push_notification_tokens = $wpdb->prefix . 'firebase_push_notification_tokens';
	$tokens = $wpdb->get_col("SELECT device_token FROM $firebase_push_notification_tokens");
	return $tokens;
}

function firebase_push_notification_records_html(){
	//Create an instance of our package class...
	$push_notifications_Table = new Push_Notification_List_Table();
	//Fetch, prepare, sort, and filter our data...
	$push_notifications_Table->prepare_items();
	?>
	<div class="wrap">
	
	<h2><?php _e('Push Notifications Data')?></h2>
    <p><?php if(isset($_GET['notifications'])=='send') { echo _e('Notifications Sent.');}?> </p>
	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="all_pushnotification_list_table" action="" method="get">
	<!-- For plugins, we also need to ensure that the form posts back to our current page -->
	<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
	<!-- Now we can render the completed list table -->
	<?php $push_notifications_Table->display(); ?>
	</form>
	</div>
	<?php
}

// $resource_types = ['post', 'place', 'advert', 'activity'];
$resource_types = !empty(get_option('add_push_button_types'))?get_option('add_push_button_types'):array();

foreach ($resource_types as $post_type) {
	if($post_type == 'topic'){
		add_filter( 'bbp_admin_topics_column_headers', 'add_push_notification_button' );
		add_action( 'bbp_admin_topics_column_data', 'insert_button_html', 10, 2 );		
	} else {
		add_filter( 'manage_' . $post_type . '_posts_columns', 'add_push_notification_button', 999 );
		add_action( 'manage_' . $post_type . '_posts_custom_column', 'insert_button_html', 999, 2);
	}
}


// add_filter('manage_post_posts_columns', 'add_push_notification_button');
function add_push_notification_button($columns){
	$columns['push_notification'] = __('Push');
	return $columns;
}

// add_action('manage_post_posts_custom_column', 'insert_button_html', 10, 2);
function insert_button_html($column, $post_id){
	if($column == 'push_notification'){
		echo '<a class="button button-secondary fpn_button" href="#" data-post-id="'. $post_id . '" id="fpn_button_' . $post_id . '" >' . __('Push') . '</a>';
	}
}

add_action('wp_ajax_firebase_push_notification', 'send_inner_resource_as_notification');

function send_inner_resource_as_notification(){
	$post_id = $_POST['post_id'];
	$post = get_post($post_id);

	$title = "";
	$text  = "";
	$type  = "";
	$id    = "";
	$link  = "";

	$title = $post->post_title;
	if(!empty($post->post_excerpt)){
		$text = $post->post_excerpt;
	} else {
		$text = mb_substr(preg_replace('/\s+/','', strip_tags(($post->post_content))), 0, 55);
	}

	$link = get_post_permalink($post_id);

	$notification = array(
	    "title" => $title,
	    "text"  => $text
	);

	$data = array(
		"title" => $title,
		"text"  => $text,
	    "type" => $type,
	    "id"   => $post_id,
	    "link" => $link
	);

	$tokens = get_all_tokens();

	// wp_send_json_error(print_r($tokens, true));

    PushNotifications::send_notification($tokens, $notification, $data);
}

add_action('wp_ajax_firebase_push_custom_notification', 'send_custom_notification');
function send_custom_notification(){

	global $wpdb;
	
	$raw_selected_user_ids = $_POST['selected_user'];

	$int_selected_user_ids = array_map('intval', $raw_selected_user_ids);

	$processed_selected_user_ids = implode("','",$int_selected_user_ids);

	$firebase_push_notification_tokens = $wpdb->prefix . 'firebase_push_notification_tokens';
	
	$tokens = $wpdb->get_col("SELECT device_token FROM $firebase_push_notification_tokens WHERE user_id IN ('" . $processed_selected_user_ids . "')");

	$title = $_POST['title'];
	$text  = $_POST['text'];
	$link  = $_POST['link'];

	$notification = array(
		"title" => $title,
    	"text"  => $text
	);

	$data = array(
		"title" => $title,
		"text"  => $text,
		"type"  => "custom",
		"id"    => 0,
		"link"  => $link
	);
	PushNotifications::send_notification($tokens, $notification, $data);
}