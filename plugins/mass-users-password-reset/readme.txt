=== MASS Users Password Reset ===
Plugin Name: MASS Users Password Reset
Plugin URI: https://wordpress.org/plugins/mass-users-password-reset
Author: krishaweb
Author URI: http://krishaweb.com
Contributors: krishaweb, vijaybaria, hardik2221
Tags: bulk reset password, mass reset password, reset password for users, role, users, reset password,admin
Requires at least: 4.1
Tested up to: 4.8.1
Stable tag: trunk
Copyright: (c) 2012-2017 KrishaWeb Technologies PVT LTD (info@krishaweb.com)
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

MASS Users Password Reset lets you easily reset the password of all users.

== Description ==

MASS Users Password Reset is a WordPress Plugin that lets you resets the password of all users. It can group the users according to their role and resets password of that group. It sends notification email to users about their new randomly generated password.

Features
•   Easy installation
•   Role wise bifurcation of users
•   Sends Notifications to selected role users
•   Free support

Checkout the advanced features of Mass Users Password Reset Pro:
•   Individual user’s password reset option in users page.
•   Bulk action of Reset password for multiple selected users in users page.
•   Customized password reset mail template.
•   Apart from user role filter, you can filter users by using custom field filters of your choice.

<a href="https://codecanyon.net/item/mass-users-password-reset-pro/20809350" target="_blank">Download the Mass Users Password Reset Pro</a>

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Install the plugin via WordPress or download and upload the plugin to the /wp-content/plugins/
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. You can see the 'Mass Users Password Reset' submenu inside the 'Users' menu.

== Frequently Asked Questions ==

= What is the length of generated password? =

The length of randomly generated password is 8 characters, but by applying filter 'mupr_password_length' you can customize it. For Example: Write this code in function file
add_filter('mupr_password_length','my_theme_function');
function my_theme_function(){
	return 6;
}

= When notification mail will be send? =

When user will choose to generate new password, an email with the new random password will be sent to users.

== Screenshots ==

1. It shows the list of users and options.
2. It shows Reset password Email format