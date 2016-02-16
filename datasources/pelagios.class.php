<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: pleiades
 * @link 		http://pleiades.stoa.org/
 * @author 		Philipp Franck
 *
 * Status: Beta 1
 *
 */


namespace esa_datasource {
	class pelagios extends abstract_datasource {

		public $title = 'Pelagios'; // Label / Title of the Datasource
		public $info = "<p>
							<a href='http://pelagios-project.blogspot.co.uk/' target='_blank'>Pelagios</a> is a
							aggregator of a vast selection of open linked data concerning the ancient world
							including a lot of gazetteers such as <a href='http://pleiades.stoa.org/' target='_blank'>Pleiades</a>.
						</p>
						<p>
							You search for a keyword or placename in Pelagios or paste an URL of a place from any 
							<a href='#' class='toggle' data-toggle='epidoclist'>of the linked ancient world gazetteers.</a>
						</p>
						<ul class='toggleable toggled' id='epidoclist'>
							<li><a href='http://pleiades.stoa.org/'  target='_blank'>Pleiades</a></li>
							<li><a href='http://atlantides.org/'  target='_blank'>Atlantides - Barrington Atlas Map Grids</a></li>
							<li><a href='http://dare.ht.lu.se/'  target='_blank'>Digital Atlas of the Roman Empire (Lund University)</a></li>
							<li><a href='http://www.trismegistos.org/'  target='_blank'>Trismegistos (Gazetteer)</a></li>
							<li><a href='http://vici.org/vici/'  target='_blank'>Vici.org - Atlas zur Archäologie des Altertums</a></li>
						</ul>
						";
		public $index = 57; // where to appear in the menu
		public $homeurl = 'http://pleiades.stoa.org/'; // link to the dataset's homepage
		public $debug = false;
		public $examplesearch = 'Insert a serach term like "gold coin", a place name or an URL like this "http://pleiades.stoa.org/places/462218"'; // placeholder for search field
		
		public $pagination = true; // are results paginated?
		private $_hits_per_page = 20;
		public $optional_classes = array(); // some classes, the user may add to the esa_item

		public $require = array();  // require additional classes -> array of fileanmes	
		
		public $url_parser = array(
			'pleiades'		=>	'#https?\:\/\/pleiades\.stoa\.org\/places\/(.*)[\/\.\?]?#',
			'atlantides'	=>	'#https?\:\/\/atlantides\.org\/capgrids\/(.*)[\/\.\?]?#',
			'dare' 			=>	'#https?\:\/\/dare\.ht\.lu\.se\/places\/(\w*)[\/\.\?]?#',
			'trismegistos'	=>	'#https?\:\/\/w?w?w?\.?trismegistos\.org\/place\/(.*)[\/\.\?]?#',
			'vici'			=>	'#https?\:\/\/vici\.org\/vici\/(.*?)[\/\.\?]#'
		); //http://pleiades.stoa.org/places/600230
		
		public $url_parser_reverse = array(
				'dare' 		=>	'http://dare.ht.lu.se/places/%%%',
		);
		
		function api_search_url($query, $params = array()) {
			$query = urlencode($query);
			return "http://pelagios.org/peripleo/search?query=$query*" . $this->_api_params_url_part($params);
		}
		
		function api_single_url($id, $params = array()) {
			// is pasted url!
			if (isset($params['pasted_url'])) {
				$url = (isset($this->url_parser_reverse[$params['regex_id']])) ?
						str_replace('%%%', $id, $this->url_parser_reverse[$params['regex_id']]) :
						$params['pasted_url'];
				$id = 'places/' . urlencode($url);
			}
			
			return "http://pelagios.org/peripleo/$id";
			//return "http://pleiades.stoa.org/places/$id/json";
		}

		function api_record_url($id, $params = array()) {
			$chunks = explode('/', $id);
			$type = array_pop($chunks);
			if ($type == 'places') {
				return "http://pleiades.stoa.org/places/$id";
			} else {
				return '';
			}
		}
			
		private function _api_params_url_part($params) {
			if(isset($params['type']) and $params['type']) {
				return "&types={$params['type']}";
			}
		}

		/*	pagination functions */
		function api_search_url_next($query, $params = array()) {
			$this->page += 1;
			$start = 1 + ($this->page - 1) * $this->_hits_per_page;
			return $this->api_search_url($query, $params) . '&offset=' . $start;
		}
			
		function api_search_url_prev($query, $params = array()) {
			$this->page -= 1;
			$start = 1 + ($this->page - 1) * $this->_hits_per_page;
			return $this->api_search_url($query, $params) . '&offset=' . $start;
		}
			
		function api_search_url_first($query, $params = array()) {
			return $this->api_search_url($query, $params) . '&offset=0';
		}
			
		function api_search_url_last($query, $params = array()) {
			$this->page = $this->pages;
			$last = 1 + ($this->pages) * $this->_hits_per_page;
			return $this->api_search_url($query, $params) . '&offset=' . $last;
		}
		
		//					http://pelagios.org/peripleo/search?query=gold&offset=20
		//no response to 	http://pelagios.org/peripleo/search?query=*&offset=60!
		function search_form_params($post) {
			$echo = "<select name='esa_ds_param_type' height='1'>";
			foreach (array('', 'place', 'item') as $type) {
				$echo .= "<option value='$type' " . (($type == $post['esa_ds_param_type']) ? 'selected ' : '') . '>' .  ucfirst(strtolower($type)) . "</option>";
			}
			$echo .= "</select>";
			return $echo;
		}
		
		
		function parse_result_set($response) {
			$response = json_decode($response);
			
			$this->pages = round($response->total / $response->limit);
			
			$this->results = array();
			foreach ($response->items as $i => $item) {
				$this->results[] = $this->_render_item($item);
			}
			return $this->results;
		}

		function parse_result($response) {
			$response = json_decode($response);
			return $this->_render_item($response);
		}
			
		private function _render_item($item) { 

			$data = new \esa_item\data();
			
			$data->title = $item->title;
			//$data->addText($key, $value);
			
			if ($item->object_type == 'Dataset') { //there are only 19
				return;
			}
			
			// basic information
			$data->addTable('type', $item->object_type);			
			$data->addTable('description', $item->description);
			
			if (isset($item->names) and is_array($item->names)) {
				$data->addTable('Alternative Names', implode(', ', $item->names));
			}
			
			// map
			$latitude = false;
			$longitude= false;
			
			// a) geometry information given, type polygon
			if (isset($item->geometry) and ($item->geometry->type == 'Polygon')) {
				
				$longitude = $item->geo_bounds->min_lon; //@todo: calculate center
				$latitude  = $item->geo_bounds->min_lat;
				
				$shape = $this->_swap_coordinates($item->geometry->coordinates);

				
			// c) bounding biox given, size zero	
			} else if (($item->geo_bounds->min_lon == $item->geo_bounds->max_lon) and
				($item->geo_bounds->min_lat == $item->geo_bounds->max_lat)) {
				
				$longitude = $item->geo_bounds->max_lon;
				$latitude  = $item->geo_bounds->max_lat;
				
				$shape = null;
				

				
			// d) bounding biox given, real size	
			} else {
				
				$longitude = $item->geo_bounds->min_lon; //@todo: calculate center
				$latitude  = $item->geo_bounds->min_lat;
	
				$shape = array(
					array($item->geo_bounds->min_lat, $item->geo_bounds->min_lon),
					array($item->geo_bounds->min_lat, $item->geo_bounds->max_lon),
					array($item->geo_bounds->max_lat, $item->geo_bounds->max_lon),
					array($item->geo_bounds->max_lat, $item->geo_bounds->min_lon),
					array($item->geo_bounds->min_lat, $item->geo_bounds->min_lon)
				);
				

			}
			
			if ($latitude and $longitude and ($item->object_type == 'Place')) {
				$data->addImages(array(
					'type' 		=> 'MAP',
					'shape'		=> $shape,
					'marker'	=> array($latitude, $longitude),
					'id'		=> $item->identifier . '-' . $source
				));
			}

			
			/*
			 list($source, $id) = $this->_identify_place_source($item->identifier);
			 $source = $source ? $source : 'pelagios';
			 $esa_item_id = $id ? $id : $item->identifier;
			 $data->addTable('sourceType', $source);
			*/
	
			if (isset($item->place_category)) {
				$data->addTable('place_category', strtolower(str_replace('_', ' ', $item->place_category)));
			}
			/* only in search
			if(isset($item->dataset_path)) {
				foreach($item->dataset_path as $ds) {
					$data->addTable('Dataset', $ds->title);
				}
			}
			*/
			if(isset($item->depictions)) {
				foreach($item->depictions as $dp) {
					$data->addImages($dp);
				}
			}
			
			
			
			$record_url = (isset($item->homepage)) ? $item->homepage : $item->identifier;
			
			$type_map = array(
				'Place' => 'places',
				'Item'	=> 'items'
			);
			
			$source_url = $type_map[$item->object_type] . '/' . urlencode($item->identifier);
	
			return new \esa_item('pelagios', $source_url, $data->render(), $record_url, array(), array(), $latitude, $longitude);
			
				
		}

		function stylesheet() {
			return array(
				'file' => 'http://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.css',
				'name' => 'leaflet'
			)
			;
		}
		
		private function _identify_place_source($string) {
			// identify source
			foreach ($this->url_parser as $type => $regex) {
				if (preg_match($regex, $string, $match)) {
					return array($type, array_pop($match));
				}
			}
			return array(false, false);
		}
		
		private function _swap_coordinates($arr) {
			foreach ($arr as $key => $val) {
				if (is_array($val))
					$arr[$key] = $this->_swap_coordinates($val);
			}
			return array_reverse($arr);
		}
		
	}
}
?>