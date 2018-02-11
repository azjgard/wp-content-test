<?php

/**
 * Plugin Name: Dummy Gateway for WooCommerce
 * Plugin URI:  http://www.vladopajic.com/projects/dummy-gateway-for-woocommerce/
 * Description: Dummy payment gateway plugin for WooCommerce used for testing checkout process.
 * Author: Vlado Pajić
 * Author URI: http://www.vladopajic.com/
 * Version: 1.0
 */

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_filter('plugins_loaded', function () {
        require_once(plugin_dir_path(__FILE__) . '/WCDummyGateway.class.php');
        add_filter('woocommerce_payment_gateways', function ($methods) {
            $methods[] = 'WC_Payment_Gateway_Dummy';
            return $methods;
        });
    });
}

?>