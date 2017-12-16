<?php

class wcdaDisplayAttributes {
	private $name         = 'woocommerce-display-attributes';

	public function uri()  { return get_stylesheet_directory_uri() . '/' . $this->name; }
	public function path() { return get_stylesheet_directory()     . '/' . $this->name; }
	public function name() { return $this->name;                                        }

	public function include_file($path) { require($this->path() . $path); }
}

$WCDA = new wcdaDisplayAttributes();

$WCDA->include_file('/admin.php');
$WCDA->include_file('/woocommerce-display-attributes-styles.php');

