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
	class /* name */ extends abstract_datasource {

		public $info = "Generic Info Text...";
		public $title = 'Title';
		
		public $pagination = false;

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
			foreach (/* whatever */ as $page) {
					
				$html  = "<div class='esa_item_left_column'>";
				$html .= "<div class='esa_item_main_image' style='background-image:url(\"{/* image url */}\")'>&nbsp;</div>";
				$html .= "</div>";
					
				$html .= "<div class='esa_item_right_column'>";
				$html .= "<h4>{/* title */}</h4>";

				$html .= "<ul class='datatable'>";
				$html .= "<li><strong>{/* field */}: </strong>{/* data */}</li>";
				$html .= "</ul>";
				
				$html .= "</div>";
					
					
				$this->results[] = new \esa_item(/* source */, /* id */, $html, /* url */);
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