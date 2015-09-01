<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: Europeana
 * @link 		http://www.europeana.eu/
 * @author 		Philipp Franck
 * 
 * Status: Test Product 
 *  
 * This is a simple data source wich searches the wikipedia for images to any keyword. 
 * It is not very useful. 
 * I just designed as example of how we cwill beable to use this engine to serach and retrieve in various data sources in the future.
 *
 */
namespace esa_datasource {
	/**
	 * 

	 * 
	 * @author Philipp Franck
	 *
	 */
	class wiki extends abstract_datasource {
		
			public $pagination = false;
		
			//public $apiurl = "https://en.wikipedia.org/w/api.php?action=query&format=json&list=allimages&titles=%s";
			function api_search_url($query, $params = array()) {
				//return "https://en.wikipedia.org/w/api.php?action=query&prop=info|pageimages&piprop=thumbnail&inprop=url&format=json&titles=$query";
				return "https://en.wikipedia.org/w/api.php?action=query&prop=extracts|images&format=json&exintro=&titles=$query";
			}
			
			function api_single_url($id) {
				return "https://en.wikipedia.org/w/api.php?action=query&prop=info|pageimages&piprop=thumbnail&inprop=url&format=json&pageids=$id";
			}

			function api_record_url($id) {
				return "https://en.wikipedia.org/?curid=$id";
			}
			
			function api_url_parser($string) {
				if (preg_match('#https?\:\/\/en\.wikipedia\.org\/wiki\/(.*)#', $string, $match)) {
					return "https://en.wikipedia.org/w/api.php?action=query&prop=info|pageimages&piprop=thumbnail&inprop=url&format=json&titles={$match[1]}";
				}
			}
			
			public $info = "This is a simple data source wich searches the wikipedia for images to any keyword. <br>Is is not very useful.<br> I just designed it as example of how we will be able to use this engine to search and retrieve in various data sources in the future. <br> Just insert something and press 'search'. <br>-  philipp";    
			public $title = 'Wikipedia';
			
			function parse_result_set($response) {
				$response = json_decode($response);
				$this->results = array(); 
				foreach ($response->query->pages as $page) {
					foreach ($page->images as $image) {
						
						$imageData = $this->_fetch_external_data("https://commons.wikimedia.org/w/api.php?action=query&format=json&titles=".urlencode($image->title)."&prop=imageinfo&iiprop=url%7Cextmetadata&iiurlwidth=150");
						
						//{"batchcomplete":"","query":{"pages":{"26631028":{"pageid":26631028,"ns":6,"title":"File:Air Berlin Boeing 737-700; D-ABBT@TXL;31.12.2012 685ap (8438321804).jpg","imagerepository":"local","imageinfo":[{"thumburl":"https://upload.wikimedia.org/wikipedia/commons/thumb/e/ed/Air_Berlin_Boeing_737-700%3B_D-ABBT%40TXL%3B31.12.2012_685ap_%288438321804%29.jpg/50px-Air_Berlin_Boeing_737-700%3B_D-ABBT%40TXL%3B31.12.2012_685ap_%288438321804%29.jpg","thumbwidth":50,"thumbheight":33,"url":"https://upload.wikimedia.org/wikipedia/commons/e/ed/Air_Berlin_Boeing_737-700%3B_D-ABBT%40TXL%3B31.12.2012_685ap_%288438321804%29.jpg","descriptionurl":"https://commons.wikimedia.org/wiki/File:Air_Berlin_Boeing_737-700;_D-ABBT@TXL;31.12.2012_685ap_(8438321804).jpg"}]}}}}
						
						$imageData = json_decode($imageData);
						
						foreach ($imageData->query->pages as $imageItem) {
						
							$html  = "<div class='esa_item_left_column'>";
							//$html .= "<span class='esa_item_main_image' src='' alt='thumpnail'>";
							$html .= "<div class='esa_item_main_image' style='background-image:url(\"{$imageItem->imageinfo[0]->thumburl}\")'>&nbsp;</div>";
							//$html .= "<pre>".print_r($imageData,1)."</pre>";
							$html .= "<div>{$imageItem->imageinfo[0]->extmetadata->ImageDescription->value}</div>";
							$html .= "</div>";
							
							$html .= "<div class='esa_item_right_column'>";
							$html .= "<h4>{$page->title}</h4>";
							//$html .= "<pre>".print_r($image,1)."</pre>";
							//$html .= "<pre>".print_r($imageData,1)."</pre>";
							$html .= "<p>{$page->extract}</p>";
							$html .= "</div>";
						
							$this->results[] = new \esa_item('wiki', $page->pageid, $html, $page->fullurl);
						}
					}
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