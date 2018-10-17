<?php
/**
 * @package 	wordpress-storytelling
 * @subpackage	Search in Datasources | esa_item Class
 * @link 		http://www.eagle-network.eu/stories/
 * @author 		Philipp Franck
 *
 * 
 * Represents an item which was created by the esa-plugin wich can be represented as a shortcode or as visual object (like an image, a map and so on)
 * 
 *
 */

class esa_item {
	public $errors = array(); //collect errors for debug purpose
	
	public $id; // unique id from whatever datasource this item  is from
	public $source; // identifier of the datasource (correspondents with class names in esa_datasource namespace)
	public $url; // URI / URL wich lead to the original dataset (displayed in the original webpage)

    public $title;

	public $latitude; // if the item is geographically localizable
	public $longitude;
	
	public $html; //htm representation of the object

	public $classes = array(); // additional classes of this item
	public $css = array(); // additional css of this item
	
	public function __construct($source, $id, $html = '', $url = '', $title = '', $classes = array(), $css = array(), $latitude = null, $longitude = null) {
		$this->id = $id;
		$this->source = $source;
		$this->html = $html;	
		$this->classes = $classes;
		$this->css = $css;
		$this->title = $title;

		if ($latitude and $longitude) {
			$this->latitude  = $latitude;
			$this->longitude = $longitude;
		}
		
		if ($url) {
			if (filter_var($url, FILTER_VALIDATE_URL)) {
				$this->url = $url;
			} else {
				$this->classes[] = 'esa_item_invalid_url';
			} 
		}
		
		
	}
	
	/**
	 * put out the html representation of this item
	 */
	public function html($return = false) {

		if ($return) {
			ob_start();
		}
		
		
		if (!$this->html) {
			$this->_generator();
		}
				
		$classes = implode(' ', $this->classes);
		
		$css_string = '';
		if (count($this->css)) {
			$css_string = "style='";
			foreach ($this->css as $key=>$val) {
				$css_string .= "$key: $val;";
			}
			$css_string .= "'";
		}
		
		echo "<div data-id='{$this->id}' data-source='{$this->source}' class='esa_item esa_item_collapsed esa_item_{$this->source} $classes' $css_string>";
		
		echo "<div class='esa_item_tools'>";
		
		echo "<a title='expand' class='esa_item_tools_expand'>&nbsp;</a>";
		
		echo ($this->url) ? "<a href='{$this->url}' class='esa_item_tools_originurl' target='_blank' title='view dataset in original context'>&nbsp;</a>" : '';
		
		$url = get_bloginfo('url');
		echo "<a href='$url?s&post_type=story&esa_item_id={$this->id}&esa_item_source={$this->source}' class='esa_item_tools_find' title='Find Stories with this Item'>&nbsp;</a>";
		
		echo "</div>";
		
		echo "<div class='esa_item_inner'>"; 
		echo $this->html;
		echo "</div>";

		echo "<div class='esa_item_resizebar'>";
		echo "&nbsp;";
		echo "</div>";
		
		echo "</div>";

		if ($return) {
			return ob_get_clean();
		}
		
	}
	
	/**
	 * generates the html-representation of this item using the corresponding engine 
	 */
	private function _generator() {
		
		if (!$this->source or !$this->id) {
			return $this->_error("id ($this->id) or source  ($this->source) missing!");
		}
		
		// check: is data allready in cache?
		global $wpdb;
		$expiring_time = "2 week"; // what is a reasonable expiring time?!
		$cached = $wpdb->get_row("select *, timestamp < date_sub(now(), interval $expiring_time) as expired from {$wpdb->prefix}esa_item_cache where id='{$this->id}' and source='{$this->source}';");
		if ($cached) {
			//echo "restored from cache ({$cached->expired})";
			$this->classes[] = 'esa_item_cached';
			$this->html = $cached->content;
			$this->url = $cached->url;
            $this->title = $cached->title;
			$this->latitude = $cached->latitude;
			$this->longitude = $cached->longitude;
			if (!$cached->expired) {
				return;
			}
		}
		
		// no then, generate content with corresponding interface
		if (!$this->source or !file_exists(plugin_dir_path(__FILE__) . "datasources/{$this->source}.class.php")) {
			return $this->_error("Error: Search engine {$this->source} not found!");
		}
		
		require_once(plugin_dir_path(__FILE__) . "datasources/{$this->source}.class.php");
		$ed_class = "\\esa_datasource\\{$this->source}";
		$eds = new $ed_class;
		try {
			$generated = $eds->get($this->id);
			$this->url = $generated->url;
			$this->html = $generated->html;
			$this->title = $generated->title;
			$this->latitude = $generated->latitude;
			$this->longitude = $generated->longitude;
			$this->store($cached);
		} catch (Exception $e) {
			
			$this->_error($e->getMessage() . '<br>'. $e->getTraceAsString());
		}

	}
	
	/**
	 * stores this object to cache datatable
	 */
	function store($cached = false) {
		global $wpdb;
		$wpdb->hide_errors();
		//echo "storing...";
		//die(" lat is {$cached->latitude}");
		if ($cached) {
			$proceed = $wpdb->update(
				$wpdb->prefix . 'esa_item_cache',
				array(
					'content' => stripslashes($this->html), 
					'searchindex' => strip_tags($this->html), 
					'timestamp' => current_time('mysql'),
					'url' => $this->url,
					'title' => $this->title,
					'latitude' => $this->latitude,
					'longitude' => $this->longitude
				),
				array(
					"source" => $this->source,
					"id" => $this->id
				)
			);
		} else {
			$proceed = $wpdb->insert(
				$wpdb->prefix . 'esa_item_cache',
				array(
					"source" => $this->source,
					"id" => $this->id,
					'content' => $this->html,
					'searchindex' => strip_tags($this->html),
					'timestamp' => current_time('mysql'),
					'url' => $this->url,
                    'title' => $this->title,
					'latitude' => $this->latitude,
					'longitude' => $this->longitude
				)
			);
		}
			
		
		if($proceed) {
			$this->classes[] = 'esa_item_stored';
			//echo "..successfull";
			return true;
		} else {
			$this->_error('insertion impossible!');
			$this->_error($wpdb->last_error);
			//$this->_error(strlen($this->id));
			$this->_error('<textarea>' . print_r($wpdb->last_query,1) . '</textarea>'); 
 
			return false;
		}

	}
	
	private function _error($error) {
		$this->errors[] = $error;
		$this->html = "<div class='error'>Some Errors: <ul><li>" . implode('</li><li>', $this->errors) . "</li></ul></div>";
	}
	
}

?>