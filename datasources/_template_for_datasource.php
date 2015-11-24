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
		//public $examplesearch; // placeholder for search field
		//public $searchbuttonlabel = 'Search'; // label for searchbutton
		
		public $pagination = false; // are results paginated?
		public $optional_classes = array(); // some classes, the user may add to the esa_item

		public $require = array();  // require additional classes -> array of fileanmes	
		
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
			foreach ($this->results[1] as $i => $name) {

				
				/* old way of doint it 
				
				$title = $this->results[2];
				$url = $this->results[3];
				$html  = "<div class='esa_item_left_column'>";
				$html .= "<div class='esa_item_main_image' style='background-image:url(\"{ image url }\")'>&nbsp;</div>";
				$html .= "</div>";
					
				$html .= "<div class='esa_item_right_column'>";
				$html .= "<h4>{ title }</h4>";

				$html .= "<ul class='datatable'>";
				$html .= "<li><strong>{ field }: </strong>{ data }</li>";
				$html .= "</ul>";
				
				$html .= "</div>";
				*/
				
				$data = new \esa_item\data();
				
				$data->title = __title__;
				$data->addText($key, $value);
				$data->addTable($key, $value);
				$data->addImages(array(
					'url' 		=> '',
					'fullres' 	=> '',
					'type' 		=> 'BITMAP',
					'mime' 		=> '',
					'title' 	=> '',
					'text' 		=> ''
				));
				
				
					
				$this->results[] = new \esa_item(__source__, __id__, $data->render(), __url__);
			}
			return array();
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