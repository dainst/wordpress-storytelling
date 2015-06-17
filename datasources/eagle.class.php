<?php
namespace esa_datasource {
	class eagle extends abstract_datasource {
		
		
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
				;
			}
		
	}
}
?>