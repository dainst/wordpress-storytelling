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
			function api_search_url($query, $params = array()) {
				return "https://en.wikipedia.org/w/api.php?action=query&prop=info|pageimages&piprop=thumbnail&inprop=url&format=json&titles=$query";
			}
			
			function api_single_url($id) {
				return "https://en.wikipedia.org/w/api.php?action=query&prop=info|pageimages&piprop=thumbnail&inprop=url&format=json&pageids=$id";
			}

			function api_record_url($id) {
				return "https://en.wikipedia.org/?curid=$id";
			}
			
			public $info = "This is a simple data source wich searches the wikipedia for images to any keyword. <br>Is is not very useful.<br> I just designed it as example of how we will be able to use this engine to search and retrieve in various data sources in the future. <br> Just insert something and press 'search'. <br>-  philipp";    
			public $title = 'Test Subplugin for Wikipedia';
			
			function parse_result_set($response) {
				$response = json_decode($response);
				$this->results = array(); 
				foreach ($response->query->pages as $page) {			
					
					$html  = "<div class='esa_item_left_column'>";
					//$html .= "<span class='esa_item_main_image' src='' alt='thumpnail'>";
					$html .= "<div class='esa_item_main_image' style='background-image:url(\"{$page->thumbnail->source}\")'>&nbsp;</div>";
					$html .= "</div>";
					
					$html .= "<div class='esa_item_right_column'>";
					$html .= "<h4>{$page->title}</h4>";
					$html .= "<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu.<br>In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus.<br>Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, </p>";
					$html .= "</div>";
					
					
					$this->results[] = new \esa_item('wiki', $page->pageid, $html, $page->fullurl);
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