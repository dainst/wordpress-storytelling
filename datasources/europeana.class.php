<?php
namespace esa_datasource {
	/**
	 * 
	 * The Subplugin to retrieve Data from Europeana!
	 * 
	 * @author philipp franck
	 *
	 */
	class europeana extends abstract_datasource {
		
			//public $apiurl = "https://en.wikipedia.org/w/api.php?action=query&format=json&list=allimages&titles=%s";
			public $api_search_url = "http://www.europeana.eu/api/v2/search.json?wskey=ydRg6Ujho&query=%s&start=1&rows=24&profile=standard";
			public $api_single_url = "";

			public $title = "Europeana Connection Alpha 0.1";
			public $info = "Europeana Connection Alpha 0.1";    
			
			function parse_result_set($response) {
				$response = json_decode($response);
				
				if (!$response->success) {
					throw new \Exception('Sussess = false'); // todo: better error message 
				}
				
				
				$this->results = array(); 
				foreach ($response->items as $item) {
					$html  = "<div class='wrapper'>"; 
					$html .= "<h1>{$item->title[0]}</h1>";
					$html .= "<table class='datatable'>";
					$html .= "<tr><td>id</td><td>{$item->id}</td></tr>";
					$html .= "<tr><td>year</td><td>{$item->year}</td></tr>";
					$html .= "<tr><td>type</td><td>{$item->type}</td></tr>";
					if (count($item->title) > 1) {
						$html .= "<tr><td>alternative titles</td><td>" . implode(',', array_slice($item->title, 1)) . "</td></tr>";
					}
					if (count($item->provider)) {
						$html .= "<tr><td>provider</td><td>" . implode(',', $item->provider) . "</td></tr>";
					}
					if (count($item->dataProvider)) {
						$html .= "<tr><td>data provider</td><td>" . implode(',', $item->dataProvider) . "</td></tr>";
					}
					$html .= "</table>";
					$html .= "<img src='{$item->edmPreview[0]}'>";
					$html .= "</div>";
					$this->results[] = new \esa_item('europeana', $item->id, $html);
				}
				return $this->results;
			}

			function parse_result($response) {
				// wikipedia always return a whole set
				$res = $this->parse_result_set($response);
				return $res[0];
			}
			
			function api_encode_fn($string, $is_single = false) {
				return urlencode($string);
			}
		
	}
}
?>