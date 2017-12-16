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

$attribute_data = wp_cache_get('attribute_data', 'lcgc');

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
          'posts_per_page' => 100
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

        // Filtering by ATTRIBUTE
        if ($filter_attribute) {
          $meta_query     = array();
          $key_value_pair = explode(';', $filter_attribute);

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
          $exclusion_meta_query = array();
          foreach ($meta_query as $k => $v) {
            $exclusion_meta_query[$k] = $v;
          }

          foreach($attribute_data['Ferrules'] as $subcategory) {
            $key = $subcategory['subcategory_name'];

            // We want to make sure that the current key doesn't exist
            // as an attribute key that is actually being queried for,
            // as that's a lot of unnecessary looping, since any attribute
            // key can only work with one value.
            if (!array_key_exists($key, $value_blacklist)) {
              foreach ($subcategory['subcategory_attr'] as $value) {
                  array_push($exclusion_meta_query, array(
                    'key' => '_product_attributes',
                    'value' => '\"'.$key.'\".{2,7}\"value\".{2,7}\"'.$value.'\"',
                    'compare' => 'REGEXP'
                  ));

          // No Hole should return true
          // (2 Hole) 0.3mm should return false

                  $exclusion_args = array(
                    'post_type' => 'product',
                    'posts_per_page' => 1,
                    'meta_query' => $exclusion_meta_query,
                    'fields' => 'ids'
                  );

                  $posts = get_posts($exclusion_args);

                  echo $key . ': ' . $value . '<br>';
                  var_dump($posts);
                  echo "<br>";
                  var_dump($exclusion_meta_query);


                  array_pop($exclusion_meta_query);

                  echo "<br>";
                  echo "<br>";
                  echo "<br>";
              }
            }
          }

          $args['meta_query'] = $meta_query;
        }



        /* $args['fields'] = 'ids'; */
        /* $before = microtime(true); */
        /* for ($i=0 ; $i<100; $i++) { */
        /*     get_posts($args); */
        /* } */
        /* $after = microtime(true); */

        /* echo ($after-$before)/$i . " sec/get posts<br/>"; */
        /* echo ($after-$before) . " to get posts ". $i ." times<br/><br/>"; */

        /* $args['fields'] = ''; */



        $loop = new WP_Query( $args );

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
