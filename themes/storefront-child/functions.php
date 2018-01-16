<?php

function lcgc_update_product_table( $post_id ) {
  // only execute for products
  /* if (get_post_type( $post_id ) == 'product') { */
  /*   global $wpdb; */

  /*   // use dbDelta for raw sql */
  /*   require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); */

  /*   $table_name = $wpdb->prefix . 'productexclusiontable'; */
  /*   $sql = ''; */


  /*   dbDelta( $sql ); */
  /* } */
  $response = wp_remote_post('localhost:3000', array(
    'method' => 'POST',
    'timeout' => 45,
    'httpversion' => '1.0',
    'blocking' => true,
    'body' => array('test' => 'test'),
    'cookies' => array()
  ));
}

add_action('save_post', 'lcgc_update_product_table');
add_action('before_delete_post', 'lcgc_update_product_table');

/**
 * WooCommerce customizations
 */

require(get_stylesheet_directory() . '/includes/woo.php');

/**
 * Enqueuing theme styles
 */

require(get_stylesheet_directory() . '/includes/enqueue.php');

/**
 * Show current template being used on all pages where it exists
 */

if (current_user_can('manage_options')) {
	function show_template() {
		global $template;
		echo '<div style="background-color: #eee; border: 1px solid #000; padding: 5px;"><strong>Current template: </strong>' . $template . '</div>';
	}
	/* add_action('wp_head', 'show_template'); */
}


/**
 * Allow WP_Query to access specific variables in the query string
 */

function add_query_vars_filter( $vars ){
  $vars[] = "category";
  $vars[] = "attribute";
  $vars[] = "inclusive";
  return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );

/**
 * This attribute data is used by the product filter and it needs
 * to be accessible throughout all of Wordpress. In additionally,
 * the access needs to be quick for snappy filtering, so we're
 * saving it in the cache.
 */

$attribute_data = array(
  'Ferrules' => array(
    array( 
      'subcategory_name' => 'Fitting Size',
      'subcategory_attr' => array(
        '1/16"', '1/8"', '3/16"', '1/4"', '3/8"', '1/2"', '5/8"',
        '3/4"', '7/8"', '1"', '1.25"', '1.5"', '6 mm', '8 mm',
        '10 mm', '12 mm')
      ),
      array( 
        'subcategory_name' => 'Ferrule ID',
        'subcategory_attr' => array(
          'No Hole', '0.3 mm', '(2 Hole) 0.3 mm', '0.4 mm',
          '(2 Hole) 0.4 mm', '0.5 mm', '(2 Hole) 0.5 mm', '0.8 mm',
          '(2 Hole) 0.8 mm', '1 mm', '1.2 mm', '2.4 mm', '4 mm',
          '5 mm', '6 mm', '8 mm', '10 mm', '12 mm', '1/16"', '1/8"',
          '3/16"', '1/4"', '3/8"', '1/2"', '5/8"', '3/4"', '7/8"',
          '1"', '1.25"', '1.5"')
        ),
        array(
          'subcategory_name' => 'Fits Column ID',
          'subcategory_attr' => array(
            '0.1 mm', '0.2-0.25 mm', '0.32 mm', '0.45-0.53 mm',
            '0.65 mm', '0.75 mm', '2.4 mm OD Tube', '4 mm OD Tube',
            '5 mm OD Tube', '6 mm OD Tube', '8 mm OD Tube',
            '10 mm OD Tube', '12 mm OD Tube', '1/16" OD Tube',
            '1/8" OD Tube', '3/16" OD Tube', '1/4" OD Tube',
            '3/8" OD Tube', '1/2" OD Tube', '5/8" OD Tube',
            '3/4" OD Tube', '7/8" OD Tube', '1" OD Tube',
            '1.25" OD Tube', '1.5" OD Tube', 'M5 NUT', 'M8 NUT'
          )
        ),
        array(
          'subcategory_name' => 'Specialty Styles',
          'subcategory_attr' => array(
            'Short', 'Short HP Style', 'Jacketed Styles',
            'Preconditioned', 'Long', 'Extra Long',
            'Long Taper', '2 Hole', 'Double Taper', 'Carlo Erba',
            'Mini Union MSUV', 'Shimadzu',
            'Accessories, Capillary Nuts', 'Accessories, O-Rings',
            'Accessories, GC Filters', 'Accessories, GC Septa',
            'Accessories, Scoring Wafer'
          )
        ),
        array(
          'subcategory_name' => 'Material',
          'subcategory_attr' => array(
            '100% Graphite',
            '100% Vespel®',
            '100% Teflon®',
            '85% Vespel®/ 15% Graphite',
            '60% Vespel®/ 40% Graphite'
          )
        )
      ),
      'these' => array(),
      'world' => array()
    );
wp_cache_add('attribute_data', $attribute_data, 'lcgc');

/**
 * Sidebar filter widget
 */

class LCGC_Sidebar_Filter_Widget extends WP_Widget {
  private $widget_id          = "lcgc-sidebar-filter";
  private $widget_name        = "Product Attribute Filter";
  private $widget_description = 'Filter store products by their categories and attributes.';
  private $widget_classname   = 'example_class';
  private $attribute_data      = '';

  public function __construct() {
    $this->attribute_data = wp_cache_get('attribute_data', 'lcgc');
    $widget_options = array( 
      'classname'   => $this->widget_classname,
      'description' => $this->widget_description
    );
    parent::__construct( $this->widget_id, $this->widget_name, $widget_options );
  } 

  public function widget( $args, $instance ) {
    $blog_title  = get_bloginfo( 'name' );
    $tagline     = get_bloginfo( 'description' );
    $categories  = get_terms('product_cat', array ('hide_empty' => true));
    $filter_html = '';
?>
    <div class="category-filter">
    <h2>Filter products by:</h2>
      <h3>Category:</h3>
      <select id="lcgc-attribute-filter"> 
        <option selected value="choose">Choose a category</option>
<?php
    foreach ($categories as $category) {
      echo '<option value="'.$category->slug.'">'.$category->name.'</option>';


      $filter_html .= '<div class="'.$category->slug.'-attribute-filter no-display">';

      foreach($this->attribute_data[$category->name] as $subcategory) {
        $filter_html .= '<h4 class="subcategory-title" name="'.$category->slug.'">'.$subcategory['subcategory_name'].'</h4>';
        $filter_html .= '<div class="subcategory" name="'.$subcategory['subcategory_name'].'">';

        foreach ($subcategory['subcategory_attr'] as $attr) {
          $filter_html .= '<div><input type="checkbox" name="'.$subcategory['subcategory_name'].'" /><label>'.$attr.'</label></div>';
        }
        $filter_html .= '</div>';
      }
      $filter_html .= '</div>';
    }
?>
      </select>
      <div class="attributes"><?php echo $filter_html ?></div>
    </div>
<?php

  }
}
add_action( 'widgets_init', function() {
  register_widget( 'LCGC_Sidebar_Filter_Widget' );
});

add_filter( 'loop_shop_per_page', 'new_loop_shop_per_page', 20 );

function new_loop_shop_per_page( $num_of_products ) {
  $num_of_products = 20;
  return $num_of_products;
}
