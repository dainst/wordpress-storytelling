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
	public $errors = array(); //collect errors for debug purpose
	
	public $id; // unique id from whatever datasource this item  is from
	public $source; // itentifier of the datasource (correspondets with class names in esa_datasource namespace)
	public $url; // URI / URL wich lead tro the orinigal dataset (diplayed in the original webspage)

	public $html; //htm representation of the object

	public function __construct($source, $id, $html = '', $url = '') {
		$this->id = $id;
		$this->source = $source;
		$this->html = $html;
		if ($url) {
			if (filter_var($url, FILTER_VALIDATE_URL)) {
				$this->url = $url;
			} else {
				$this->_error("$url considered as invalid");
			} 
		}
	}
	
	/**
	 * put out the html representation of this item
	 */
	public function html() {
		
		
		if (!$this->html) {
			$this->_generator();
		}
				
		echo "<div data-id='{$this->id}' data-source='{$this->source}' class='esa_item esa_item_{$this->source}'>";

		echo "<div class='esa_item_inner'>"; 
		echo $this->html;
		echo "</div>";
		if ($this->url) {
			echo "<div class='esa_item_tools'><a href='{$this->url}' class='esa_item_tools_originurl' target='_blank' title='view dataset in original context'>v</a></div>";
		}
		echo "</div>";
	}
	
	/**
	 * generates the html-representation of this item using the corresponding engine 
	 */
	private function _generator() {
		if (!$this->source or !$this->id) {
			return $this->_error("id ($this->id) or source  ($this->source) missing!");
		}
		
		// todo: check for cached data
		
		// get engine interface
		if (!$this->source or !file_exists(plugin_dir_path(__FILE__) . "datasources/{$this->source}.class.php")) {
			return $this->_error("Error: Search engine {$this->source} not found!");
		}
		
		require_once(plugin_dir_path(__FILE__) . "datasources/{$this->source}.class.php");
		$ed_class = "\\esa_datasource\\{$this->source}";
		$eds = new $ed_class;
		try {
			$this->html = $eds->get($this->id)->html;
		} catch (Exception $e) {
			$this->_error($e->getMessage());
		}

	}
	
	private function _error($error) {
		$this->errors[] = $error;
		echo $error;
		$this->html = "<div class='error'>Some Errors: <ul><li>" . implode('</li><li>', $this->errors) . "</li></ul></div>";
	}
	
}
?>