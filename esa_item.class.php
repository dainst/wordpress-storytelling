<?php

/**
 * 
 * represents an item which was created by the esa-plugin wich can be represented as a shortcode or as visual object (like an image, a map and so on)
 * 
 * 
 * @author philipp Franck
 *
 */

class esa_item {
	public $id; // unique id from whatever datasource this item  is from
	public $source; // itentifier of the datasource (correspondets with class names in esa_datasource namespace)

	public $html; //htm representation of the object

	public function __construct($source, $id, $html) {
		$this->id = $id;
		$this->source = $source;
		$this->html = $html;
	}
	
	public function html() {
		echo "<div class='esa_item esa_item_{$this->source}'>{$this->html}</div>";
	}
	
}
?>