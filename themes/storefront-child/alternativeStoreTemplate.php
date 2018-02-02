<?php

/*
 * Template Name: Filter Products via Query String
 */

// This allows us to catch any notices or warnings by throwing them
// as errors. Otherwise, error handling becomes incredibly hard.
/* set_error_handler(function($errno, $errstr, $errfile, $errline) { */
/*     throw new RuntimeException($errstr . " on line " . $errline . " in file " . $errfile); */
/* }); */

get_header(); 

$attribute_data  = wp_cache_get('attribute_data', 'lcgc');
$value_blacklist = array();

?>

<div id="primary" class="content-area">
  <main id="main" class="site-main" role="main">

    <ul class="products">
      <?php

        // This will be a list of comma-separated category slugs
        // to filter by.
        $filter_category = get_query_var("category", null);

        // This will be a list of key-value attributes to filter
        // by. The format will be like this:
        // attr-name:attr-value;attr-name:attr-value
        $filter_attribute = get_query_var("attribute", null);

        $attributes = array();

        // Filtering by ATTRIBUTE
        if ($filter_attribute) {
          $key_value_pair = explode(';', $filter_attribute);
          foreach ($key_value_pair as $pair) {
            if (strlen($pair) > 0) {
              $key   = explode(':', $pair)[0];
              $value = explode(':', $pair)[1];

              $attributes[$key] = $value;
            }
          }
        }

        global_debug($filter_category, "filter_category");
        global_debug($attributes, "attributes");

        /* $data = get_filter_exclusions($filter_category, $attributes); */
        $data = get_filter_exclusions('ferrules', $attributes);

        $loop             = new WP_Query(array());
        $loop->posts      = $data['products'];
        $loop->post_count = sizeof($data['products']);

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
</div>

<?php get_footer(); ?>

<!-- Set the error handling back to normal -->
<?php restore_error_handler(); ?>
