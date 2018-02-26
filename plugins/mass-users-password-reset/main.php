<?php 

class Mass_users_password_reset {

	function __construct() {
		add_action( 'admin_menu', array($this,'mass_users_password_reset_menu' )); // include in admin menu
		 if(isset($_GET['page']) && $_GET['page'] == 'mass_users_password_reset_options'){
        add_action('admin_enqueue_scripts', array($this,'include_scripts'));
      }
	}

	function mass_users_password_reset_menu() {
    add_submenu_page( 'users.php', 'Mass Users Password Reset', 'Mass Users Password Reset ',
    'activate_plugins', 'mass_users_password_reset_options',array($this,'users_list_display') );
	}

	function include_scripts(){
		wp_enqueue_style('main-css', MASS_USERS_PASSWORD_RESET_PLUGIN_URL . 'css/mupr.css');
		wp_enqueue_script('mass-users-script', MASS_USERS_PASSWORD_RESET_PLUGIN_URL . 'js/main.js', array(),false,true);
	 	wp_localize_script( 'mass-users-script', 'mupr_ajax_obj', 
       array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	function users_list_display(){
		echo '<div class="mass-reset-password-div"><h2>Mass Users Password Reset</h2><div class="filters">';
		echo self::users_filters();
		echo '</div><div class="users_list"><div class="loader"><img src="'.MASS_USERS_PASSWORD_RESET_PLUGIN_URL .'images/loading.gif" id="mupr-image-loader" style="display:none;" ></div>';
		$all_users_list = new WP_User_Query(array('role__not_in'=>'Administrator'));
		echo '<table class="wp-list-table widefat fixed striped table-wrap"><thead>
				<tr class="post_header"><th>Username</th><th>Name</th><th>Email</th></tr></thead>';
		if (!empty($all_users_list->results)){
			foreach($all_users_list->results as $users){
				echo '<tr>'; 
				echo '<td><strong><a href="'.get_edit_user_link( $users->ID ).'" target="_blank">'.$users->user_login.'</a></strong></td><td>'.get_user_meta($users->ID,'first_name',true).' '.get_user_meta($users->ID,'last_name',true).'</td><td><a href="mailto:'.$users->user_email.'">'.$users->user_email.'</a></td>';
				echo '</tr>';
			}
		}else{
			echo '<tr><td colspan="3" align="center">No users yet</td></tr>';
		}
		echo '</table>
					</div>
					<div class="tablenav bottom">
					<input type="button" value="Reset Password" name="reset" class="button" '.(empty($all_users_list->results)? "disabled":"").'>
					<img src="'.MASS_USERS_PASSWORD_RESET_PLUGIN_URL.'images/loader-icon.gif" style="display:none;" class="mupr-loader-img">
					<div class="mupr-msg"></div></div></div>';
	}

	function users_filters(){
		$filter = '<div><label for="mupr-role-filter" class="mupr-label">Select User Role</label><select name="role_filter">';
		$filter .= '<option value="">All</option>'; 
		foreach (get_editable_roles() as $role_name => $role_info){
			if ($role_name != 'administrator'){
				$filter .= '<option value="'.$role_name.'">'.$role_info['name'].'</option>';
			}
		}
		$filter .= '</select></div>';
		return $filter;
	}
} // end of class

$mass_users_password_reset_Obj = new Mass_users_password_reset();

add_action('wp_ajax_rolewise_users_display_action', 'rolewise_users_display_action');
add_action('wp_ajax_nopriv_rolewise_users_display_action', 'rolewise_users_display_action');
function rolewise_users_display_action() {
	if (isset($_POST['role_val'])){
		$role = sanitize_text_field($_POST['role_val']);
		if ($role != ""){
			$all_users_list = new WP_User_Query(array('role'=>$role));
		}else{
			$all_users_list = new WP_User_Query(array('role__not_in'=>'Administrator'));
		}
		$authors = $all_users_list->get_results();
		$content = '';
		if (!empty($authors)){
				foreach($authors as $users){
					$content .= '<tr>'; 
					$content .= '<td><strong><a href="'.get_edit_user_link( $users->ID ).'" target="_blank">'.$users->user_login.'</a></strong></td><td>'.get_user_meta($users->ID,'first_name',true).' '.get_user_meta($users->ID,'last_name',true).'</td><td><a href="mailto:'.$users->user_email.'">'.$users->user_email.'</a></td>';
					$content .= '</tr>';
				}
				$msg = array('result'=>'1','message'=>'successful','content'=>$content);
		}else{
			$msg = array('result'=>'0','message'=>'No users in this role');
		}
	}else{
		$msg = array('result'=>'0','message'=>'Unauthorized Access');
	}
	echo json_encode($msg);
	exit;
}

add_action('wp_ajax_send_reset_password_mail_action', 'send_reset_password_mail_action');
add_action('wp_ajax_nopriv_send_reset_password_mail_action', 'send_reset_password_mail_action');
function send_reset_password_mail_action() {
	if (isset($_POST['role'])){
		$role = sanitize_text_field($_POST['role']);
		if ($role != ""){
			$all_users_list = new WP_User_Query(array('role'=>$role));
		}else{
			$all_users_list = new WP_User_Query(array('role__not_in'=>'Administrator'));
		}
		$user_ids = $all_users_list->get_results();
		if (!empty($user_ids)){

			foreach($user_ids as $user_id){

				$user_info = get_userdata($user_id->ID);

			 	$new_password = wp_generate_password( apply_filters('mupr_password_length', '8'), true, true );
				wp_set_password( $new_password, $user_info->ID) ;
			 	ob_start(); ?>
			 	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml">
				<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title><?php echo get_bloginfo( 'name' ); ?></title>
				</head>
				<body topmargin="0" leftmargin="0" style="padding:0; margin:0;">
					<p>Dear <?php echo $user_info->display_name; ?>,</p>
					<p>Your Password has been changed. </p>
					<p>Your new password is : <?php echo $new_password; ?></p>
					<p>To reset your password, log in to <?php echo get_site_url().'/wp-admin/profile.php'; ?></p>				
				</body>
				</html> 
				<?php
				$to = $user_info->user_email;
				$subject = 'Reset Password';
				$body = ob_get_clean();
				// To send HTML mail, the Content-type header must be set
				// Additional headers
				$headers = array('Content-Type: text/html; charset=UTF-8','From: '.get_bloginfo( 'name' ).' <'.get_option('admin_email').'>');
				
				$result = wp_mail($to,$subject,$body,$headers);
			}
			if ($result==1){
				$msg = array('result'=>'1','message'=>'Mails sent successfully');
			}
		}else{
			$msg = array('result'=>'0','message'=>'No users in this role');
		}
	}else{
		$msg = array('result'=>'0','message'=>'Unauthorized Access');
	}
	echo json_encode($msg);
	exit;
}