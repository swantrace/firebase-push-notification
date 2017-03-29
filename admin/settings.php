<?php

function firebase_push_notification_settings_html(){ 
	global $wp;
	$current_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$phpself_url = $_SERVER['PHP_SELF'];
	$add_push_button_types = !empty(get_option('add_push_button_types'))?get_option('add_push_button_types'):array();
	$args = array(
   		'public'             => true,
   		'publicly_queryable' => true,
   		'exclude_from_search'=> false,
   		'show_ui'            => true,
   		'_builtin'           => false
	);

	$all_types = get_post_types($args); 
	$all_types[] = 'post'; 
	if(class_exists('bbPress')){
		$all_types[] = 'topic';
	} ?>

	<form name="setting" action="" id="setting" method="post"><?php

		wp_nonce_field('firebase_api_key', 'fpn_fcm_key');
		wp_nonce_field('add_push_button_types', 'fpn_types');

		if(isset($_POST['save_setting'])){

			if(!wp_verify_nonce($_POST['fpn_fcm_key'], 'firebase_api_key' )) { 
				
				print 'Sorry, your nonce did not verify.';
			
			} else {
				
				$firebase_api_key = $_POST['firebase_api_key'];

				update_option('firebase_api_key', $firebase_api_key);
			}

			if(!wp_verify_nonce($_POST['fpn_types'], 'add_push_button_types' )) { 
				
				print 'Sorry, your nonce did not verify.';
			
			} else {
				
				$add_push_button_types = $_POST['add_push_button_types'];

				update_option('add_push_button_types', $add_push_button_types);
			}
		}
		
		$firebase_api_key = get_option('firebase_api_key'); ?>
		
		<div>
			<div>
				<h4>
					<?php _e('Firebase API Key:'); ?> 
				</h4>
				<p>
					<input type="text" value="<?php echo $firebase_api_key; ?>" id="firebase_api_key" name="firebase_api_key" class="textfield" required>
				</p>	  
			</div>
			<div>
				<h4>
					<?php _e('Add Push Button to which Types:'); ?>
				</h4>
				<p><?php
					foreach ($all_types as $type) {?>
						<input type="checkbox" name="add_push_button_types[]" id="<?php echo $type; ?>" value="<?php echo $type; ?>" <?php echo (in_array($type, $add_push_button_types))?'checked':'';?>>
						<label for="post"><?php echo get_post_type_object($type)->labels->singular_name; ?></label>
						<br><?php 
					} ?>
				</p>

			<div>
				<p>
					<input type="submit" class="button button-primary" name="save_setting" id="save_setting" value="<?php _e('Save settings'); ?>">
				</p>
			</div>	
		</div>
	</form>
<?php
}