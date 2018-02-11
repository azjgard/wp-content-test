<?php
function global_debug($val, $title) {
  if ( class_exists( 'PC' ) ) {
    PC::debug($val, $title);
  }
}

add_action( 'woocommerce_product_import_inserted_product_object', 'testing_product_import' );

function sqlify($str) {
  /* $str = str_replace  ("'", "", $str); */
  /* $str = preg_replace ('/[^\p{L}\p{N}]/u', '_', $str); */
  return $str;
}

function slugify($str) {
  return strtolower(str_replace(" ", "-", $str));
}

function comparing_quotes($str) {
  $str = str_replace('"', "____");
  $str = str_replace(",", "----");

  return $str;
}

function normalize($str) {
  $str = str_replace("\'", "'", $str);
  $str = str_replace('\"', '"', $str);
  $str = str_replace(' | ', ', ', $str);

  return $str;
}

// These actions allow both logged-in and non logged-in users to
// use the filter from the front-end.
add_action( 'wp_ajax_get_filter_exclusions', 'get_filter_exclusions' );
add_action( 'wp_ajax_nopriv_get_filter_exclusions', 'get_filter_exclusions' );

function get_product_cat_by_slug($slug) {
  return get_term_by('slug', $slug, 'product_cat');
}

function get_filter_exclusions($category_slug = false, $filter_args = false) {

  if (isset($_POST["category_slug"])) {
    $category_slug = $_POST["category_slug"];
  }

  if (isset($_POST["filter_args"])) {
    $filter_args = $_POST["filter_args"];
  }

  if (!$category_slug) {
    return;
  }

  $category    = get_product_cat_by_slug($category_slug);
  $category_id = $category->term_id;

  $max_product_count = 15;

  $attributes = maybe_unserialize(
    get_option( "global-attributes-object" ) 
  )[$category_slug];

  $unfiltered_products = maybe_unserialize(
    get_option( $category_slug . '-products-object' )
  );

  $filtered_products = array();

  $product_count = 0;

  if ($filter_args) {
    for ($i = 0; $i < sizeof($unfiltered_products); $i++) {
      $product            = $unfiltered_products[$i];
      $product_does_match = true;

      foreach ($filter_args as $name => $filter_meta_value) {
        $meta               = $product->meta;
        $product_meta_value = $meta[slugify($name)]['value'];

        // We need to normalize values anytime we are making a comparison.
        if (normalize($product_meta_value) != normalize($filter_meta_value)) {
          $product_does_match = false;
        }
      }

      if ($product_does_match) {
        // We are popping every single attribute value that our matching
        // products have off of the global attributes object. That global
        // object will be returned to the front-end, where it will
        // be used to exclude future filters that wouldn't otherwise
        // return any results.
        foreach($product->meta as $attribute_object) {
          unset($attributes[$attribute_object['name']][$attribute_object['value']]);
        }

        if ($product_count < $max_product_count) {
          array_push($filtered_products, $product);
          $product_count++;
        }
      }
    }
  }
  else {
    for ($x = 0; $x < $max_product_count; $x++) {
      array_push($filtered_products, $unfiltered_products[$x]);
    }
    $attributes = array();
  }

?>

<div id="primary" class="content-area">
  <main id="main" class="site-main" role="main">
    <ul class="products"> 
<?php
  $loop             = new WP_Query(array());
  $loop->posts      = $filtered_products;
  $loop->post_count = sizeof($filtered_products);

  if ( $loop->have_posts() ) {
    while ( $loop->have_posts() ) : $loop->the_post();
    wc_get_template_part( 'content', 'product' );
endwhile;
  } 
  else {
    echo __( 'No products found' );
  }

  wp_reset_postdata();
?>
    </ul>
  </main>

<?php 
  echo '<div id="exclusion-string">' . json_encode($attributes) . '</div>'; 
?>

</div>

</div>

<?php

  wp_die();
}

function get_combinations($arrays) {
  $result = array(array());

  $property_blacklist = array(
    "Qty_pk",
    "Similar_to"
  );

  foreach ($arrays as $property => $property_values) {
    $tmp = array();

    if (!in_array($property, $property_blacklist)) {

      foreach ($result as $result_item) {
        foreach ($property_values as $property_value) {
          $tmp[] = array_merge($result_item, array($property => $property_value));
        }
      }
      $result = $tmp;
    }
  }
  return $result;
}

function mb_unserialize($string) {
  $string2 = preg_replace_callback(
    '!s:(\d+):"(.*?)";!s',
    function($m){
      $len = strlen($m[2]);
      $result = "s:$len:\"{$m[2]}\";";
      return $result;

    },
    $string);
  return unserialize($string2);
}    

function get_product_categories() {
  $taxonomy     = 'product_cat';
  $orderby      = 'name';  
  $show_count   = 0;
  $pad_counts   = 0;
  $hierarchical = 1;
  $title        = '';
  $empty        = 1;

  $args = array(
    'taxonomy'     => $taxonomy,
    'orderby'      => $orderby,
    'show_count'   => $show_count,
    'pad_counts'   => $pad_counts,
    'hierarchical' => $hierarchical,
    'title_li'     => $title,
    'hide_empty'   => $empty
  );

  return get_categories( $args );
}

function get_product_attribute_list() {
  global $wpdb;

  $meta_keys = [];

  $product_categories = get_product_categories();

  $query = "SELECT meta_value FROM wp_postmeta WHERE meta_key = '_product_attributes' AND post_id = '%s' LIMIT 1;";

  foreach ($product_categories as $category) {
    $single_post = get_posts(array(
      'numberposts' => 1,
      'post_type'   => 'product',
      'tax_query'   => array(
        array(
          'taxonomy' => 'product_cat',
          'terms'    => $category->cat_ID,
          'operator' => 'IN'
        )
      )
    ))[0];

    $meta_keys[$category->category_nicename] = [];

    $meta_query        = $wpdb->prepare($query, $single_post->ID);
    $meta_serialized   = $wpdb->get_results($meta_query)[0]->meta_value;

    $meta_unserialized = maybe_unserialize($meta_serialized);

    foreach($meta_unserialized as $meta_value) {
      array_push(
        $meta_keys[$category->category_nicename],
        $meta_value["name"]
      );
    }
  }
  return $meta_keys;
}

function products_with_meta_exist($attributes) {
  global $wpdb;

  $query = "SELECT * FROM wp_postmeta WHERE meta_key = '_product_attributes'";

  foreach($attributes as $key => $value) {
    $query .= $wpdb->prepare(
      ' AND meta_value LIKE %s',
      '%' . $key . '%' . $value . '%'
    );
  }

  $query .= ' LIMIT 1;';

  $result = $wpdb->get_results($query);

  return sizeof($result) > 0;
}


function process_post() {
  $exists = products_with_meta_exist(array(
    'Material'         => '100% Graphite',
    'Specialty Styles' => 'Short'
  ));

  if ( class_exists( 'PC' ) ) {
    PC::debug($exists, "Do products exist?");
  }
}

add_filter( 'get_product_search_form' , 'woo_custom_product_searchform' );
/**
 * woo_custom_product_searchform
 *
 * @access      public
 * @since       1.0 
 * @return      void
 */
function woo_custom_product_searchform( $form ) {

  if (strpos($form, 'woocommerce-product-search-field-0') !== false) {
    return $form;
  }

  $form = '
<div class="custom-search-form">
<label class="search-label">Search:</label>
<form role="search" method="get" id="searchform" action="' . esc_url( home_url( '/'  ) ) . '">
    <div>
      <label class="screen-reader-text" for="s">' . __( 'Search for:', 'woocommerce' ) . '</label>
      <input type="text" value="' . get_search_query() . '" name="s" id="s" placeholder="' . __( 'Enter Keyword', 'woocommerce' ) . '" />
      <input type="submit" id="searchsubmit" value="'. esc_attr__( 'Search', 'woocommerce' ) .'" />
      <input type="hidden" name="post_type" value="product" />
    </div>
  </form>
<span class="small">Search by product name, part #, or other manufacturer\'s part #</span>
</div>';

  return $form;

}

function lcgc_update_product_table( $post_id ) {
  /* // only execute for products */
  /* /1* if (get_post_type( $post_id ) == 'product') { *1/ */
  /* /1*   global $wpdb; *1/ */

  /* /1*   // use dbDelta for raw sql *1/ */
  /* /1*   require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); *1/ */

  /* /1*   $table_name = $wpdb->prefix . 'productexclusiontable'; *1/ */
  /* /1*   $sql = ''; *1/ */


  /* /1*   dbDelta( $sql ); *1/ */
  /* /1* } *1/ */
  /* $response = wp_remote_post('localhost:3000', array( */
  /*   'method' => 'POST', */
  /*   'timeout' => 45, */
  /*   'httpversion' => '1.0', */
  /*   'blocking' => true, */
  /*   'body' => array('test' => 'test'), */
  /*   'cookies' => array() */
  /* )); */

  if ( class_exists( 'PC' ) ) {
    /* PC::debug('Product updated: ' . $post_id); */
    /* PC::debug(array('hello' => 'world'), 'Array test'); */
  }
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

require(get_stylesheet_directory() . '/includes/products.php');

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
            '100% Graphite', '100% Vespel®', '100% Teflon®',
            '85% Vespel®/ 15% Graphite', '60% Vespel®/ 40% Graphite'
          )
        )
      ),
      'Accessories' => array(
        array( 
          'subcategory_name' => 'Use',
          'subcategory_attr' => array(
            'GC', 'HPLC', 'GC, HPLC'
          )
        )
      ),
      'GC Inlet Liners' => array(
        array( 
          'subcategory_name' => 'OD',
          'subcategory_attr' => array(
            '4.6 mm', '5 mm', '6.2 mm', '6.3 mm', '6.5 mm'
          )
        ),
        array( 
          'subcategory_name' => 'ID',
          'subcategory_attr' => array(
            '.5 mm', '.8 mm', '.9 mm', '1 mm', '2 mm', '2.4 mm', '3.4 mm', '4 mm'
          )
        ),
        array( 
          'subcategory_name' => 'Length',
          'subcategory_attr' => array(
            '18 mm', '54 mm', '64 mm', '72 mm', '74 mm', '78.5 mm', '92 mm'
          )
        ),
        array( 
          'subcategory_name' => 'Fits Injector Port',
          'subcategory_attr' => array(
            'Varian 1060/1061', 'Varian 1075/1077', 'Varian 1078/1079',
            'Varian 1093/1094', 'Varian 1095/1096/1097/1098'
          )
        ),
        array( 
          'subcategory_name' => 'Fits GC Model',
          'subcategory_attr' => array(
            'Agilent/HP 4890', 'Agilent/HP 5880', 'Agilent/HP 5890',
            'Agilent/HP 6850', 'Agilent/HP 6890', 'Finnigan 9001',
            'Finnigan GCQ', 'Perkin Elmer', 'Varian CP-1177', 'Varian/Bruker'
          )
        ),
        array( 
          'subcategory_name' => 'Volume μL',
          'subcategory_attr' => array(
            '8', '10', '30', '35', '170', '200', '250', '350', '500', '800',
            '900', '1000', '1200'
          )
        ),
      ),
      'GC Purifiers' => array(
        array( 
          'subcategory_name' => 'Removes',
          'subcategory_attr' => array(
            'H2O',
            'Hydrocarbon',
            'Hydrocarbon, H2O',
            'Hydrocarbon, H2O, Oxygen',
            'Oxygen',
            'Oxygen, H2O',
            'Oxygen, Hydrocarbon'
          )
        ),
        array( 
          'subcategory_name' => 'Volume',
          'subcategory_attr' => array(
            '1.48cc', '1.84cc', '4.4cc', '120cc', '200cc', '825cc'
          )
        ),
        array( 
          'subcategory_name' => 'Pressure Rating',
          'subcategory_attr' => array(
            '50 PSI', '250 PSI', '100 PSI'
          )
        ),
        array( 
          'subcategory_name' => 'Tube Material',
          'subcategory_attr' => array(
            'Aluminum', 'Lexan'
          )
        ),
        array( 
          'subcategory_name' => 'Dimensions',
          'subcategory_attr' => array(
            '.4" OD x 3.43" L ', '.4" OD x 3.83" L ', '.4" OD x 6.63" L ',
            '1.3" OD x 11" L', '2" OD x 10.25" L', '3.75" OD x 7.25" L'
          )
        )
      ),
      'GC Septa' => array(
        array( 
          'subcategory_name' => 'Size',
          'subcategory_attr' => array(
            '5 mm (3/16 in.)', '9 mm (11/32 in.)', '9.5 mm (3/8 in.)',
            '10 mm', '11 mm (7/16 in.)', '11.5 mm (11/24 in.)',
            '12.5 mm (1/2 in.) ', '17 mm (21/32 in.)', 'Shimadzu Plug'
          )
        ),
        array( 
          'subcategory_name' => 'Temp. Rating',
          'subcategory_attr' => array(
            '250°C', '300°C', '325°C', '350°C', '400°C'
          )
        ),
        array( 
          'subcategory_name' => 'Guide Hole',
          'subcategory_attr' => array(
            'Yes', 'No'
          )
        ),
        array( 
          'subcategory_name' => 'Molded Septa',
          'subcategory_attr' => array(
            'Yes', 'No'
          )
        ),
        array( 
          'subcategory_name' => 'Bleed',
          'subcategory_attr' => array(
            'Low Bleed', 'Teflon Faced', 'Ultra Low Bleed'
          )
        ),
        array( 
          'subcategory_name' => 'Fits Instrument',
          'subcategory_attr' => array(
            'Agilent (HP ) On-Column Injection', 'Varian/Bruker 1177',
            'Varian/Bruker Packed Column', 'Varian/Bruker 1075',
            'Varian/Bruker 1078', 'Agilent (HP) HP5750 & Earlier',
            'Thermo Scientific TRACE GC/', 'Shimadzu All Models (14/15A/16/17A)',
            'Varian/Bruker Packed Column', 'Agilent (HP) Capillary',
            'Thermo Scientific Large Volume Splitless Injector',
            'Varian/Bruker 1040', 'Varian/Bruker 1041',
            'Varian/Bruker 1060', 'Varian/Bruker 1061', 'Agilent (HP) 5700',
            'Agilent (HP) 5880', 'Agilent (HP) 5890', 'Agilent (HP) 6890',
            'Agilent (HP) 6900', 'Tracor 550', 'Tracor 560',
            'Finnigan (TMQ) GC 9001', 'Fin', 'Varian/Bruker 1077',
            'Varian/Bruker 3700 Vista', 'Varian/Bruker Capillary Injectors',
            'Varian/Bruker Saturn GC/MS', 'Agilent (HP) 5880A',
            'Agilent (HP) 5890', 'Agilent (HP) 6850', 'Agilent (HP) 7890',
            'Agilent (HP) PTV', 'Agilent (HP)', 'Varian/Bruker 1079',
            'Varian/Bruker 1093', 'Varian/Bruker 1094 SPI', 'Varian/Bruker Saturn '
          )
        ),
      )
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
    <h2>Filter by Category:</h2>
      <select id="lcgc-attribute-filter"> 
        <option selected value="choose">Choose a category</option>
<?php
    foreach ($categories as $category) {
      echo '<option value="'.$category->slug.'">'.$category->name.'</option>';


      $filter_html .= '<div class="'.$category->slug.'-attribute-filter no-display">';

      foreach($this->attribute_data[$category->name] as $subcategory) {
        $filter_html .= '<h3 class="subcategory-title" name="'.$category->slug.'">'.$subcategory['subcategory_name'].'</h3>';
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
