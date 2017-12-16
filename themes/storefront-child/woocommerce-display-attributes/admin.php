<?php



//
// Set up all form fields
// 
add_action('admin_menu' , 'wcda_plugin_setup');
add_action('admin_init' , 'wcda_setup_sections');
add_action('admin_init' , 'wcda_setup_fields');

function wcda_plugin_setup() {
  add_menu_page(
    'Attribute Manager' ,
    'Attr. Manager'     ,
    'manage_options'    ,
    'attribute-manager' ,
    'wcda_init_plugin'
  );
  register_setting('attribute-manager', 'our_first_field');
}

// Create the form 
function wcda_init_plugin() { ?>
  <div class="wrap">
    <form method="post" action="options.php"> <?php

      settings_fields('attribute-manager');
      do_settings_sections('attribute-manager');
      submit_button(); ?>

    </form>
  </div> <?php
}

// Init the sections
function wcda_setup_sections() {
  add_settings_section(
    'attribute_list_section' ,
    'Attribute Manager'      ,
    'wcda_section_callback'  ,
    'attribute-manager'
  );
}

// Informational text to display at the top of the section
function wcda_section_callback($args) { ?>
    <p>For each category below, input the attribute names that you'd like
    that category's products to display in the main product list. Separate each
    attribute name by a comma.</p>

    <p>Put attributes that should <b>always</b> be displayed (independent
       of the category) in the "All Categories" input box.</p> <?php
}

function wcda_setup_fields() {
  $categories = get_categories(array('taxonomy' => 'product_cat'));

  // Universal field & setting
  add_settings_field(
    'universal-field'        ,
    'All Categories'         ,
    'wcda_field_callback'    ,
    'attribute-manager'      ,
    'attribute_list_section' ,
    array('category-name' => 'universal')
  );
  register_setting('attribute-manager', 'wcda-universal-attribute');

  // Category-specific fields & settings
  foreach ($categories as $category) {
    $cleanName  = wcda_clean_string($category->name);
    $field_name = $cleanName.'-field';
    $label_name = $category->name;

    add_settings_field(
      $field_name              ,
      $label_name              ,
      'wcda_field_callback'    ,
      'attribute-manager'      ,
      'attribute_list_section' ,
      array('category-name' => $cleanName)
    );
    register_setting('attribute-manager', 'wcda-'.$cleanName.'-attribute');
  }
}

// For each field
function wcda_field_callback($arguments) {
  $uid      = null;
  $value    = null;
  $category = $arguments['category-name'];

  if ($category == 'universal') $uid = 'wcda-universal-attribute';
  else                          $uid = 'wcda-'.$category.'-attribute';

  $value = get_option($uid);
  printf('<textarea name="%2$s">%1$s</textarea>', $value, $uid);
}


if (!function_exists('wcda_clean_string')) {
  function wcda_clean_string($string) {
    $string = strtolower($string);
    $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
    $string = preg_replace("/[\s-]+/", " ", $string);
    $string = preg_replace("/[\s_]/", "-", $string);
    return $string;
  }
}
