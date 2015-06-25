<?php
namespace esa_datasource {
	/**
	 * 
	 * This is a simple data source wich searches the wikipedia for images to any keyword. 
	 * It is not very useful. 
	 * I just designed as example of how we cwill beable to use this engine to serach and retrieve in various data sources in the future.
	 * 
	 * @author Philipp Franck
	 *
	 */
	class wiki extends abstract_datasource {
		
			public $pagination = false;
		
			//public $apiurl = "https://en.wikipedia.org/w/api.php?action=query&format=json&list=allimages&titles=%s";
			function api_search_url($query) {
				return "https://en.wikipedia.org/w/api.php?action=query&prop=info|pageimages&piprop=thumbnail&inprop=url&format=json&titles=$query";
			}
			
			function api_single_url($id) {
				return "https://en.wikipedia.org/w/api.php?action=query&prop=info|pageimages&piprop=thumbnail&inprop=url&format=json&pageids=$id";
			}

			public $info = "This is a simple data source wich searches the wikipedia for images to any keyword. <br>Is is not very useful.<br> I just designed it as example of how we will be able to use this engine to search and retrieve in various data sources in the future. <br> Just insert something and press 'search'. <br>-  philipp";    
			public $title = 'Test Subplugin for Wikipedia';
			
			function parse_result_set($response) {
				$response = json_decode($response);
				$this->results = array(); 
				foreach ($response->query->pages as $page) {
					$this->results[] = new \esa_item('wiki', $page->pageid, "<div class='subtext'>{$page->title}</div><img src='{$page->thumbnail->source}'>", $page->fullurl);
				}
				return $this->results;
			}

			function parse_result($response) {
				// wikipedia always return a whole set
				$res = $this->parse_result_set($response);
				return $res[0];
			}
		
	}
}
?>