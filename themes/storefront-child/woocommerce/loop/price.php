<?php
/**
 * //////////////////////////////////////////////////////////////////////////
 *
 * OVERRIDE NOTES:
 *
 * - Show a table within each product that displays product attributes pulled from the DB
 *
 * - Show a select box for products with different quantities, denoted by products that
 *   have the same SKU as the current product, appended with a '-' and a number; e.g. 12345-50
 *
 * //////////////////////////////////////////////////////////////////////////
 *
 * Loop Price
 *
 * This template file overrides /woocommerce/loop/price.php.
 *
 * On occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}


// ---------------------------------------------------------------------------------
// ---------------------------------------------------------------------------------
// ---------------------------------------------------------------------------------

/**
 * Show a table within each product that displays product attributes pulled from the DB.
 */

if (!function_exists('wcda_clean_string')) {
  function wcda_clean_string($string) {
    $string = strtolower($string);
    $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
    $string = preg_replace("/[\s-]+/", " ", $string);
    $string = preg_replace("/[\s_]/", "-", $string);
    return $string;
  }
}

if (!function_exists('wcda_handle_option')) {
  function wcda_handle_option($option_name) {
    global $product;

    // Get the full setting name
    $clean_option_name = wcda_clean_string($option_name);
    $full_setting_name = 'wcda-'.$clean_option_name.'-attribute';

    // Grab and split the string stored in the option
    $option_string = get_option($full_setting_name);
    $option_split  = explode(',', $option_string);

    // Display the appropriate attribute (if it has a value)
    foreach ($option_split as $rawItem) {
      $cleanItem = trim(preg_replace('/\s+/', ' ', $rawItem));
      $text      = $cleanItem;
      $value     = null;

      if      ($cleanItem == 'Price') $value = '$'.$product->get_price();
      else if ($cleanItem == 'SKU'  ) $value = $product->get_sku();
      else                            $value = $product->get_attribute($rawItem);

      if ($value != "") echoProdAttr($rawItem, $value);
    }
  }
}

if (!function_exists( 'wcda_display_attributes' )) {
  function wcda_display_attributes($product) { ?>
    <div class="wcda-display-attributes">
      <table class="attr-table"> <?php
        wcda_handle_option('universal');
        foreach ($product->get_category_ids() as $id) {
          wcda_handle_option(get_the_category_by_ID($id));
        } ?>
      </table>
    </div> <?php
  }
}

if (!function_exists('echoProdAttr')) {
  function echoProdAttr($name, $value) { ?>
    <tr>
      <td>
        <span class="attribute-key">
          <b><?php echo $name; ?></b>
        </span>
      </td>
      <td>
        <span class="attribute-value">
          <?php echo barsToCommas($value); ?>
        </span>
      </td>
    </tr> <?php
  }
}

if (!function_exists('barsToCommas')) {
  function barsToCommas($str) {
    return str_replace('|', ',', $str);
  } 
}

global $product; ?>

<?php if ( $price_html = $product->get_price_html() ) : ?>
  <?php wcda_display_attributes($product); ?>
<?php endif;


