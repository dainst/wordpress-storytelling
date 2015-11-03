<?php
/**
 * @package 	Wikimedia Commons
 * @subpackage	Search in Datasources | Subplugin: Wikimedia Commons
 * @link 		
 * @author 		Philipp Franck
 *
 * Status: Alpha 1
 *
 */


namespace esa_datasource {
	class commons extends abstract_datasource {

		public $title = 'Wikimedia Commons'; // Label / Title of the Datasource
		public $info = false; // get created automatically, or enter text
		public $homeurl; // link to the dataset's homepage
		public $debug = true;
		//public $examplesearch; // placeholder for search field
		//public $searchbuttonlabel = 'Search'; // label for searchbutton
		
		public $pagination = false; // are results paginated?
		public $optional_classes = array(); // some classes, the user may add to the esa_item

		public $require = array();  // require additional classes -> array of fileanmes	
		
		function api_search_url($query, $params = array()) {
			//return "https://commons.wikimedia.org/w/api.php?action=opensearch&search=$query&limit=1000";
		}
			
		function api_single_url($id) {
			return "https://tools.wmflabs.org/magnus-toolserver/commonsapi.php?image=$id&thumbwidth=150&thumbheight=150";
		}


		
		function api_record_url($id) {
			return "";
		}
			
		function api_url_parser($string) {
			if (preg_match('#https?\:\/\/commons.wikimedia.org\/wiki\/.*\#\/media\/(File\:.*)#', $string, $match)) {
			//if (preg_match('#https?\:\/\/commons.wikimedia.org\/(.*)#', $string, $match)) {

				$title = urlencode($match[1]);
				//return "https://tools.wmflabs.org/magnus-toolserver/commonsapi.php?image={$match[1]}&thumbwidth=150&thumbheight=150";
				$url = "https://commons.wikimedia.org/w/api.php?action=query&prop=imageinfo&format=json&iiprop=url|size|mediatype|extmetadata&iiurlwidth=150&titles=$title";
				echo "<br><textarea>", print_r($url), "</textarea>";
				return $url;
			}
		}
		/*	pagination functions
		function api_search_url_next($query, $params = array()) {
			
		}
			
		function api_search_url_prev($query, $params = array()) {
			
		}
			
		function api_search_url_first($query, $params = array()) {
			
		}
			
		function api_search_url_last($query, $params = array()) {
			
		}
		*/	
		function parse_result_set($response) {
			$response = json_decode($response);
			$this->results = array();
			echo "<br><textarea>", print_r($response), "</textarea>";
			
			
			foreach ($response->query->pages as $pageId => $page) {
					
					
				$this->results[] = new \esa_item('commons', $pageId, $this->render_item($this->fetch_information($page)), $this->api_single_url($pageId));
			}
			return $this->results;
		}

		
		function fetch_information($page) {
			$data = array('table'=>array(), 'text'=>array(), 'images'=>array());
			
			$data['images'][] = new \esa_item\image(array(
				'type' 	=>	$page->imageinfo[0]->mediatype,
				'url'	=>	$page->imageinfo[0]->thumburl,
				'fullres' => $page->imageinfo[0]->url,
				'mime'	=>	$page->imageinfo[0]->mime,
				'text'	=>	strip_tags($page->imageinfo[0]->extmetadata->ImageDescription->value)
			));
			
			preg_match('#File\:(.*)\..*#', $page->title, $matches);
			$data['title'] = $matches[1];
			
			return $data;			
		}
		
		
		function parse_result($response) {
			// if always return a whole set
			$res = $this->parse_result_set($response);
			return $res[0];
		}

		function stylesheet() {
			return array(
				'name' => get_class($this),
				'css' => ''
			);
		}

	}
}
?>