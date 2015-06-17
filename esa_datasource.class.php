<?php
namespace esa_datasource {
	abstract class abstract_datasource {
	
		// url of the api, the source uses (important, if default search dialogue nad/or search functions are used)
		public $api_search_url;
		public $api_single_url;
		
		// infotext to this data source
		public $title;
		public $info; 
		
		// array of esa_items containing the results of a performed search
		public $results = array();

		//error collector
		public $errors = array();
		
		/**
		 * a generic search dialogue (can be overwitten) 	
		 * - needs $this->apiurl to be set	
		 */
		function search_form() {
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
		 * 
		 * Serach given Data Source for Query
		 * 
		 * This is a generic function, it can be overwritten in some implementations
		 * - based on $this->api_search_url 
		 * - %s in apiurl becomes replaced by search string
		 * - trys to use $_POST['esa_ds_query'] as search string, when $query is not given
		 * 
		 * @param string $query
		 * 
		 * @return array of result, wich has to be parsed by $this->parse_result_set or false if error
		 */
		function search($query = null) {
			$query = (isset($_POST['esa_ds_query'])) ? $_POST['esa_ds_query'] : $query;

			try {
				$response = $this->parse_result_set($this->_generic_api_call($this->api_search_url, $this->api_encode_fn($query, false)));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			return (!count($this->errors)) ?  : false;
		}
		
		/**
		 * 
		 * get data from source for a specific unique identifier
		 * 
		 * This is generic function, it can be overwritten in some implementations
		 * 
		 * @param $id - unique identifier
		 * 
		 * @return array of result, wich has to be parsed by $this->parse_result
		 */
		function get($id) {
			$id = (isset($_POST['esa_ds_id'])) ? $_POST['esa_ds_id'] : $id;
			return $this->parse_result($this->_generic_api_call($this->api_single_url, $this->api_encode_fn($id, true)));
		}
		
		/**
		 * used for the generic get and serach function only; 
		 * 
		 * @param string $api
		 * @param string $param
		 */
		private function _generic_api_call($api, $param) {
				
			if (!$param) {
				throw new \Exception('No Query');
			}
			
				
			$url = sprintf($api, $param);
		
				
			$response = $this->_fetch_external_data($url);
			
			//*/
			
			echo "<pre class='esa_debug'>";
			echo $url;
			print_r((array) json_decode($response));
			echo "</pre>";
			//*/
			
			return $response;
		}
		
		/**
		 * to use with api_search_url and api_single_url
		 * overwrite in implementation with the encoding you need (warurlencode, urlencode etc.) 
		 * 
		 * the genereic version does exactly nothing.
		 * 
		 * @param string $string
		 * @param boolean $is_single -> single api call or serach query
		 * @return string
		 */
		function api_encode_fn($string, $is_single = false) {
			return $string;
		}
		
		/**
		 * 
		 * This functions parses a result from a api and brings it in the needed form
		 * it HAS to be implemented in every data source class
		 * 
		 * @param unknown $result
		 */
		abstract function parse_result_set($result);
		
		abstract function parse_result($result);
		
		
		/**
		 * 
		 * display error message
		 * 
		 * @param string $error_text
		 */
		protected function error($error_text) {
			$this->errors[] = $error_text;
		}
		

		/**
		 * shows the list of search results to select one! 
		 * 
		 */
		function show_result() {
			echo "<div class='esa_item_list'>";
			foreach ($this->results as $result) {
				$result->html();
			}
			echo "</div><div style='clear:both'></div>";
		}
		
		/**
		 * shows the list of errors
		 *
		 */
		function show_errors() {
			echo "<div class='esa_error_list'>";
			foreach ($this->errors as $error) {
				echo "<div class='error'>$error</div>";
			}
			echo "</div>";
		}
		
		/**
		 * fetches $data from url, unsing curl if possible, if not it uses file_get_contents
		 * 
		 * (curl version never tested :D )
		 */
		protected function _fetch_external_data($url) {
			if(function_exists("curl_init") && function_exists("curl_setopt") && function_exists("curl_exec") && function_exists("curl_close") ) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "example.com");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$response = curl_exec($ch);
				curl_close($ch);
				return $response;
			}
		
			//if (init_set('allow_url_fopen')) {
			
			if (!$url) {
				throw new \Exception('no $url!');
			}
			
			return file_get_contents($url); 
		}
	}
}
?>