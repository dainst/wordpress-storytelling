<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: Europeana
 * @link 		http://www.europeana.eu/
 * @author 		Philipp Franck
 * 
 * Status: Beta
 * 
 */
namespace esa_datasource {
	class europeana extends abstract_datasource {
		

			public $title = "Europeana";
			public $homeurl = "http://www.europeana.eu/portal/";

			private $_hits_per_page = 24;
			
			function api_search_url($query, $params = array()) {
				$query = urlencode($query);
				return "http://www.europeana.eu/api/v2/search.json?wskey=ydRg6Ujho&query=$query&start=1&rows={$this->_hits_per_page}&profile=standard" . $this->_api_params_url_part($params);
			}
			
			function api_single_url($id) {
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
			
			function api_record_url($id) {
				return "http://www.europeana.eu/portal/record$id.html";
			}
			
			function api_url_parser($string) {
				if (preg_match('#http\:\/\/www\.europeana\.eu\/portal\/record(.*)\.html.*#', $string, $match)) {
					return $this->api_single_url($match[1]);
				}
			}
			
			private function _api_params_url_part($params) {
				if (isset($params['type'])) {
					return "&qf=TYPE%3A{$params['type']}";
				}
				return '';
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
					$this->results[] = new \esa_item('europeana', $item->id, $this->_item2html($item, $item->id), $item->guid);
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
					$this->id = $item->about;
				}
				
				return new \esa_item('europeana', $response->object->id, $this->_item2html($response->object, $this->id), $this->api_record_url($this->id));
				
			}
			

			function search_form_params($post) {
				//$echo = "<pre>b|" . print_r($post,1) . "</pre>";
				$echo = "<select name='esa_ds_param_type' height='1'>";
				foreach (array('', 'TEXT', 'VIDEO', 'SOUND', 'IMAGE', '3D') as $type) {
						
					$echo .= "<option value='$type' " . (($type == $post['esa_ds_param_type']) ? 'selected ' : '') . '>' .  ucfirst(strtolower($type)) . "</option>";
				}
				$echo .= "</select>";
				return $echo;
			}
			
			
			private function _item2html($item, $id) {
				$html  = "<div class='esa_item_left_column'>";
				
				$thumbnails = isset($item->edmPreview) ?
					$item->edmPreview : (
					isset($item->europeanaAggregation->edmPreview) ?
						$item->europeanaAggregation->edmPreview :
						'');
				$thumbnail = is_array($thumbnails) ? $thumbnails[0] : $thumbnails;
				 	
				$html .= "<div class='esa_item_main_image' style='background-image:url(\"$thumbnail\")'>&nbsp;</div>";
				$html .= "</div>";
				
				$html .= "<div class='esa_item_right_column'>";
				$html .= "<h4>{$item->title[0]}</h4>";
				$html .= "<ul class='datatable'>";
								
				$html .= "<li><strong>id: </strong>{$id}</li>";
				if (isset($item->year)) {
					$year = (is_array($item->year)) ? implode(' ,', $item->year) : $item->year;
					$html .= "<li><strong>Year: </strong>$year</li>";
				}
				$html .= 	 "<li><strong>Type: </strong>" . ucfirst(strtolower($item->type)) . "</li>";
				if (count($item->title) > 1) {
					$html .= "<li><strong>Alternative titles: </strong>" . implode(',', array_slice($item->title, 1)) . "</li>";
				}
				if (count($item->provider)) {
					$html .= "<li><strong>Provider: </strong>" . implode(',', $item->provider) . "</li>";
				}
				
				if (isset($item->europeanaAggregation) and isset($item->europeanaAggregation->edmCountry)) {
					$country = isset($item->europeanaAggregation->edmCountry->def) ? implode(', ', $item->europeanaAggregation->edmCountry->def) : (
							isset($item->europeanaAggregation->edmCountry->en) ? implode(', ', $item->europeanaAggregation->edmCountry->en) : ''
							); 
				}
				if ($country) {
					$html .= "<li><strong>Country: </strong>$country</li>";
				}
				
				// concepts
				if (isset($item->concepts) and count($item->concepts)) {
					$tags = array();
					$d = array();
					foreach ($item->concepts as $concept) {
						if (isset($concept->prefLabel)) {
							$tags[] = isset($concept->prefLabel->en) ? $concept->prefLabel->en[0] : isset($concept->altLabel->en) ? $concept->altLabel->en[0] : array_values((array) $concept->prefLabel)[0][0];
						}
						//$html .= "<li><hr>X<pre>" .  print_r(array_values((array) $concept->prefLabel), 1) . "</pre></li>";; 
					}
					$tags = array_diff($tags, array('?', 'unknown', 'Ignoratur'));
					if (count($tags)) {
						$html .= "<li><strong>Keywords: </strong>" . implode(', ', array_unique($tags)) . "</li>";
						//$html .= "<li><hr><pre>" . print_r($item->concepts, 1) . "</pre></li>";
					}

				}
				
				// proxies
				$no_repeat = array();
				if (isset($item->proxies)) {
					foreach ($item->proxies as $proxy) {
						foreach ($proxy as $prop => $pval) {
							if (preg_match('#dc(terms)?(.*)#', $prop, $match)) {
								foreach ($this->_LangMap($pval) as $v) {
									if (filter_var($v, FILTER_VALIDATE_URL)) {
										$v = "<a href='$v' target='_blank'>$v</a>";
									}
									if (!isset($no_repeat[$match[2]]) or ($no_repeat[$match[2]] != $v)) {
										$html .= "<li><strong>$match[2]: </strong>$v</li>";
										$no_repeat[$match[2]] = $v;
									}
									
								}
							}
						}
					}
				}
				
				
				$html .= "</ul>";
				
				$html .= "</div>";
				return $html;
			}
			
			
			/*if (!preg_match('#dc(.*)#', $prop, $match)) {
			 return array(false, false);
			 }
			
			 $name = $match[1];
			 */
			
			private function _LangMap($p) {
				if (!is_object($p) and !is_array($p)) {
					return array($p);
				}
				
				$p = (array) $p;
				
				$collector = array();
				
				foreach($p as $lng => $token) {
					if (!is_array($token)) {
						$collector[] = $token;
					} else {
						$collector = array_merge($collector, $token);
					}
				}
				
				return $collector;
				
				
			}
			
			

			
		
	}
}
?>