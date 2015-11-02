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
		public $info; // infotext
		public $homeurl; // homepage of this datasource
		public $examplesearch; // placeholder for search field
		public $searchbuttonlabel = 'Search'; // label for searchbutton
		
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
		
		/**
		 * some initialation
		 */
		final function __construct() {
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
			
			//make plugin path available
			$this->path = __DIR__;
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
		 * Serach given Data Source for Query
		 * 
		 * This is a generic function, it can be overwritten in some implementations
		 * 
		 * 
		 * @return array of result, wich has to be parsed by $this->parse_result_set or false if error
		 */
		function search() {
			try {
				$query = (isset($_POST['esa_ds_query'])) ? $_POST['esa_ds_query'] : null;
				
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
					//print_r($url);
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
		private function _generic_api_call($url) {
				
			if (!$url) {
				throw new \Exception('No Query');
			}
				
			$response = $this->_fetch_external_data($url);
			
			if (ESA_DEBUG) {
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
		
		abstract function api_single_url($id);
		
		abstract function api_search_url($query);
		
		abstract function api_record_url($id);
		
		abstract function api_url_parser($id);
		
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
			if ($this->pagination and $this->pages > 1) {
				
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
								
				if ($this->pages) {
					echo ($this->page) ? ' of ' : 'Pages: '; 
					echo $this->pages;
				}
				
				echo "</div>";
				
				if (method_exists($this, "api_search_url_next") and ($this->page < $this->pages)) {
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
		function render_item($data = array()) {
			
			if (count($data['images']) || count($data['text'])) {
				$html  = "<div class='esa_item_left_column_max_left'>";
				
				if (count($data['text'])) {
					foreach ($data['text'] as $type => $text) {
						if ($text) {
							$html .= "<div class='esa_item_text {$type}'>$text</div>";
						}
					}
				}
				
				if (count($data['images'])) {
					foreach($data['images'] as $image)  {
						$html .= "<div class='esa_item_main_image' style='background-image:url(\"{$image->url}\")' title='{$image->title}'>&nbsp;</div>";
						$html .= "<div class='esa_item_subtext'>{$image->text}</div>";
					}
				}
				$html .= "</div>";
				$html .= "<div class='esa_item_right_column_max_left'>";
			} else {
				$html = "<div class='esa_item_single_column'>";
			}
			
			
			$html .= "<h4>{$data['title']}</h4><br>";
			

			
			if (count($data['table'])) {
			$html .= "<ul class='datatable'>";
				foreach ($data['table'] as $field => $value) {
					$value = trim($value);
					if ($value) {
						$label = $this->_label($field);
						$html .= "<li><strong>{$label}: </strong>{$value}</li>";
						//$html .='<textarea>' . print_r($value,1) . "</textarea>";
					}
				}
				$html .= "</ul>";
			}
				
			$html .= "</div>";
			
			return $html;
		}
		
		
		private function _label($of) {
			$labels = array(
					'objectType' => 'Type',
					'repositoryname' => 'Repository',
					'material' => 'Material',
					'tmid' => 'Trismegistos-Id',
					'artifactType' => 'Artifact Type',
					'objectType2' => 'Type',
					'transcription' => 'Transcription',
					'provider' => 'Content Provider',
					'ancientFindSpot' => 'Ancient find spot',
					'modernFindSpot' =>  'Modern find spot',
					'origDate' => 'Date'
			);
				
			return (isset($labels[$of])) ? $labels[$of] : $of;
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
		 * fetches $data from url, unsing curl if possible, if not it uses file_get_contents
		 * 
		 * (curl version never tested :D )
		 */
		protected function _fetch_external_data($url) {
			if (!$url) {
				throw new \Exception('no $url!');
			}
				
			/*
			if(ESA_CURL & function_exists("curl_init") && function_exists("curl_setopt") && function_exists("curl_exec") && function_exists("curl_close") ) {
				$ch = curl_init();
				
				$http_headers = array(
					"Accept: application/json",
					"Connection: close",                    // Disable Keep-Alive
					"Expect:",                              // Disable "100 Continue" server response
					"Content-Type: application/json"        // Content Type json
				);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FAILONERROR, true);
				curl_setopt($ch, CURLOPT_VERBOSE, true);               // Verbose mode for diagnostics
				curl_setopt($ch, CURLOPT_POST, true);  
				curl_setopt($ch, CURLOPT_URL, $url);
				$response = curl_exec($ch);
				
				
				if (ESA_DEBUG) {
					echo "<pre class='esa_debug'>";
					echo "url: ", $url, "\nPOST: ", print_r($_POST,1 ), "\nResponse: ";
					print_r((array) json_decode($response));
					echo "\nerror:" . curl_error($ch);
					echo "</pre>";
				}
				
				curl_close($ch);
				return $response;
			}
		

			*/
			//echo $url;
			
			if (!$json = file_get_contents($url)) {
				$this->error('some error');
				throw new \Exception('no response!');
				
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
		
		protected function _require($require) {
			require_once(__DIR__ . '/' . $require);
		}
		
	}
}
?>