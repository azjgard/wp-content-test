<?php 
/**
 * Plugin Name: MASS Users Password Reset
 * Plugin URI: https://wordpress.org/plugins/mass-users-password-reset/
 * Description: Reset password of all users 
 * Version: 1.0
 * Author: KrishaWeb PVT LTD
 * Author URI: http://www.krishaweb.com 
 * License: GPL2
 */
if ( ! defined( 'ABSPATH' ) ){
	exit;
} 

define( 'MASS_USERS_PASSWORD_RESET_VERSION', '1.0' );
define( 'MASS_USERS_PASSWORD_RESET_REQUIRED_WP_VERSION', '4.3' );
define( 'MASS_USERS_PASSWORD_RESET', __FILE__ );
define( 'MASS_USERS_PASSWORD_RESET_BASENAME', plugin_basename( MASS_USERS_PASSWORD_RESET ) );
define( 'MASS_USERS_PASSWORD_RESET_PLUGIN_DIR', plugin_dir_path( MASS_USERS_PASSWORD_RESET ) );
define( 'MASS_USERS_PASSWORD_RESET_PLUGIN_URL', plugin_dir_url( MASS_USERS_PASSWORD_RESET ) );
function mass_users_password_reset_activate() {}
register_activation_hook( __FILE__, 'mass_users_password_reset_activate' );
function mass_users_password_reset_deactivate() {}
register_deactivation_hook( __FILE__, 'mass_users_password_reset_deactivate' );
require_once( MASS_USERS_PASSWORD_RESET_PLUGIN_DIR . '/main.php' );
?>