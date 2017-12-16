<?php
/**
 * woo.php
 * WooCommerce customizations.
 */

// ---------------------------------------------------------------------------------

/*
 * Show a warning message when this theme is enabled but WooCommerce is disabled.
 */

function disabledWcShowWarningMessage() {
	if(!function_exists('wc_get_text_attributes')) {
		$warning = '<b>WARNING:</b> This theme needs WooCommerce to function!';
		?>
			<div class="error">
				<p><?php echo $warning ?></p>
			</div>
        <?php
	}
	add_action('admin_notices', 'disabledWcShowWarningMessage');
}

// ---------------------------------------------------------------------------------

/**
 * Show WooCommerce products in a single column.
 */

// Change the number of columns per row in the Woocommerce
if (!function_exists('loop_columns')) {
	function loop_columns() {
	    return 1;
	}
	add_filter('loop_shop_columns', 'loop_columns', 999);
}

// ---------------------------------------------------------------------------------

/**
 * Remove the WooCommerce select box that capacitates sorting.
 */

if (!function_exists('remove_woocommerce_sorting_dropdown')) {
	function remove_woocommerce_sorting_dropdown() {
		remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
	}
	add_action('init' , 'remove_woocommerce_sorting_dropdown');
}

// ---------------------------------------------------------------------------------

/**
 * Remove WooCommerce breadcrumbs.
 */

remove_action( 'woocommerce_before_main_content','woocommerce_breadcrumb', 20, 0);

// ---------------------------------------------------------------------------------

/**
 * Add select boxes for all products that have multiple quantities associated with them.
 * Product quantity variations are represented by uploading products that have the same
 * SKU as another product, but with a - and a quantity on the end.
 *
 * Example:
 * Product 1 - SKU: 15232
 * Product 1 x 50 - SKU: 15232-50
 *
 * Each product quantity needs to have separate pricing and also needs to have images, etc.
 *
 * ******************
 * ***** README *****
 * ******************
 * The code below also relies on a JavaScript file to track when the user changes the select boxes.
 * That JavaScript can be found in '/js/initializeForms.js', and it's included in the project
 * in '/includes/enqueue.php'
 */

// Accepts a product's SKU, queries the database for products that match
// the following pattern: /{sku}-\d{1,}$/
//
// Returns the IDs of the products that were found, or false if none were found
if (!function_exists('wcgp_get_quantity_options_by_sku')) {
	function wcgp_get_quantity_options_by_sku($sku) {
		global $wpdb;

    $product_ids = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value LIKE '%s'",
        $sku.'-%'
      )
    );

		if ($product_ids) { return $product_ids; }
		else              { return false;        }
	}
}

// Accepts a product's SKU, splits the string at -, and returns the number
// that comes after it
if (!function_exists('wcgp_get_product_quantity_by_sku')) {
	function wcgp_get_product_quantity_by_sku( $sku ) {
		return explode( '-', $sku )[1];
	}
}

if (!function_exists('generate_quantity_select_box')) {
    function generate_quantity_select_box($product, $html, $return_cart_class) {
	    $shop_url = get_permalink(wc_get_page_id('shop'));

	    $product_sku = $product->get_sku();
      $product_link = $shop_url.'?add-to-cart='.$product->get_id();
      $product_formatted_price = money_format('%i', (double)$product->get_price());

	    $product_ids = wcgp_get_quantity_options_by_sku($product_sku);

	    $add_to_cart_button_class = $product_ids ? '' : 'full-width';

	    if ($product_ids) {
		    $html .= '<select class="wcgp-select">';

        // Do we need this as a default? Maybe... so it's indicative that
        // the user needs to select a pack versus just automatically adding
        // one to their cart.
		    $html .= '<option value selected>Select Qty/Pk</option>';

        $html .= '<option value="' . $product_link . '">10 - $' . $product_formatted_price . '</option>';
        
		    foreach ($product_ids as $product_object) {
			    $product_id    = $product_object->post_id;
			    $qty_product   = new WC_Product($product_id);
			    $product_qty   = wcgp_get_product_quantity_by_sku($qty_product->get_sku());
			    $product_price = $qty_product->get_price();
			    $product_variant_link = $shop_url . '?add-to-cart=' . $product_id;
			    $product_variant_text = $product_qty . ' - $' . money_format('%i', (double)$product_price);

			    $html .= '<option value="' . $product_variant_link . '">' . $product_variant_text . '</option>';
		    }
		    $html .= '</select>';
	    }

	    if ($return_cart_class) {
		    return array(
			    'html' => $html,
			    'add_to_cart_button_class' => $add_to_cart_button_class
		    );
        }
        else {
	        return $html;
        }
    }
}

function lcgc_echo_cart_form() {
    global $product;
    echo generate_quantity_select_box($product, '', false);
}

add_action('woocommerce_before_add_to_cart_quantity', 'lcgc_echo_cart_form');

// Attaches to the WooCommerce filter that generates the HTML for the "Add to Cart" form
// for each product on the archive page. This function adds a select box that allows
// the user to select the quantity of product that they desire to add to their cart.
function quantity_inputs_for_woocommerce_loop_add_to_cart_link( $html, $product ) {
	if ( $product && $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() && ! $product->is_sold_individually() ) {

	    // the default action that will occur when the form
        // is submitted
		$default_action = esc_url($product->add_to_cart_url());

		$form_quantity_info = generate_quantity_select_box($product, '', true);

		$add_to_cart_button_class = $form_quantity_info['add_to_cart_button_class'];


		$html = '<form action="' . $default_action . '" data-default="'.$default_action.'" class="cart-form" method="post" enctype="multipart/form-data">';
		$html .= $form_quantity_info['html'];
		$html .= '<button type="submit" class="add-to-cart button alt ' . $add_to_cart_button_class . '">' . esc_html( $product->add_to_cart_text() ) . '</button>';
		$html .= '</form>';
	}
	return $html;
}
add_filter('woocommerce_loop_add_to_cart_link', 'quantity_inputs_for_woocommerce_loop_add_to_cart_link', 10, 2);

// ---------------------------------------------------------------------------------

/**
 * Hide the page title on the WooCommerce shop page.
 */

if (!function_exists('show_woocommerce_page_title')) {
	function show_woocommerce_page_title() { return false; }
	add_filter('woocommerce_show_page_title', 'show_woocommerce_page_title');
}

// ---------------------------------------------------------------------------------

/**
 * Overwrite the WooCommerce core function to generate a link to the product page
 * in the product title.
 */

function woocommerce_template_loop_product_title() {
	echo '<h2 class="woocommerce-loop-product__title"><a href="'. get_the_permalink() . '">' . get_the_title() . '</a></h2>';
}

// ---------------------------------------------------------------------------------

/*
 * Change the text for the menu toggle button
 */

if (!function_exists('lcgc_change_menu_toggle_text')) {
	function lcgc_change_menu_toggle_text() {
		return 'Navigation';
	}
	add_filter('storefront_menu_toggle_text', 'lcgc_change_menu_toggle_text');
}

// ---------------------------------------------------------------------------------

/**
 * Override the toggle navigation button on mobile devices
 * so that we can get similar functionality to the product filter.
 */

if ( ! function_exists( 'storefront_primary_navigation' ) ) {
	function storefront_primary_navigation() {
		?>
        <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_html_e( 'Primary Navigation', 'storefront' ); ?>">
            <button class="menu-toggle" aria-controls="site-navigation" aria-expanded="false"><span><?php echo esc_attr( apply_filters( 'storefront_menu_toggle_text', __( 'Menu', 'storefront' ) ) ); ?></span></button>

			<?php
			wp_nav_menu(
				array(
					'theme_location'	=> 'primary',
					'container_class'	=> 'primary-navigation',
				)
			);

			wp_nav_menu(
				array(
					'theme_location'	=> 'handheld',
					'container_class'	=> 'handheld-navigation',
				)
			);
			?>
            <!-- Add this button to toggle navigation on mobile devices -->
            <button id="lcgc-toggle-mobile-nav"><i class="fa fa-navicon"></i> Navigation</button>
            <!-- Add this button to toggle navigation on mobile devices -->
        </nav>
		<?php
	}
}

