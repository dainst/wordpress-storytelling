<?php
namespace esa_datasource {
	/**
	 * 
	 * The Subplugin to retrieve Data from Europeana!
	 * 
	 * 
	 * @author philipp franck
	 *
	 */
	class europeana extends abstract_datasource {
		

			public $title = "Europeana Connection Alpha 0.2";
			public $info = "Europeana Connection Alpha 0.2"; 
			

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
				
				return new \esa_item('europeana', $response->object->id, $this->_item2html($response->object, $this->id), $this->api_record_url($this->id));
				
			}
			

			private function _item2html($item, $id) {
				$html  = "<div class='esa_item_left_column'>";
				
				$thumpnails = isset($item->edmPreview) ?
					$item->edmPreview : (
					isset($item->europeanaAggregation->edmPreview) ?
						$item->europeanaAggregation->edmPreview :
						'');
				$thumpnail = is_array($thumpnails) ? $thumpnails[0] : $thumpnails;
				 	
				$html .= "<img src='$thumpnail' alt='thumpnail'>";
				$html .= "</div>";
				
				$html .= "<div class='esa_item_right_column'>";
				$html .= "<h4>{$item->title[0]}</h4>";
				$html .= "<ul class='datatable'>";
				$html .= "<li><strong>id: </strong>{$id}</li>";
				if (isset($item->year)) {
					$html .= "<li><strong>Year: </strong>{$item->year}</li>";
				}
				$html .= 	 "<li><strong>Type: </strong>{$item->type}</li>";
				if (count($item->title) > 1) {
					$html .= "<li><strong>Alternative titles: </strong>" . implode(',', array_slice($item->title, 1)) . "</li>";
				}
				if (count($item->provider)) {
					$html .= "<li><strong>Provider: </strong>" . implode(',', $item->provider) . "</li>";
				}
				if (isset($item->concepts) and count($item->concepts)) {
					$tags = array();
					$d = array();
					foreach ($item->concepts as $concept) {
						if (isset($concept->prefLabel)) {
							$tags[] = isset($concept->prefLabel->en) ? $concept->prefLabel->en[0] : isset($concept->altLabel->en) ? $concept->altLabel->en[0] : array_values((array) $concept->prefLabel)[0][0];
						}
						//$html .= "<li><hr>X<pre>" .  print_r(array_values((array) $concept->prefLabel), 1) . "</pre></li>";; 
					}
					$html .= "<li><strong>Keywords: </strong>" . implode(', ', array_unique($tags)) . "</li>";
					//$html .= "<li><hr><pre>" . print_r($item->concepts, 1) . "</pre></li>";
				}
				
				
				$html .= "</ul>";
				
				
				
				$html .= "</div>";
				return $html;
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
			
		
	}
}
?>