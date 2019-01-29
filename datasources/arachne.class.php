<?php
/**
 * @package 	enhanced-storytelling
 * @subpackage	Search in Datasources | Subplugin: 
 * @link 		
 * @author 		
 *
 *
 */


namespace esa_datasource {
	class arachne extends abstract_datasource {

		public $title = 'iDAI.objects | Arachne'; // Label / Title of the Datasource
		public $index = 50; // where to appear in the menu
		public $info = false; // get created automatically, or enter text
		public $homeurl = "https://arachne.dainst.org"; // link to the dataset's homepage
		public $debug = false;
		public $examplesearch = "Betender Knabe"; // placeholder for search field
		//public $searchbuttonlabel = 'Search'; // label for searchbutton

		public $pagination = true; // are results paginated?
		public $optional_classes = array(); // some classes, the user may add to the esa_item

		public $require = array();  // require additional classes -> array of filenames

		public $url_parser = '#https?\:\/\/arachne\.dainst\.org\/entity\/(\d+)#'; // // url regex (or array)

        private $_items_per_page = 12;

		/**
		 * constructor
		 * @see \esa_datasource\abstract_datasource::construct()
		 */
		function construct() {

		}

		function api_search_url($query, $params = array()) : string {
			return "https://arachne.dainst.org/data/search?q={$query}&limit={$this->_items_per_page}";
		}

		function api_single_url($id, $params = array()) : string {
			return "https://arachne.dainst.org/data/entity/{$id}";
		}

		function api_record_url($id, $params = array()) : string {
			return "https://arachne.dainst.org/entity/{$id}";
		}

		function api_search_url_next($query, $params = array()) {
			$this->page += 1;
            $offset = ($this->page - 1) * $this->_items_per_page;
			return $this->api_search_url($query) . '&offset=' . $offset;
		}

		function api_search_url_prev($query, $params = array()) {
			$this->page -= 1;
            $offset = ($this->page - 1) * $this->_items_per_page;
            return $this->api_search_url($query) . '&offset=' . $offset;
		}

		function api_search_url_first($query, $params = array()) {
			$this->page = 1;
            $offset = ($this->page - 1) * $this->_items_per_page;
            return $this->api_search_url($query) . '&offset=' . $offset;
		}

		function api_search_url_last($query, $params = array()) {
			$this->page = $this->pages;
            $offset = ($this->page - 1) * $this->_items_per_page;
            return $this->api_search_url($query) . '&offset=' . $offset;
		}

		function parse_result_set($response) {
			$response = json_decode($response);
			$this->results = array();
			$entities = isset($response->entities) ? $response->entities : array();
			foreach ($entities as $item) {
                $entity = json_decode($this->_fetch_external_data($this->api_single_url($item->entityId)));
                $this->results[] = $this->_parse_entity($entity);
			}
			
			// pagination
			$this->pages = (int) ($response->size / $this->_items_per_page) + 1;
			$this->page = isset($response->offset) ? ((int) ($response->offset / $this->_items_per_page) + 1) : 1;
			
			return $this->results;
		}
		function parse_result($response) : \esa_item {
            $response = json_decode($response);
            return $this->_parse_entity($response);
		}

		private function _parse_entity($entity) {
            $data = new \esa_item\data();
            //echo "<pre>";var_dump($entity);echo "</pre>";
            $data->title = $entity->title;
            $data->tableAsTree = true;
            if (isset($entity->sections)) {
                foreach($entity->sections as $section) {
                    $this->_parse_section($section, $data->table);
                }
            }
            if (isset($entity->thumbnailId)) {
                $data->addImages(array(
                    'url' 		=> "https://arachne.dainst.org/data/image/{$entity->thumbnailId}",
                    'type' 		=> 'BITMAP',
                ));
            }

            $item = new \esa_item(
                "arachne",
                $entity->entityId,
                $data->render() /*esa_debug($data->table)*/,
                $this->api_record_url($entity->entityId),
                $data->title
            );

            if (isset($entity->places) and count($entity->places) and isset($entity->places[0]->location)) {
                $item->longitude = $entity->places[0]->location->lon;
                $item->latitude = $entity->places[0]->location->lat;
            }

            return $item;

        }

        private function _parse_section($section, &$table, $label = "") {
            if (isset($section->content) and is_array($section->content)) {
                foreach ($section->content as $content_item) {
                    if ($section->label) {
                        $table[$section->label] = !isset($table[$section->label]) ? array() : $table[$section->label];
                        $this->_parse_section($content_item, $table[$section->label], $section->label);
                    } else {
                        $this->_parse_section($content_item, $table, $label);
                    }
                }
            } else {
                $table = array_merge($this->_split_value($section->value), $table);
            }
        }

        private function _sanitize_value($value) {
		    return strip_tags($value, "<a>");
        }

        private function _split_value($value) {
		    if (is_array($value)) {
		        $return = array();
		        foreach ($value as $subvalue) {
		            $return[] = $this->_split_value($subvalue);
                }
                return $return;
            }
		    $regex = '#<\/?[bh]r\W?\/?>#';
            return array_map(array($this, '_sanitize_value'), preg_split($regex, $value, -1,PREG_SPLIT_NO_EMPTY));
        }

		function stylesheet() {
			return array(
				'name' => get_class($this),
				'css' => ''
			);
		}

	}
}
?>