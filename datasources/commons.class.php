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
			if (preg_match('#https?\:\/\/commons.wikimedia.org\/wiki\/.*\#\/media\/File\:(.*)#', $string, $match)) {
			//if (preg_match('#https?\:\/\/commons.wikimedia.org\/(.*)#', $string, $match)) {
				echo "<br><textarea>", print_r($match), "</textarea>";
				return "https://tools.wmflabs.org/magnus-toolserver/commonsapi.php?image={$match[1]}&thumbwidth=150&thumbheight=150";
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
			
			
			
			foreach (__whatever__ as $page) {
					
				$html  = "<div class='esa_item_left_column'>";
				$html .= "<div class='esa_item_main_image' style='background-image:url(\"{/* image url */}\")'>&nbsp;</div>";
				$html .= "</div>";
					
				$html .= "<div class='esa_item_right_column'>";
				$html .= "<h4>{/* title */}</h4>";

				$html .= "<ul class='datatable'>";
				$html .= "<li><strong>{/* field */}: </strong>{/* data */}</li>";
				$html .= "</ul>";
				
				$html .= "</div>";
					
					
				$this->results[] = new \esa_item(__source__, __id__, $html, __url__);
			}
			return $this->results;
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