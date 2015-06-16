<?php
namespace esa_datasource {
	abstract class abstract_datasource {
	
		// url of the api, the source uses (important, if default search dialogue nad/or search functions are used)
		public $apiurl;
		
		// infotext to this data source
		public $info; 
		
		// array of esa_items containing the results of a performed search
		public $results = array();
				
		/**
		 * a generic search dialogue (can be overwitten) 	
		 * - needs $this->apiurl to be set	
		 */
		function dialogue() {
			/*
			echo "<pre>";
			print_r($_POST);
			echo "</pre>";
			*/
			
			echo "<p>{$this->info}</p>";
			
			$query = (isset($_POST['esa_ds_query'])) ? $_POST['esa_ds_query'] : '';
			echo "<form method='post'>";
			echo "<input type='text' name='esa_ds_query' value='{$query}'>";
			echo "<input type='submit' class='button button-primary' value='Search'>";
			echo "</form>";
		}
		
		/**
		 * a generic search based on $this->apiurl 
		 * - %1 in apiurl becomes replaced by search string
		 * - trys to use $_POST['esa_ds_query'] as search string, when $query is not given
		 * 
		 * @param string $searchString
		 * 
		 * @return array of result 
		 */
		function search($query = null) {
			$query = (isset($_POST['esa_ds_query'])) ? $_POST['esa_ds_query'] : $query; 
			
			if (!$query) {
				return $this->error('No Query');
			}

			
			$url = sprintf($this->apiurl, $query);
			//echo $url;
			
			$response = $this->_fetch_external_data($url);

			/*
			echo "<pre>";
			print_r($response);
			echo "</pre>";
			*/
			$result = $this->interprete_result($response);
				
			
			/*
			echo "<pre>";
			print_r($result);
			echo "</pre>";
			*/
			$bad_json = '{ bar: "baz", }';
			json_decode($bad_json); // null
			
		}
		
		/**
		 * 
		 * This functions interpretes a result from a api and brings it in the needed form
		 * it HAS to be implemented in every data source class
		 * 
		 * @param unknown $result
		 */
		abstract function interprete_result($result);
		
		
		/**
		 * 
		 * display error message
		 * 
		 * @param string $error_text
		 */
		function error($error_text) {
			echo "<div class='error'>$error_text</div>";
		}
		

		/**
		 * shows the list of serach results to select one! 
		 * 
		 */
		function show_result() {
			echo "<div class='esa_item_list'>";
			foreach ($this->results as $result) {
				$result->html();
			}
			echo "</div>";
		}
		
		abstract function insert();
		
		/**
		 * fetches $data from url, unsing curl if possible, if not it uses file_get_contents
		 * 
		 * (curl version never tested :D )
		 */
		private function _fetch_external_data($url) {
			if(function_exists("curl_init") && function_exists("curl_setopt") && function_exists("curl_exec") && function_exists("curl_close") ) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "example.com");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$response = curl_exec($ch);
				curl_close($ch);
				return $response;
			}
		
			//if (init_set('allow_url_fopen')) {
			return file_get_contents($url); 
		}
	}
}
?>