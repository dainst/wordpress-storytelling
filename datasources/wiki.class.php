<?php
namespace esa_datasource {
	/**
	 * 
	 * This is a simple data source wich searches the wikipedia for images to any keyword. 
	 * It is not very useful. 
	 * I just designed as example of how we cwill beable to use this engine to serach and retrieve in various data sources in the future.
	 * 
	 * @author philipp franck
	 *
	 */
	class wiki extends abstract_datasource {
		
			//public $apiurl = "https://en.wikipedia.org/w/api.php?action=query&format=json&list=allimages&titles=%s";
			public $api_search_url = "https://en.wikipedia.org/w/api.php?action=query&prop=pageimages&piprop=thumbnail&format=json&titles=%s";
			public $api_single_url = "https://en.wikipedia.org/w/api.php?action=query&prop=pageimages&piprop=thumbnail&format=json&pageids=%s";

			public $info = "This is a simple data source wich searches the wikipedia for images to any keyword. <br>Is is not very useful.<br> I just designed it as example of how we will be able to use this engine to search and retrieve in various data sources in the future. <br> Just insert something and press 'search'. <br>-  philipp";    
			public $title = 'Test Subplugin for Wikipedia';
			
			function parse_result_set($response) {
				$response = json_decode($response);
				$this->results = array(); 
				foreach ($response->query->pages as $page) {
					$this->results[] = new \esa_item('wiki', $page->pageid, "<div class='subtext'>{$page->title}</div><img src='{$page->thumbnail->source}'>");
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