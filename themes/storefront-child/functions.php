<?php

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
        $filter_html .= '<div class="subcategory">';

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
  $num_of_products = 30;
  return $num_of_products;
}
