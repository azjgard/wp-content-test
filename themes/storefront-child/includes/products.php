<?php

class WC_LCGC_Product_Filter {

}

function get_woocommerce_product_list() {
  $loop = new WP_Query(
    array(
    'post_type' => 'product',
    'posts_per_page' => '1'
    )
  );

  $unique_attributes = array();

  while ( $loop->have_posts() ) : $loop->the_post();
    $product = new WC_Product( get_the_ID() );
    $product_attributes = $product->get_attributes();
    $product_category = ($product->get_category_ids());

    foreach( $product_attributes as $attribute ) {
      $attribute_name = $attribute['name'];

      if (!in_array($attribute_name, $unique_attributes)) {
        array_push($unique_attributes, $attribute_name);
      }
    }
  endwhile; wp_reset_query();
}

get_woocommerce_product_list();