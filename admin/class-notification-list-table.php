<?php
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class Push_Notification_List_Table extends WP_List_Table {

	function __construct(){
		global $status, $page;				
		//Set parent defaults
		parent::__construct( array(
		'singular'  => 'notification',     //singular name of the listed records
		'plural'    => 'notifications',    //plural name of the listed records
		'ajax'      => false              //does this table support ajax?
		) );		
	}

	function column_default($item, $column_name){
		switch($column_name){
		case 'log_id':
		case 'push_title':
			return $item[$column_name];
		case 'push_message':
			return $item[$column_name];
		case 'push_send_date':
			return $item[$column_name];
		case 'devicetoken_id':
			return $item[$column_name];						
		default:
			return print_r($item,true); //Show the whole array for troubleshooting purposes
		}
	}

	function column_title($item){		
		//Build row actions
		$actions = array(
		'edit'      => sprintf('<a href="?page=%s&action=%s&delete_id=%s">Edit</a>',$_REQUEST['page'],'edit',$item['log_id']),
		'delete'    => sprintf('<a href="?page=%s&action=%s&delete_id=%s">Delete</a>',$_REQUEST['page'],'delete',$item['log_id']),
		);
		
		//Return the title contents
		return sprintf('%1$s %2$s',
		$item['log_id'],
		$item['push_title'],
		$item['push_message'],
		$item['push_send_date'],
		$item['devicetoken_id'],
		$this->row_actions($actions)
		);
	}

	function column_cb($item){
		return sprintf(
		'<input type="checkbox" name="%1$s[]" value="%2$s" />',
		$this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
		$item['log_id'],               //The value of the checkbox should be the record's id
		$item['push_title'],
		$item['push_message'],
		$item['push_send_date'],
		$item['devicetoken_id']
		);
	}

	function get_columns(){
		$columns = array(
		'cb'    => '<input type="checkbox" />', //Render a checkbox instead of text
		'push_title'  => 'Title',
		'push_message'  => 'Message',
		'push_send_date' => 'Date',
		'devicetoken_id' => 'Device Token'
		);
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
		'push_send_date' => array('push_send_date',false)
		);
		return $sortable_columns;
	}

	function get_bulk_actions() {		
		$actions = array(
		'delete'    => 'Delete'
		);
		return $actions;
	}

	function process_bulk_action() {
		//Detect when a bulk action is being triggered...
		if( 'delete'===$this->current_action() ) {			
			global $wpdb;								
			$array_data=$_REQUEST['delete_id'];
			$firebase_push_notification_logs = $wpdb->prefix . 'firebase_push_notification_logs';
			foreach($array_data as $key => $id_value){ 				
                $wpdb->query( "DELETE FROM $firebase_push_notification_logs WHERE log_id = ".$id_value);				
			}			
			$current_page_url=$_REQUEST['_wp_http_referer'];
            echo "<script>window.location.href='" . $current_page_url . "';</script>";
			//header('Location: '.$current_page_url);
		}		
	}

	function prepare_items() {
		global $wpdb;
		$per_page = 15;
		$firebase_push_notification_logs = $wpdb->prefix . 'firebase_push_notification_logs';
		$paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1)*$per_page : 0;					
		$where = '';					
		$orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'log_id';
		$order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';
		$data = $wpdb->get_results("SELECT * FROM $firebase_push_notification_logs $where ORDER BY $orderby $order LIMIT $per_page OFFSET $paged", ARRAY_A);		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$current_page = $this->get_pagenum();		
		$total_items = $wpdb->get_var("SELECT COUNT(log_id) FROM $firebase_push_notification_logs");		
		$this->items = $data;		
		$this->process_bulk_action();		
		$this->set_pagination_args( array(
		'total_items' => $total_items,                  //WE have to calculate the total number of items
		'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
		'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
		) );
	}
}

function all_pushnotifications_wp_html(){
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
?>