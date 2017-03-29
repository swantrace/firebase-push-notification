<?php
function firebase_custom_push_notification_html(){

	$all_users = get_users('orderby=ID');
	$all_roles = get_editable_roles();
	$processed_all_users = array();
	foreach ($all_users as $user) {
		$current_user_roles = $user->roles;
		foreach ($all_roles as $role_key => $role_value) {
			if(in_array($role_key, $current_user_roles)){
				$processed_all_users[$role_key][] = array('id' => $user->ID, 'display_name'=>$user->data->display_name);
			}
		}
	}
?>
<form name="custom_push_notification_form" action="" id="custom_push_notification_form" method="post">

	<h2><?php _e('Send Custom Notification') ?></h2>
	<div>
		<h4><?php _e('Select Users') ?></h4>
		<p>
			<select id="selected_user" name="selected_user[]" multiple=multiple required><?php
				foreach ($processed_all_users as $role => $role_users_array) {
					echo '<optgroup label="' . __(ucfirst($role)) . '">';
					foreach($role_users_array as $user){
						
						//echo '<option value=' . $user['id'] . ' style="background-position-y: 2px;background-repeat:no-repeat;background-size:16px;background-image:url('; 
						echo '<option value=' . $user['id'] . '>';
						
						// $img_tag = get_avatar($user['id'], 16);
						// preg_match( '@src="([^"]+)"@' , $img_tag, $match );	
						// echo array_pop($match);
						// echo ')">';
						
						echo $user['display_name'];
						echo '</option>';
					}					
					echo '</optgroup>';
				} ?>
			</select>
		</p>
		<p>
			<label for="title">Please input the title of the notification</label><br>
			<input type="text" name="title" id="title" style="width:300px;" required>
		</p>
		<p>
			<label for="title">Please input the link of the notification</label><br>
			<input type="link" name="link" id="link" style="width:300px;" required>
		</p>
		<p>
			<label for="text_content">Please input the content of the notification</label>
			<br>
			<textarea name="text_content" id="text_content" rows="4" cols="50" style="max-width: 300px;" required></textarea>
		</p>
		<p>
			<input type="submit" value="<?php _e('push'); ?>" name="push_button" id="push_button" class="button button-primary">
		</p>
	</div>
	</form><?php
	
}