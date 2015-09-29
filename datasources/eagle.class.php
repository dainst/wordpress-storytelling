<?php
namespace esa_datasource {
	class eagle extends abstract_datasource {
		
		public $title = 'test';
		
			function search_form() {
				echo "search dialgue";
			}
		
			function search($searchString) {
				echo "search"; 
			}
			
			function preview() {
				echo "preview";	
			}
			
			function interprete_result($result) {
			}
			
			
			function parse_result_set($result){
				
			}
			
			function parse_result($result){
				
			}
			
			function api_single_url($id){
				
			}
			
			function api_search_url($query){
				
			}
			
			function api_record_url($id){
				
			}
			
			function api_url_parser($id){
				
			}
		
	}
}
?>