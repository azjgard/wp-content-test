<?php

/*
 * Template Name: Filter Products via Query String
 */

// This allows us to catch any notices or warnings by throwing them
// as errors. Otherwise, error handling becomes incredibly hard.
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new RuntimeException($errstr . " on line " . $errline . " in file " . $errfile);
});

get_header(); 

$attribute_data  = wp_cache_get('attribute_data', 'lcgc');
$value_blacklist = array();

?>

<div id="primary" class="content-area">
  <main id="main" class="site-main" role="main">

    <ul class="products">
      <?php
        // This will be either 'true' or will not be set at
        // all. If true, then the query performed should be an "OR"
        // query instead of an "AND" query.
        $filter_inclusive = get_query_var("inclusive", false);

        // This will be a list of comma-separated category slugs
        // to filter by.
        $filter_category = get_query_var("category", null);

        // This will be a list of key-value attributes to filter
        // by. The format will be like this:
        // attr-name:attr-value;attr-name:attr-value
        $filter_attribute = get_query_var("attribute", null);

        $args = array(
          'post_type' => 'product',
          'posts_per_page' => 15
        );

        // Filtering by CATEGORY
        if ($filter_category) {
          $tax_query      = array();
          $category_array = explode(',', $filter_category);

          $tax_query['relation'] =
            ($filter_inclusive == "true") ? 'OR' : 'AND';

          foreach ($category_array as $category) {
            array_push($tax_query, array(
              'taxonomy' => 'product_cat',
              'field' => 'slug',
              'terms' => $category
            ));
          }

          $args['tax_query'] = $tax_query;
        }

        $start_time = null;

        // Filtering by ATTRIBUTE
        if ($filter_attribute) {
          $meta_query     = array();
          $key_value_pair = explode(';', $filter_attribute);


          // Everytime we filter by an attribute(s), we want to keep track
          // of all the other attributes that now shouldn't be selectable
          // (because the filtering is exclusive vs inclusive). Those
          // attributes will be stored in this string in the same format
          // that the attributes are passed into the query string.
          $exclusion_string = '';

          $tax_query['relation'] =
            ($filter_inclusive == "true") ? 'OR' : 'AND';

          foreach ($key_value_pair as $pair) {
            // We don't want to attempt to explode the string by a delimiter
            // if the string doesn't have any characters in it.
            if (strlen($pair) > 0) {
              try {
                $key   = explode(':', $pair)[0];
                $value = explode(':', $pair)[1];

                // We want to track all of the keys we find in a global
                // blacklist because later on, when we're querying
                // for exclusion factors, we'll check against this list
                // before running the query
                $value_blacklist[$key] = $value;

                // Unfortunately, since we're importing local attributes
                // in our spreadsheets, WooCommerce stores them in a pseudo
                // JSON string inside of the meta_key '_product_attributes'.
                // Local attributes are literally stored NOWHERE else. Luckily,
                // we're allowed to do regex in our query, which works pretty
                // well. If something breaks in the future, try adjusting the
                // {2,7} ranges; that part is probably a little hacky.
                //
                // TODO:
                // This might be able to be done as 'value' => '%". $value . "%'
                // using 'compare' => 'LIKE'
                array_push($meta_query, array(
                  'key' => '_product_attributes',
                  'value' => '\"'.$key.'\".{2,7}\"value\".{2,7}\"'.$value.'\"',
                  'compare' => 'REGEXP'
                ));
              }
              // This catch will be triggered if anything goes wrong
              // when trying to explode each $pair in the loop, i.e. if
              // there isn't a colon separating two strings.
              catch(Exception $e) {
                echo '<h1>Your query is in an incorrect format.</h1>';
                return;
              }
            }
          }

          // This creates a deep copy the of meta_query data. We don't
          // want to modify the query itself, because that should be run
          // without modification to return the actual products for the user.
          /* $exclusion_meta_query = array(); */
          /* foreach ($meta_query as $k => $v) { */
          /*   $exclusion_meta_query[$k] = $v; */
          /* } */

          /* foreach($attribute_data['Ferrules'] as $subcategory) { */


          /*   $key = $subcategory['subcategory_name']; */

          /*   // We want to make sure that the current key doesn't exist */
          /*   // as an attribute key that is actually being queried for, */
          /*   // as that's a lot of unnecessary looping, since any attribute */
          /*   // key can only work with one value. */
          /*   if (!array_key_exists($key, $value_blacklist)) { */
          /*     foreach ($subcategory['subcategory_attr'] as $value) { */

          /*       $start_time = microtime(true); */

          /*       array_push($exclusion_meta_query, array( */
          /*         'key' => '_product_attributes', */
          /*         'value' => '\"'.$key.'\".{2,7}\"value\".{2,7}\"'.$value.'\"', */
          /*         'compare' => 'REGEXP' */
          /*       )); */

          /*       $exclusion_args = array( */
          /*         'post_type' => 'product', */
          /*         'posts_per_page' => 1, */
          /*         'numberposts' => 1, */
          /*         'meta_query' => $exclusion_meta_query, */
          /*         'fields' => 'ids', */
          /*         'no_found_rows'          => true, */
          /*         'update_post_term_cache' => false, */
          /*         'update_post_meta_cache' => false, */
          /*         'cache_results'          => false */
          /*       ); */

          /*       $exclusion_loop  = new WP_Query( $exclusion_args ); */
          /*       $arg_has_results = ($exclusion_loop->post_count > 0) ? 'true;' : 'false;'; */

          /*       $exclusion_string .= */ 
          /*         $key . '--' . $value . '--' . $arg_has_results; */

          /*       array_pop($exclusion_meta_query); */
          /*     } */
          /*   } */
          /* } */

          $args['meta_query'] = $meta_query;
        }

        $loop = new WP_Query( $args );

        if ( $loop->have_posts() ) {
          while ( $loop->have_posts() ) : $loop->the_post();
          wc_get_template_part( 'content', 'product' );
endwhile;

          // The client side JavaScript will grab the value of this input
          // box and use it to gray out the options that shouldn't be selectable.

          /* echo '<input type="hidden" id="exclusion-string" value="'.$exclusion_string.'" />'; */

/* echo '<div id="exclusion-string-div" style="display: none">'; */

echo $exclusion_string;
echo '</div>';
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
