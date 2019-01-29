<?php
/**
 * @package 	enhanced-storytelling
 * @subpackage	Search in Datasources | Subplugin: ancient_eu
 * @link 		http://www.ancient.eu/
 * @author 		Philipp Franck
 *
 * Status: 1.1
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
		
		public $url_parser = array(
				// 'entry' 	=>	'#https?\:\/\/(www\.)ancient\.eu\/(\w*)\/?$#', //type 1  // does not work because no id included!
				'article'	=>	'#https?\:\/\/(www\.)ancient\.eu\/article\/(\w*)\/?#',  // type 2
				'image'		=>	'#https?\:\/\/(www\.)ancient\.eu\/image\/(\w*)\/?#', // type 3
				'blog'		=>	'#https?\:\/\/(www\.)ancient\.eu\/blog\/(\w*)\/?#' // type 4
				//'blog2'		=>	'#https?\:\/\/etc\.ancient\.eu\/\d*\/\d*\/\d*\/([\w-]*)\/?$#', // does not work because no id included!
		);				

		public 	$types = array(
			'',
			'Encyclopedia Entry',
			'Article',
			'Image',
			'',
			'Blog Entry',
			'Video',
			'Link',
			'Book Review'
		);
		
		function api_search_url($query, $params = array()) : string {
			$query = str_replace(' ', ' AND ', $query);
			//$query = str_replace(' ', '+AND+', $query);
			$query = str_replace(':', '\:', $query);
			$query = rawurlencode($query);
			return "http://www.ancient.eu/api/search.php?query=$query&limit=12"  . $this->_api_params_url_part($params);
		}
			
		function api_single_url($id, $params = array()) : string {
			if (isset($params['pasted_url'])) {
				switch ($params['regex_id']) {
					case 'entry': 
						$id = "1-$id";
					break;
					case 'article': 
						$id = "2-$id";
					break;
					case 'image': 
						$id = "3-$id";
					break;
					case 'blog':
						$id = "5-$id";
					break;
				}
			}
			
			return "http://www.ancient.eu/api/search.php?query=id:$id";
		}
		
		function api_record_url($id, $params = array()) : string {
			$x = explode('-', $id);
			$x = array_pop($x);
			return "http://www.ancient.eu/article/$x";
		}
			

		/*	pagination functions  */
		function api_search_url_next($query, $params = array()) {
			$this->page += 1;
			return $this->api_search_url($query) . '&page=' . $this->page  . $this->_api_params_url_part($params);
		}
			
		function api_search_url_prev($query, $params = array()) {
			$this->page -= 1;
			return $this->api_search_url($query) . '&page=' . $this->page  . $this->_api_params_url_part($params);
		}
			
		function api_search_url_first($query, $params = array()) {
			$this->page = 1;
			return $this->api_search_url($query) . '&page=' . $this->page  . $this->_api_params_url_part($params);
		}
			
		function api_search_url_last($query, $params = array()) {
			$this->page = $this->pages;
			return $this->api_search_url($query) . '&page=' . $this->page  . $this->_api_params_url_part($params);
		}
		
		/*
		 * 
		 * search form
		 */
		function search_form_params($post) {
			$echo = "<select name='esa_ds_param_type' height='1'>";
			foreach ($this->types as $typeId =>  $type) {
				if ($type == '') {
					continue;
				}
				$echo .= "<option value='$typeId' " . (isset($post['esa_ds_param_type']) && ($typeId == $post['esa_ds_param_type']) ? 'selected ' : '') . '>' .  $type . "</option>";
			}
			$echo .= "<option value='' " . (isset($post['esa_ds_param_type']) ? 'selected ' : '') . ">All</option>";
			$echo .= "</select>";
			return $echo;
		}
		
		private function _api_params_url_part($params) {
			$return = '';
			if (isset($params['type'])) {
				$return .= "&type={$params['type']}";
			}
			return $return;
		}
		
		
		/*
		 * The result rendering
		 * 
		 */
		function parse_result_set($response) : array {


			
			$response = json_decode($response);
			$this->results = array();
			foreach ($response->documents as $doc) {		
				
				$data = new \esa_item\data();
				
				$url = $this->_url($doc->url);
				
				$data->title = $doc->title;
				$data->addTable('', str_replace("\n", '<br>', $doc->description) . "<br><br><a href='{$url}' target='_blank'>Read Full Article</a>");
				$tags = is_array($doc->tags) ? implode(', ', $doc->tags) : $doc->tags;
				$data->addTable('Keywords', str_replace('_', ' ', $tags));
				$data->addTable('Type', $this->types[$doc->type]);

				if ($doc->thumbnail and $doc->image) {
					$data->addImages(array(
						'url' 		=> $this->_url($doc->thumbnail),
						'fullres' 	=> $this->_url($doc->image),
						'type' 		=> 'BITMAP',
						'title' 	=> $doc->title
					));
				}
				$this->results[] = new \esa_item('ancient_eu', $doc->id, $data->render(), $url, $data->title);
			}
						
			// pagination
			$this->pages = 1 + (int) ($response->nb_results / 12);
			
			return $this->results;
		}

		function parse_result($response) : \esa_item {
			// if always return a whole set
			$res = $this->parse_result_set($response);
			return $res[0];
		}

		
		private function _url($url) {
			return (substr($url, 0, 1) == '/') ? 'http://www.ancient.eu' . $url : $url;
		} 

	}
}
?>