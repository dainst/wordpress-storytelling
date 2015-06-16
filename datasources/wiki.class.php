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
			public $apiurl = "https://en.wikipedia.org/w/api.php?action=query&list=allimages&format=json&aifrom=%s&aiprop=url&ailimit=15";

			public $info = "This is a simple data source wich searches the wikipedia for images to any keyword. <br>Is is not very useful.<br> I just designed it as example of how we will be able to use this engine to search and retrieve in various data sources in the future. <br> Just insert something and press 'search'. <br>-  philipp";    
			
			function interprete_result($response) {
				$response = json_decode($response);
				$this->results = array(); 
				foreach ($response->query->allimages as $image) {
					$this->results[] = new \esa_item('wiki', $pageId, "<div class='subtext'>{$image->title}</div><img src='{$image->url}'>"); 
				}
				return $this->results;
			}
			
			function preview() {
				echo "preview";	
			}
			
			function insert() {
				;
			}
		
	}
}
?>