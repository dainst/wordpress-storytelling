<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: iDAI Gazetteer
 * @link 		http://gazetteer.dainst.org/
 * @author 		Philipp Franck
 *
 * Status: Alpha 2
 * 
 * Sub-Plugin is nearly ready, there is only one Problem: Ich bekomme einen 400er Bad Request wenn ich die API anspreche, um einen einzelnen Record
 * zu bekommen. Eine URL wie http://gazetteer.dainst.org/doc/2281530.json, die im Browser ein Ergebnis liefert, klappt mit PHP über curl oder
 * file_get_contents nicht.  
 *
 */


namespace esa_datasource {
	class idai extends abstract_datasource {


		public $title = 'iDAI Gazetteer';
		public $homeurl = "http://gazetteer.dainst.org/";
		
		public $pagination = false;

		function api_search_url($query, $params = array()) {
			$query = urlencode($query);
			return "http://gazetteer.dainst.org/search.json?q={$query}";
		}
			
		function api_single_url($id) {
			$query = urlencode($id);
			return "http://gazetteer.dainst.org/doc/$id.json";
		}

		function api_record_url($id) {
			$query = urlencode($id);
			return "http://gazetteer.dainst.org/app/#!/show/$id";
		}
			
		function api_url_parser($string) {
			if (preg_match('#http://gazetteer.dainst.org/app/\#\!\/show\/(.*)\??.?#', $string, $match)) {
				return "https?://gazetteer.dainst.org/doc/{$match[1]}.json";
			}
		}
			

			
		function parse_result_set($response) {
			$response = $this->_json_decode($response);


			$this->results = array();
			$list = (isset($response->result)) ? $response->result : array($response);
			foreach ($list as $result) {

				$the_name = $result->prefName->title;
				$the_name .= ($result->prefName->ancient == true) ? ' (ancient)' : '';
				$alt_names = array();
				foreach($result->names as $name) {
					$alt_names[] = $name->title;
				}
				$name_list = implode(', ', $alt_names);
				$type_list = (isset($result->types)) ? implode(', ', $result->types) : '';
				$type_label = (count($type_list) > 1) ? 'Types' : "Type";
				
				list($long, $lat) = $result->prefLocation->coordinates;
				
				$html  = "<div class='esa_item_left_column_max_left'>";
				$html .= "<div class='esa_item_map' id='esa_item_map-{$result->gazId}@idai' data-latitude='$lat' data-longitude='$long'>&nbsp;</div>";
				$html .= "</div>";
				
				$html .= "<div class='esa_item_right_column_max_left'>";
				$html .= "<h4>$the_name</h4>";

				$html .= "<ul class='datatable'>";
				$html .= "<li><strong>Names: </strong>$name_list</li>";
				$html .= "<li><strong>$type_label: </strong>$type_list</li>";
				$html .= "<li><strong>Latitude: </strong>$lat</li>";
				$html .= "<li><strong>Longitude: </strong>$long</li>";
				$html .= "</ul>";
				
				$html .= "</div>";
					
				$this->results[] = new \esa_item('idai', $result->gazId, $html, $this->api_record_url($result->gazId));
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