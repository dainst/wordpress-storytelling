<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: 
 * @link 		
 * @author 		
 *
 * Status: Alpha 1
 *
 */


namespace esa_datasource {
	class __NAME__ extends abstract_datasource {

		public $title = 'Title'; // Label / Title of the Datasource
		public $info = false; // get created automatically, or enter text
		public $homeurl; // link to the dataset's homepage
		public $debug = false;
		
		public $pagination = false; // are results paginated?
		public $optional_classes = array(); // some classes, the user may add to the esa_item

		function api_search_url($query, $params = array()) {
			return "";
		}
			
		function api_single_url($id) {
			return "";
		}


		
		function api_record_url($id) {
			return "";
		}
			
		function api_url_parser($string) {
			if (preg_match('#https?\:\/\/en\.wikipedia\.org\/wiki\/(.*)#', $string, $match)) {
				return "...{$match[1]}";
			}
		}
			

			
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
		

	}
}
?>