<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Abstract Datasource Class
 * @link 		http://www.eagle-network.eu/stories/
 * @author 		Philipp Franck
 * 
 * 
 * Every datasource wich is connected to the Eagle Story Telling Application (such as europeana, iDai 
 * Gazetteer etc.) is an implementation of this abstract class.
 * 
 * 
 */



namespace esa_datasource {
	abstract class abstract_datasource {
		
		// infotext to this data source
		public $title; // title
		public $index = 3; // where to appear in the menu
		public $info; // infotext
		public $homeurl; // homepage of this datasource
		public $examplesearch; // placeholder for search field
		public $searchbuttonlabel = 'Search'; // label for searchbutton
		public $id_is_url = false; // is important to know about datasource
		
		public $debug = false;
		
		// array of esa_items containing the results of a performed search
		public $results = array();

		// saves current search params
		public $query;
		public $id;
		public $params = array();
		
		// pagination data
		public $pagination = true; //is pagination possible / supported in the serach results
		public $page = 1; //current page
		public $pages = false; // number of pages. false means: unknown
		
		// some classes, the user may add to the esa_item
		public $optional_classes = array(); //'test' => 'test'
		
		//error collector
		public $errors = array();
		
		// require additional classes -> array of files
		public $require = array();
		public $path;
		
		// list of regexes to realize regexes of this kind: fale | string | array of string
		public $url_parser = false;
		
		// some settings
		public $settings = array('epidoc' => array());
		public $force_curl = false;
		
		/**
		 * some initialation
		 */
		final function __construct() {
			// make plugin path available
			$this->path = __DIR__;
			
			// get some settings
			if (file_exists('esa_datasource.settings.local.php')) {
				$settings = array();
				$settings['epidoc'] = array('mode' => 'libxml');
			} else {
				include('esa_datasource.settings.local.php');
			}
			$this->settings = $settings;
			$this->settings['epidoc']['settings']['workingDir'] = $this->path . '/inc/epidocConverter';	
			
			// generate a generic info text
			if (!$this->info) {
				$this->info = "<p>Insert anything you want to search for <strong>or</strong> <a href='{$this->homeurl}' target='_blank'> search at the {$this->title} itself</a> and paste the URL of one record in the field below.<p>";
			}
			
			// require additional classes
			if (count($this->require)) {
				foreach($this->require as $require) {
					$this->_require($require);
				}
			}
			
			// call constructor
			$this->construct();
			
		}
		/**
		 * to be overwritten in implementation
		 */
		function construct() {
		}
		
		/**
		 * a generic search dialogue (can be overwitten) 	
		 * - needs $this->apiurl to be set
		 * - you can overwrite this whole function in the implementation or just the search_form_params part 
		 */
		function search_form() {
			
			echo $this->info;
			
			$query = (isset($_POST['esa_ds_query'])) ? $_POST['esa_ds_query'] : '';
			echo "<form method='post' id='esa_search_form'>";
			echo "<input type='text' name='esa_ds_query' placeholder='{$this->examplesearch}' value='{$query}'>";

			echo "<input type='hidden' name='esa_ds_page' value='1'>";
			
			echo $this->search_form_params($_POST);
			
			echo "<input type='submit' class='button button-primary' value='{$this->searchbuttonlabel}'>";
			echo "</form>";
		}
		
		/**
		 * to be overwritten by implementation if needed
		 * @return string
		 */
		function search_form_params($post) {
			return "";
		}
		
		
		/**
		 * 
		 * Search given Data Source for Query
		 * 
		 * This is a generic function, it can be overwritten in some implementations
		 * 
		 * 
		 * @return array of result, wich has to be parsed by $this->parse_result_set or false if error
		 */
		function search($query = null) {
			try {
				$query = (isset($_POST['esa_ds_query'])) ? $_POST['esa_ds_query'] : $query;
				
				if (!$query) {
					return;
				}
				
				// collect $_POST data
				$this->page  = (isset($_POST['esa_ds_page'])) ? $_POST['esa_ds_page'] : null;
				$this->pages  = (isset($_POST['esa_ds_pages'])) ? $_POST['esa_ds_pages'] : null;
				$navi  = (isset($_POST['esa_ds_navigation'])) ? $_POST['esa_ds_navigation'] : '';
				$this->query = $query;
				
				// additional $_POST data
				$params = array();
				foreach ($_POST as $k => $v) {
					if ($v and preg_match('#^esa_ds_param_(.*)#', $k, $real_k)) {
						$params[$real_k[1]] = $v;
					}
				}
				$this->params = array_merge($this->params, $params);
				
				// go

				// is url pasted?
				if ($url = $this->api_url_parser($query)) {
					//print_r('url: ' . $url);
					$this->results = array($this->parse_result($this->_generic_api_call($url)));
					
				} else {
					// perform search
					$fun = "api_search_url_$navi";
					
					if ($navi and method_exists($this, $fun)) {
						$queryurl = $this->$fun($query, $params);
					} else {
						$queryurl = $this->api_search_url($query, $params);
					}
					if ($this->debug) {
						echo $queryurl;
					}
					
					$this->parse_result_set($this->_generic_api_call($queryurl));
				
				}

				
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			
			return (!count($this->errors));
		}
		
		/**
		 * 
		 * get data from source for a specific unique identifier or URL
		 * 
		 * This is generic function, it can be overwritten in some implementations
		 * 
		 * @param $id - unique identifier
		 * 
		 * @return array of result, wich has to be parsed by $this->parse_result
		 */
		function get($id) {
			$this->id = (isset($_POST['esa_ds_id'])) ? $_POST['esa_ds_id'] : $id;
			return $this->parse_result($this->_generic_api_call($this->api_single_url($this->id)));
		}
		
		/**
		 * used for the generic get and search function only; 
		 * 
		 * @param string $api
		 * @param string $param
		 */
		function _generic_api_call($url) {
			
			if (!$url) {
				throw new \Exception('No Query: ' . $url);
			}
				
			$response = $this->_fetch_external_data($url);
			
			if ($this->debug) {
				echo "<pre>";
				echo "url: ", $url, "\nPOST: ", print_r($_POST,1 ), "\nResponse: ";
				print_r((array) json_decode($response));
				echo "</pre>";
			}
			
			return $response;
		}
		
		
		/**
		 * 
		 * This functions parses a result from a api and brings it in the needed form
		 * it HAS to be implemented in every data source class
		 * 
		 * @param unknown $result
		 * 
		 * @param implementation may use 2nd parameter $params = array()
		 */
		abstract function parse_result_set($result);
		
		abstract function parse_result($result);
		
		abstract function api_single_url($id, $params = array());
		
		abstract function api_search_url($query, $params = array());
		
		abstract function api_record_url($id, $params = array());
		
		
		/**
		 * 
		 * checks if the URL pasted comes from this sources and returns the url to this dataset
		 * 
		 * in implementation rewrite function or set up the 
		 * $url_parser array
		 * 
		 * 
		 * @param string $string
		 */
		function api_url_parser($string) {
			if (!$this->url_parser) {
				return $string;
			}
			if (!is_array($this->url_parser)) {
				$this->url_parser = array($this->url_parser);
			}
			foreach ($this->url_parser as $regex_id => $regex) {
				if (preg_match($regex, $string, $match)) {
					return $this->api_single_url(array_pop($match), array(
						'pasted_url' => $string,
						'regex_id'	=> $regex_id
					));
				}
			}
		}
		
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
			$this->show_pagination();
			
			echo "<div class='esa_item_list'>";
			foreach ($this->results as $result) {
				if (is_object($result)) {
					$result->html();
				}
			}
			echo "</div><div style='clear:both'></div>";
		}
		
		/**
		 * shows the pagination control of the results (next page etc.), when
		 * $this->pagination contains data
		 * it is a task of the specific implementation of this class, to fill the array,
		 * because how pagination works strongly differs from datasource to datasource  
		 * 
		 * 
		 */
		function show_pagination() {
			if ($this->pagination and ($this->pages > 1 or $this->pages == '?')) {
				
				echo "<div class='esa_item_list_pagination'>";
				
				if (method_exists($this, "api_search_url_first")) {
					$this->show_pagination_button('first');
				}
				
				if (method_exists($this, "api_search_url_prev") and ($this->page > 1)) {
					$this->show_pagination_button('prev');
				}

				echo "<div class='esa_item_list_pagination_current'>";
				
				if ($this->page) {
					echo "Page " . $this->page;
				}
								
				if ($this->pages and $this->pages != '?') {
					echo ($this->page) ? ' of ' : 'Pages: '; 
					echo $this->pages;
				}
				
				echo "</div>";
				
				if (method_exists($this, "api_search_url_next") and ($this->page < $this->pages or $this->pages == '?')) {
					$this->show_pagination_button('next');
				}
				
				if (method_exists($this, "api_search_url_last") and $this->pages) {
					$this->show_pagination_button('last');
				}
				
				echo "</div>";
			}
		}
		
		function show_pagination_button($type) {
			
			$labels = array(
					'prev' => "Previous",
					'next' => "Next",
					'first' => "First",
					'last' => "Last"
			);
			
		
			echo "<form method='post' class='esa_item_list_pagination_button'>";
			echo "<input type='hidden' name='esa_ds_query' value='{$this->query}'>";
			echo "<input type='hidden' name='esa_ds_page' value='{$this->page}'>";
			echo "<input type='hidden' name='esa_ds_pages' value='{$this->pages}'>";
			echo "<input type='hidden' name='esa_ds_navigation' value='$type'>";
			foreach ($this->params as $k => $v) {
				echo "<input type='hidden' name='esa_ds_param_$k' value='$v'>";
			}
			echo "<input type='submit' class='button button-secondary' value='{$labels[$type]}'>";
			echo "</form>";
		}
		
		/**
		 * transforms a array to a esa_item html
		 */
		function render_item($item) {
			return $item->render();
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
		 * if the functionality of the datasource relies onto something special like specific php libraries or external software,
		 * you can implement a dependancy check on wose result the availabilty in wordpress depends.
		 * @return string
		 * @throws Exception if not
		 */
		function dependency_check() {
			return 'O. K.';
		}
		
		/**
		 * get datasource specif styles
		 * @return array(
		 * 'name' =>  name,
		 * 'css' => css sontent AND/OR 
		 * 'file' => file to link)
		 */
		function stylesheet() {
			return array();
		}
		
		
		
		/**
		 * fetches $data from url, unsing curl if possible, if not it uses file_get_contents
		 * 
		 * (curl version never tested :D )
		 */
		protected function _fetch_external_data($url) {
			if (!$url) {
				throw new \Exception('no $url!');
			}
				
			if(
				function_exists("curl_init") and 
				function_exists("curl_setopt") and
				function_exists("curl_exec") and 
				function_exists("curl_close") and
				$this->force_curl
			){
				$ch = curl_init();
				
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_URL, $url);
				$response = curl_exec($ch);
				
				if ($this->debug) {
					echo "<pre>mode: curl</pre>";
				}
				
				if(!curl_errno($ch)) {
					$info = curl_getinfo($ch);
					if ($this->debug) {
						echo '<pre>Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'] . '</pre>';
					}
				} else {
					throw new \Exception('Curl error: ' . curl_error($ch));
				}
				
				curl_close($ch);

				return $response;
			}
		
			
			if (!$json = file_get_contents($url)) {
				throw new \Exception("no response to $url!");
			}
			
			return $json;
		}
		
		protected function _ckeck_url($url) {
			return (!filter_var($url, FILTER_VALIDATE_URL) === false);
		}
		
		
		/**
		 * json decode with error handling
		 */
		protected function _json_decode($json) {

			//echo "input json string<br><pre>$json</pre><hr>";

			
			$dec = json_decode($json);
			
			switch (json_last_error()) {
				case JSON_ERROR_NONE:
					break;
				case JSON_ERROR_DEPTH:
					$this->error('json error: - Maximum stack depth exceeded');
					break;
				case JSON_ERROR_STATE_MISMATCH:
					$this->error('json error: - Underflow or the modes mismatch');
					break;
				case JSON_ERROR_CTRL_CHAR:
					$this->error('json error: - Unexpected control character found');
					break;
				case JSON_ERROR_SYNTAX:
					$this->error('json error: - Syntax error, malformed JSON');
					break;
				case JSON_ERROR_UTF8:
					$this->error('json error: - Malformed UTF-8 characters, possibly incorrectly encoded');
					break;
				default:
					$this->error('json error: - Unknown error');
					break;
			}
			
			//echo "<br><hr>output json<br><pre>", print_r($dec, 1), "</pre>";
			
			
			return $dec;
		}
		
		/**
		 * require a file
		 * @param $require path or filename of file in plugin base dir
		 */
		protected function _require($require) {
			require_once(__DIR__ . '/' . $require);
		}
		
		
		function get_by_url($query) {
			if ($url = $this->api_url_parser($query)) {
				return $this->parse_result($this->_generic_api_call($url));
			}
		}
		
		
		function get_source_name() {
			return array_pop(explode('\\', get_class($this)));
		}
		

		
	}
}
	





?>