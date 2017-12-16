<?php

global $WCDA;

// enqueue regular scripts
add_action('wp_enqueue_scripts', function() use ($WCDA) {
	wp_enqueue_style( 'WCDA_styles', $WCDA->uri() . '/' . $WCDA->name() . '.css' );
});

// enqueue admin scripts
add_action('admin_enqueue_scripts', function($hook) use ($WCDA) {
	$admin_page = 'toplevel_page_attribute-manager';
	if ($hook == $admin_page) {
		wp_register_style(
			'wcda_admin_css',
			get_stylesheet_directory_uri() . '/woocommerce-display-attributes/admin.css',
			false, '1.0.0'
		);
		wp_enqueue_style('wcda_admin_css');
	}
});
