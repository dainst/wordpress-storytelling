<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: ancient_eu
 * @link 		http://www.ancient.eu/
 * @author 		Philipp Franck
 *
 * Status: Alpha 1
 *
 */


namespace esa_datasource {
	class ancient_eu extends abstract_datasource {

		public $title = 'Ancient History Encyclopedia'; // Label / Title of the Datasource
		public $index = 130; // where to appear in the menu
		public $info = false; // get created automatically, or enter text
		public $homeurl; // link to the dataset's homepage
		public $debug = false;
		//public $examplesearch; // placeholder for search field
		//public $searchbuttonlabel = 'Search'; // label for searchbutton
		
		public $pagination = true; // are results paginated?
		public $optional_classes = array(); // some classes, the user may add to the esa_item

		public $require = array();  // require additional classes -> array of fileanmes	
		
		public $url_parser = '#https?\:\/\/(www\.)some_page.de?ID=(.*)#'; // // url regex (or array)
		
		public $force_curl = false;
		
		/**
		 * constructor
		 * @see \esa_datasource\abstract_datasource::construct()
		 */
		function construct() {
				
		}
		
		function api_search_url($query, $params = array()) {
			$query = str_replace(' ', ' AND ', $query);
			//$query = str_replace(' ', '+AND+', $query);
			$query = str_replace(':', '\:', $query);
			$query = rawurlencode($query);
			return "http://www.ancient.eu/api/search.php?query=$query&limit=12";
		}
			
		function api_single_url($id, $params = array()) {
			return "http://www.ancient.eu/api/search.php?query=id:$id";
		}


		
		function api_record_url($id, $params = array()) {
			$x = explode('-', $id);
			$x = array_pop($x);
			return "http://www.ancient.eu/article/$x";
		}
			

		/*	pagination functions  */
		function api_search_url_next($query, $params = array()) {
			$this->page += 1;
			return $this->api_search_url($query) . '&page=' . $this->page;
		}
			
		function api_search_url_prev($query, $params = array()) {
			$this->page -= 1;
			return $this->api_search_url($query) . '&page=' . $this->page;
		}
			
		function api_search_url_first($query, $params = array()) {
			$this->page = 1;
			return $this->api_search_url($query) . '&page=' . $this->page;
		}
			
		function api_search_url_last($query, $params = array()) {
			$this->page = $this->pages;
			return $this->api_search_url($query) . '&page=' . $this->page;
		}
			
		function parse_result_set($response) {
			
			$types = array(
				'Encyclopedia Entry',
				'Article',
				'Image',
				'',
				'Blog Entry',
				'Video',
				'Link',
				'Review'
			);
			
			$response = json_decode($response);
			$this->results = array();
			foreach ($response->documents as $doc) {		
				
				$data = new \esa_item\data();
				
				$url = $this->_url($doc->url);
				
				$data->title = $doc->title;
				$data->addTable('', str_replace("\n", '<br>', $doc->description) . "<br><br><a href='{$url}' target='_blank'>Read Full Article</a>");
				$data->addTable('Keywords', str_replace('_', ' ', implode(', ',$doc->tags)));
				$data->addTable('Type', $types[$doc->type -1]);

				if ($doc->thumbnail and $doc->image) {
					$data->addImages(array(
						'url' 		=> $this->_url($doc->thumbnail),
						'fullres' 	=> $this->_url($doc->image),
						'type' 		=> 'BITMAP',
						'title' 	=> $doc->title
					));
				}
				$this->results[] = new \esa_item('ancient_eu', $doc->id, $data->render(), $url);
			}
						
			// pagination
			$this->pages = 1 + (int) ($response->nb_results / 12);
			
			return $this->results;
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
		
		private function _url($url) {
			return (substr($url, 0, 1) == '/') ? 'http://www.ancient.eu' . $url : $url;
		} 

	}
}
?>