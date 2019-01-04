<?php
/**
 * @package 	enhanced-storytelling
 * @subpackage	Search in Datasources | Subplugin: Europeana
 * @link 		http://www.europeana.eu/
 * @author 		Philipp Franck
 * 
 * Status: Beta
 * 
 */
namespace esa_datasource {
	class europeana extends abstract_datasource {
		public $debug = false;

		public $title = "Europeana";
		public $index = 20; // where to appear in the menu
		public $homeurl = "http://www.europeana.eu/portal/";

		private $_hits_per_page = 24;
		
		public $url_parser = '#https?\:\/\/(www\.)?europeana\.eu\/portal\/record(.*)\.html.*#';
		
		function api_search_url($query, $params = array()) {
			$query = urlencode($query);
			return "http://www.europeana.eu/api/v2/search.json?wskey=ydRg6Ujho&query=$query&start=1&rows={$this->_hits_per_page}&profile=standard" . $this->_api_params_url_part($params);
		}
		
		function api_single_url($id, $params = array()) {
			return "http://www.europeana.eu/api/v2/record{$id}.json?wskey=ydRg6Ujho&profile=standard";
		}
		
		function api_search_url_next($query, $params = array()) {
			$query = urlencode($query);
			$this->page += 1;
			$start = 1 + ($this->page - 1) * $this->_hits_per_page;
			return "http://www.europeana.eu/api/v2/search.json?wskey=ydRg6Ujho&query=$query&start=$start&rows={$this->_hits_per_page}&profile=standard" . $this->_api_params_url_part($params);
		} 
		
		function api_search_url_prev($query, $params = array()) {
			$query = urlencode($query);
			$this->page -= 1;
			$start = 1 + ($this->page - 1) * $this->_hits_per_page;
			return "http://www.europeana.eu/api/v2/search.json?wskey=ydRg6Ujho&query=$query&start=$start&rows={$this->_hits_per_page}&profile=standard" . $this->_api_params_url_part($params);
		} 
		
		function api_search_url_first($query, $params = array()) {
			$query = urlencode($query);
			$this->page = 1;
			return "http://www.europeana.eu/api/v2/search.json?wskey=ydRg6Ujho&query=$query&start=1&rows={$this->_hits_per_page}&profile=standard" . $this->_api_params_url_part($params);
		}
		
		function api_search_url_last($query, $params = array()) {
			$query = urlencode($query);
			$this->page = $this->pages;
			$last = 1 + ($this->pages - 1) * $this->_hits_per_page;
			return "http://www.europeana.eu/api/v2/search.json?wskey=ydRg6Ujho&query=$query&start=$last&rows={$this->_hits_per_page}&profile=standard" . $this->_api_params_url_part($params);
		}
		
		function api_record_url($id, $params = array()) {
			return "http://www.europeana.eu/portal/record$id.html";
		}
		

		
		private function _api_params_url_part($params) {
			$return = '';
			if (isset($params['type'])) {
				$return .= "&qf=TYPE%3A{$params['type']}";
			}
			if (isset($params['onlyeagle'])) {
				$return .= "&qf=PROVIDER%3AEAGLE";
			}
			return $return;
		}
		
		function parse_result_set($response) {
			$response = json_decode($response);
			
			if (!$response->success) {
				throw new \Exception('Sussess = false'); // todo: better error message 
			}
			
			if ($response->totalResults == 0) {
				throw new \Exception('Zero results'); // todo: better error message
			}
			
			$this->results = array(); 
			foreach ($response->items as $item) {
				$data = $this->_item2html($item, $item->id);
				$this->results[] = new \esa_item('europeana', $item->id, $data->render(), $item->guid, $data->title, array(), array(), $data->latitude, $data->longitude);
			}
			
			// set up pagination data
			$this->pages = round($response->totalResults / $this->_hits_per_page);


			return $this->results;
		}

		function parse_result($response) {
			$response = json_decode($response);
			
			if (!$response->success) {
				throw new \Exception('Success = false'); // todo: better error message 
			}
			
			$item = $response->object;
			

			if (!$this->id) { //this is the case if you paste an url in the search box
				$this->id = $response->object->about;
			}
			
			$data = $this->_item2html($response->object, $this->id);
			return new \esa_item('europeana', $this->id, $data->render(), $this->api_record_url($this->id), $data->title, array(), array(), $data->latitude, $data->longitude);
			
		}
		

		function search_form_params($post) {
			//$echo = "<pre>b|" . print_r($post,1) . "</pre>";
			$echo = "<select name='esa_ds_param_type' height='1'>";
			foreach (array('', 'TEXT', 'VIDEO', 'SOUND', 'IMAGE', '3D') as $type) {
				$echo .= "<option value='$type' " . ((isset($post['esa_ds_param_type']) and ($type == $post['esa_ds_param_type'])) ? 'selected ' : '') . '>' .  ucfirst(strtolower($type)) . "</option>";
			}
			$echo .= "</select>";
			$checked = isset($post['esa_ds_param_onlyeagle']) ? 'checked' : '';
			$echo .= "<input type='checkbox' name='esa_ds_param_onlyeagle' id='esa_ds_param_onlyeagle' $checked /><label for='esa_ds_param_onlyeagle'>Only Eagle Content</label>";
			return $echo;
		}
		
		
		private function _item2html($item, $id) {
			$data = new \esa_item\data();
			
			// title
			$data->title = $item->title[0];
			
			// images
			$thumbnails = isset($item->edmPreview) ?
				$item->edmPreview : (
				isset($item->europeanaAggregation->edmPreview) ?
					$item->europeanaAggregation->edmPreview :
					'');
			
			$img = false;
			if (isset($item->aggregations)) {
				foreach ($item->aggregations as $aggregation) {
					$img  = 
						isset($aggregation->edmObject) ?
						$aggregation->edmObject :
						(isset($aggregation->edmIsShownBy) ? $aggregation->edmIsShownBy : false);
					//$data->addTable('DEBUGG', $aggregation->edmIsShownBy . '|' . $aggregation->edmObjec);			
					if ($img) {
						$data->addImages(array(
							'url' => $img,
							'fullres' => $img,
							'title' => $data->title 
						));
					}
					 
				}
			}
			
			if (!count($data->images)) {
				$data->addImages(array(
						'url' => (is_array($thumbnails)) ? $thumbnails[0] : $thumbnails,
						'title' => $data->title
				));
			}

			
			
			// other
			if (isset($item->year)) {
				if (is_array($item->year) and count($item->year) == 4) {
					$data->addTable('Year', "<a target='_blank' href='{$item->year[2]}'>{$item->year[0]}</a> - <a target='_blank' href='{$item->year[3]}'>{$item->year[1]}</a>");
				} else {
					$data->addTable('Year', $item->year);
				}
			}
			
			$data->latitude  = null;
			$data->longitude = null;
			if (isset($item->edmPlaceLatitude) and isset($item->edmPlaceLongitude)) {
				$data->latitude  = (float) $item->edmPlaceLatitude[0]; //europeana itself is only displaing the frist one hihi
				$data->longitude = (float) $item->edmPlaceLongitude[0];
				$data->addTable('Position', "{$data->latitude}, {$data->longitude}");
			}
			if (isset($item->places)) {
				$data->latitude  = (float) $item->places[0]->latitude; //europeana itself is only displaing the frist one hihi
				$data->longitude = (float) $item->places[0]->longitude;
				$place = isset($item->places[0]->prefLabel->en) ? $item->places[0]->prefLabel->en[0] : $item->places[0]->prefLabel->def[0];
				$data->addTable('Place', "<a href='{$item->places[0]->about}' target='_blank'>$place</a>");
				$data->addTable('Position', "{$data->latitude}, {$data->longitude}");
			}
			
			$data->addTable('Type', ucfirst(strtolower($item->type)));

			if (count($item->title) > 1) {
				$data->addTable('Alternative titles', implode(',', array_slice($item->title, 1)));
			}
			if (count($item->provider)) {
				$data->addTable('Provider', implode(',', $item->provider));
			}
			
			if (isset($item->europeanaAggregation) and isset($item->europeanaAggregation->edmCountry)) {
				$country = isset($item->europeanaAggregation->edmCountry->def) ? implode(', ', $item->europeanaAggregation->edmCountry->def) : (
						isset($item->europeanaAggregation->edmCountry->en) ? implode(', ', $item->europeanaAggregation->edmCountry->en) : ''
						); 
			}
			if (isset($country) and $country) {
				$data->addTable('Country', $country);
			}
			
			// concepts
			if (isset($item->concepts) and count($item->concepts)) {
				$tags = array();
				$d = array();
				$d = array();
				foreach ($item->concepts as $concept) {
					if (isset($concept->prefLabel)) {
						$tags[] = isset($concept->prefLabel->en) ? $concept->prefLabel->en[0] : isset($concept->altLabel->en) ? $concept->altLabel->en[0] : array_values((array) $concept->prefLabel)[0][0];
					}
				}
				$tags = array_diff($tags, array('?', 'unknown', 'Ignoratur'));
				if (count($tags)) {
					$data->addTable('Keywords', implode(', ', array_unique($tags)));
				}

			}
			
			// proxies
			$no_repeat = array();
			if (isset($item->proxies)) {
				foreach ($item->proxies as $proxy) {
					foreach ($proxy as $prop => $pval) {
						if (preg_match('#dc(terms)?(.*)#', $prop, $match)) {
							$cat = $match[2];


							$data->addTable($cat, $this->_LangMap($pval));

						}
					}
				}
			}
			$data->addTable('id', $id);
			
			if (!$data->title and isset($data->table['Title'])) {
				$data->title = $data->table['Title'];
				unset($data->table['Title']);
			}
			
			
			//rights
			if (isset($item->rights)) {
				$data->addTable('Licence', "<a href='{$item->rights[0]}' target='_blank'>{$item->rights[0]}</a>");
			}

			//$data->addTable('id', $this->api_single_url($id));
			
			return $data;
		}
		
		
		/*if (!preg_match('#dc(.*)#', $prop, $match)) {
		 return array(false, false);
		 }
		
		 $name = $match[1];
		 */
		
		private function _LangMap($p) {

			if (isset($p->def) ) { //and 
				$def = $p->def;
				unset($p->def);
			}
			
			if (isset($p->en)) {
				$vals = array_unique($p->en);
			} elseif (count((array) $p)) {
				$vals = array_unique(array_values((array) $p)[0]);
			}
			

			if ($def) {
				$def = array_unique($def);
				$i = 0;
				foreach ($def as $d) {
					if (filter_var($d, FILTER_VALIDATE_URL)) {
						$i++;						
						$defstring .= " <a href='$d' title='$d' target='_blank'>[{$i}]</a>";
					} else {
						$vals[] = $d;
					}
				}
			}
			$vals = is_array($vals) ? $vals : array();
			return implode(', ', $vals) . $defstring;
			
		}
		
		/*
		private function _LangMap($p) {
			if (!is_object($p) and !is_array($p)) {
				return array($p);
			}
			
			$p = (array) $p;
			
			$collector = array();
			
			foreach($p as $lng => $token) {
				if ($lng == 'def') {
					
				}
				
				
				if (!is_array($token)) {
					$collector[] = $token;
				} else {
					$collector = array_merge($collector, $token);
				}
			}
			
			return $collector;
			
		}
		*/
		
		function stylesheet() {
			$css = "
				.esa_item_europeana {
					background-image: url('../images/europeana-logo-2.png');
					background-position: top right;
					background-repeat: no-repeat;
				}";
			return array(
				'css' => $css,
				'name' => 'europeana'
			);
		}
			
		
	}
}
?>