<?php
/**
 * @package 	enhanced-storytelling
 * @subpackage	Search in Datasources | esa_item Class
 * @link 		https://github.com/dainst/wordpress-storytelling
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

    private $_rawdata = array(); // selected datafields to be kept als raw data.
	
	public function __construct($source, $id, $html = '', $url = '', $title = '', $classes = array(), $css = array(), $latitude = null, $longitude = null, $rawdata = array()) {
		$this->id = $id;
		$this->source = $source;
        $this->html = $html;
        $this->classes = $classes;
        $this->css = $css;
        $this->title = $title;

		if (is_array($rawdata)) {
		    $this->importRawData($rawdata);
        }

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


		if (!esa_get_settings("modules", "esa_item_display_settings", "dont_collapse_esa_items")) {
		    $classes .= " esa_item_collapsed";
        }
		
		echo "<div data-id='{$this->id}' data-source='{$this->source}' class='esa_item esa_item_{$this->source} $classes' $css_string>";
		
		echo "<div class='esa_item_tools'>";
		
		echo "<a title='expand' class='esa_item_tools_expand'>&nbsp;</a>";
		
		echo ($this->url) ? "<a href='{$this->url}' class='esa_item_tools_originurl' target='_blank' title='view dataset in original context'>&nbsp;</a>" : '';
		
		$url = get_bloginfo('url');
		$id = urlencode($this->id);
		$source = urlencode($this->source);
		echo "<a href='$url?s&post_type=story&esa_item_id=$id&esa_item_source=$source' class='esa_item_tools_find' title='Find Stories with this Item'>&nbsp;</a>";
		echo "<a href='$url/wp-admin/post-new.php?esa_item_id=$id&esa_item_source=$source' class='esa_item_tools_write' title='Start Story with this Item'>&nbsp;</a>";

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
            $this->error("Error: id ($this->id) or source ($this->source) missing!");
			return;
		}
		
		// check: is data already in cache?
		global $wpdb;
		$enable_cache = function_exists('esa_get_settings') ? !!esa_get_settings('modules', 'cache', 'activate') : true;
		$expiring_time = "2 week"; // TODO in settings plz
		$cached = $wpdb->get_row("select *, timestamp < date_sub(now(), interval $expiring_time) as expired from {$wpdb->prefix}esa_item_cache where id='{$this->id}' and source='{$this->source}';");
		if ($enable_cache and $cached) {
			//echo "restored from cache ({$cached->expired})";
			$this->classes[] = 'esa_item_cached';
			$this->html = $cached->content;
			$this->url = $cached->url;
            $this->title = $cached->title;
			$this->latitude = $cached->latitude;
			$this->longitude = $cached->longitude;
            $this->importRawData($this->_query_rawdata($cached));
			if (!$cached->expired) {
				return;
			}
		}
		
		// no then, generate content with corresponding interface
		try {
            $eds = esa_get_datasource($this->source);
			$generated = $eds->get($this->id);
			$this->url = $generated->url;
			$this->html = $generated->html;
			$this->title = $generated->title;
			$this->latitude = $generated->latitude;
			$this->longitude = $generated->longitude;
            $this->importRawData($generated->getRawdata());
			if ($enable_cache) {
                $this->store($cached);
            }
		} catch (Exception $e) {
			$this->error($e->getMessage());
		}

	}
	
	/**
	 * stores this object to cache datatable
	 */
	function store($cached = false) {
		global $wpdb;
		$wpdb->hide_errors();

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

		if ($proceed) {
            $proceed = $this->storeData($cached);
        }

        if(!$proceed) {
            $this->error("Insertion impossible!\n{$wpdb->last_error}\n<textarea>" . print_r($wpdb->last_query,1) . '</textarea>');
            return false;
        }

        $this->classes[] = 'esa_item_stored';
        return true;

	}
	
	public function error($error) {
		$this->errors[] = $error;
		$stack = debug_backtrace();
		$this->html = "Some Errors: <div class='error'>" . implode("</div><div class='error'>", $this->errors) . "</div>" . esa_debug($stack);
	}

	public function importRawData(array $data) {

	    $valid = true;

        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $valid = false;
                $this->error("Data: Value of '$key' must be an array but is " . gettype($value));
            } else {
                foreach ($value as $lang => $v) {
                    if (!is_array($v)) {
                        $valid = false;
                        $this->error("Data: Value of '$key'->'$lang' must be an array but is " . gettype($v));
                    }
                }
            }

            if ($valid) {
                $this->_rawdata = $data;
            }
        }
    }

    public function getRawdata() : array {
	    return $this->_rawdata;
    }

    function storeData($cached = false) {
        global $wpdb;
        $wpdb->hide_errors();

        $proceed = (false !== $wpdb->delete(
            $wpdb->prefix . 'esa_item_data_cache',
            array(
                "source" => $this->source,
                "id" => $this->id
            )
        ));

        if (!$proceed) {
            return false;
        }

        foreach ($this->_rawdata as $key => $val) {

            foreach ($val as $lang => $values) {

                foreach ($values as $value) {

                    $proceed = ($proceed and $wpdb->insert(
                        $wpdb->prefix . 'esa_item_data_cache',
                        array(
                            'language' => stripslashes($lang),
                            'key' => stripslashes($key),
                            'value' => stripslashes($value),
                            "source" => $this->source,
                            "id" => $this->id
                        )
                    ));

                }
            }
        }

        return $proceed;
    }

    private function _query_rawdata($cached) {
	    global $wpdb;
	    $cached_data = $wpdb->get_results("select * from {$wpdb->prefix}esa_item_data_cache where id='{$this->id}' and source='{$this->source}';");
        //die(esa_debug($cached_data));
        $data = new \esa_item\data();
        foreach ($cached_data as $item) {
            $data->put($item->key, $item->value, $item->language);
        }

        return $data->get();
    }
}

?>