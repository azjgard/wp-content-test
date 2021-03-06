<?php

/*
Plugin Name : WooCommerce Attribute Filter - LCGC Vials
Description : Registers a sidebar filter that can be managed from the admin UI that can filter WooCommerce based on their attributes.
Author      : Jordin Gardner
Version     : 1.0
 */

add_action('admin_footer', function () {?>
	<script type="text/javascript" >

    jQuery(document).ready(function($) {
      var data = {
        'action' : 'update_filter'
      };

      $('#update-attribute-filter').on('click', function(e) {
        $('#response').html('');
        $.post(ajaxurl, data, function(response) {
          $('#response').html('<h2>' + response + '</h2>');
        })
      });
    });

  </script>
<?php
});

// hey

add_action('wp_ajax_update_filter', 'LCGC_update_attribute_filter');

function LCGC_update_attribute_filter()
{
    global $wpdb;

    // As we cycle through all of the products, we store every single
    // attribute in this array so that we can pull from it later on.
    $attribute_tracker = [];

    $product_categories = get_product_categories();
    $product_attribute_list = get_product_attribute_list();

    foreach ($product_attribute_list as $category => $attributes) {
        $category_id = null;
        $category_products = array();

        // This loop is literally just to grab the category ID since
        // it's not otherwise included.
        foreach ($product_categories as $product_category) {
            if ($product_category->category_nicename == $category) {
                $category_id = $product_category->cat_ID;
            }
        }

        $products = get_posts(
            array(
                'numberposts' => -1,
                'post_type' => 'product',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'terms' => $category_id,
                        'operator' => 'IN',
                    ),
                ),
            )
        );

        foreach ($products as $product) {
            $category_slug = wp_get_post_terms($product->ID, 'product_cat')[0]->slug;

            // All of the attributes that we store are nested within a
            // subarray whose key is the name of the category.
            if (!array_key_exists($category_slug, $attribute_tracker)) {
                $attribute_tracker[$category_slug] = [];
            }

            $post_id = $product->ID;

            $sql = "SELECT meta_value FROM wp_postmeta WHERE
        meta_key = '_product_attributes' AND post_id = $post_id;";

            $meta = maybe_unserialize(
                $wpdb->get_results($sql)[0]->meta_value
            );

            // We modify the WP_Post object itself here because, later on,
            // we'll be bootstrapping our own WP_Query using these same objects.
            // If that weren't the case, it would make more sense to turn
            // these into regular key => value arrays.
            $product->meta = $meta;
            $product->category = $category;
            array_push($category_products, $product);

            foreach ($meta as $data) {
                $sqlified_name = $data["name"];
                $sqlified_value = $data["value"];

                if (!array_key_exists($sqlified_name, $attribute_tracker[$category_slug])) {
                    $attribute_tracker[$category_slug][$sqlified_name] = [];
                }
                if (!array_key_exists($sqlified_value, $attribute_tracker[$category_slug][$sqlified_name]) && $sqlified_value != "") {
                    $attribute_tracker[$category_slug][$sqlified_name][normalize($sqlified_value)] = false;

                }
            }
        }

        delete_option("global-attributes-object");
        update_option("global-attributes-object", $attribute_tracker);

        delete_option($category . "-products-object");
        update_option($category . "-products-object", $category_products);
    }

    global_debug( get_option( "global-attributes-object" ), "global attributes" );

    echo "The Attribute Filter has been updated!";

    wp_die();
}

class WooCommerce_Attribute_Filter
{

    private $page_title = 'Update Filter';
    private $slug = 'wc-update-filter';
    private $main_section_name = 'main_section';

    public function __construct()
    {
        add_action('admin_menu', array($this, 'create_plugin_settings_page'));
        add_action('admin_init', array($this, 'setup_sections'));
        add_action('admin_init', array($this, 'setup_fields'));
    }

    public function create_plugin_settings_page()
    {
        // Add the menu item and page
        $menu_title = 'Update Filter';
        $capability = 'manage_options';
        $callback = array($this, 'plugin_settings_page_content');
        $icon = '';
        $position = 100;

        add_menu_page($this->page_title, $menu_title, $capability, $this->slug, $callback, $icon, $position);
    }

    public function plugin_settings_page_content()
    {?>
    <div class="wrap">
      <h1>Update Attribute Filter</h1>
      <div>
          <p>Clicking the button below should be done after products
          have been uploaded via a spreadsheet import.
          It will update the filter information to make sure it is
          fast and usable.</p>
        <button id="update-attribute-filter">Update Attribute Filter</button>
        <div id="response"></div>
      </div>
      </div> <?php
}

    public function setup_sections()
    {}
    public function setup_fields()
    {}
    public function field_callback($args)
    {}
    private function clean_string($string)
    {}
}

$wc = new WooCommerce_Attribute_Filter();
