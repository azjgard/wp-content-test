<?php

/*
Plugin Name : WooCommerce Attribute Filter - LCGC Vials
Description : Registers a sidebar filter that can be managed from the admin UI that can filter WooCommerce based on their attributes.
Author      : Jordin Gardner
Version     : 1.0
*/


class WooCommerce_Attribute_Filter {

  private $page_title = 'Attribute Filter';
  private $slug       = 'wc-manage-filters';
  private $main_section_name = 'main_section';

  public function __construct() {
    add_action( 'admin_menu', array($this, 'create_plugin_settings_page') );
    add_action( 'admin_init', array($this, 'setup_sections') );
    add_action( 'admin_init', array($this, 'setup_fields') );
  }

  public function create_plugin_settings_page() {
    // Add the menu item and page
    $menu_title = 'Attr. Filter';
    $capability = 'manage_options';
    $callback   = array( $this, 'plugin_settings_page_content' );
    $icon       = '';
    $position   = 100;

    add_menu_page( $this->page_title, $menu_title, $capability, $this->slug, $callback, $icon, $position );
  }

  public function plugin_settings_page_content() { ?>
    <div class="wrap">
      <h2><?php echo $this->page_title ?></h2>
        <form method="post" action="options.php">
          <?php
            settings_fields($this->slug);
            do_settings_sections($this->slug);
            submit_button();
          ?>
        </form>
    </div> <?php
  }

  public function setup_sections() {
    add_settings_section( $this->main_section_name, '', false, $this->slug);
  }

  public function setup_fields() {
    $categories = get_categories(array('taxonomy' => 'product_cat'));

    // Category-specific fields & settings
    foreach ($categories as $category) {
      $cleanName  = $this->clean_string($category->name);
      $field_name = 'wcaf-'.$cleanName.'-field';
      $label_name = $category->name;

      add_settings_field(
        $field_name              ,
        $label_name              ,
        array($this, 'field_callback'),
        $this->slug,
        $this->main_section_name,
        array(
          'category-name' => $cleanName,
          'field-name' => $field_name
        ) // args to pass to the callback
      );
      register_setting($this->slug, $field_name);
    }
  }

  public function field_callback($args) {
    $category_name = $args['category-name'];
    $field_name    = $args['field-name'];

    $category_section_id = $field_name.'-category-section';

    $current_value = get_option($field_name);


    $categoryDivSelector = '#'.$category_section_id;

    // Store the information so that it is accessible in the JavaScript
    echo 
    "<script>
      if (!this.wcafOptionContainer) { this.wcafOptionContainer = {}; } 
      this.wcafOptionContainer['".$field_name."'] = '".$current_value."';

      this.wcafInfoObject = {
        'fieldName'           : '".$field_name."',
        'categoryName'        : '".$category_name."',
        'categoryDivSelector' : '".$categoryDivSelector."'
      }

    </script>";
?>

  <button id="<?php echo $field_name ?>">Add New Subcategory</button>
  <input name="<?php echo $field_name ?>" id="<?php echo $field_name ?>" type="text" value="<?php echo $current_value; ?>" />

  <div class="category" id="<?php echo $category_section_id ?>">

  </div>

  <style>
.category {
}
.subcategory {
  margin-left: 30px;
  border: 1px solid #555;
}
</style>

<script>
(function($, info) {
  var selector            = '#' + info.fieldName;
  var categoryDivSelector = info.categoryDivSelector;

  $(selector).on('click', e => {
    e.preventDefault();
    addCategoryTemplate();
  });

  function addCategoryTemplate() {
    $(categoryDivSelector).append(`
      <div class="subcategory">
        <button id="" class="add-attribute">Add New Attribute</button>

        <input type="text" value="SubCategory Name"/>

        <div>
        </div>
      </div>
  `);
}

    })(jQuery, this.wcafInfoObject);
  </script>

<?php

  }

  private function clean_string($string) {
    $string = strtolower($string);
    $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
    $string = preg_replace("/[\s-]+/", " ", $string);
    $string = preg_replace("/[\s_]/", "-", $string);
    return $string;
  }
}

$wc = new WooCommerce_Attribute_Filter();
