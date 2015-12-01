<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: Wikipedia
 * @link 		http://www.eagle-network.eu/stories/
 * @author 		Philipp Franck
 * 
 * 
 * 
 *  
 * 
 */
namespace esa_datasource {
	class wiki extends abstract_datasource {
		
		public $title = 'Wikipedia';
		
		public $info = "<p>Use the field above to search for articles in the <a href='https://en.wikipedia.org/' target='_blank'>Wikipedia</a> or insert a link to page from any wikipedia.</p>";
	
		public $pagination = true; // are results paginated?
		private $_hits_per_page = 9;
		
		public $debug = false;
		
		public $params = array(
			'lang'  => 'en'
		);
				
		//public $apiurl = "https://en.wikipedia.org/w/api.php?action=query&format=json&list=allimages&titles=%s";
		function api_search_url($query) {
			$query = urlencode($query);
			$offset = $this->_hits_per_page * ($this->page - 1);
			return "https://{$this->params['lang']}.wikipedia.org/w/api.php?action=query&prop=extracts|pageimages|info&format=json&inprop=url&exintro=&exsectionformat=plain&piprop=thumbnail|name|original&pithumbsize=150&generator=search&redirects=&gsrsearch=$query&gsrwhat=text&exlimit=max&pilimit=max&gsrlimit={$this->_hits_per_page}&gsroffset={$offset}";
		}                                                                          
		
		function api_single_url($id) {
			$id = $this->real_id($id);
			$id = urlencode($id);
			return "https://{$this->params['lang']}.wikipedia.org/w/api.php?action=query&prop=extracts|pageimages|info&format=json&inprop=url&exintro=&exsectionformat=plain&piprop=thumbnail|name|original&pithumbsize=150&redirects=&titles=$id";

		}

		function api_record_url($id) {
			$id = $this->real_id($id);
			return "https://{$this->params['lang']}.wikipedia.org/?curid=$id";
		}
		
		function api_url_parser($string) {
			if (preg_match('#https?\:\/\/(..)\.wikipedia\.org\/wiki\/(.*)#', $string, $match)) {
				$this->params['lang'] = $match[1];
				return $this->api_single_url($match[2]);
			}
		}
		
		//	pagination functions
		function api_search_url_next($query, $params = array()) {
			$this->page += 1;
			return $this->api_search_url($query, $params);
		}
			
		function api_search_url_prev($query, $params = array()) {
			$this->page -= 1;
			return $this->api_search_url($query, $params);
		}
			
		function api_search_url_first($query, $params = array()) {
			$this->page = 1;
			return $this->api_search_url($query, $params);
		}
		/*	
		function api_search_url_last($query, $params = array()) {
			$this->page = $this->pages;
			return $this->api_search_url($query, $params);
		}
		*/
		
		
		
		
		
		// ids look like that: wikiID@wikiLANG  -> this function strips them
		function real_id($id) {
			$ex = explode('@', $id);
			$this->params['lang'] = (isset($ex[1])) ? $ex[1] : $this->params['lang'];
			return $ex[0];
		}
		

		
		function parse_result_set($response, $subquery = false) {

			$this->results = array();
			
			$response = json_decode($response);
			
			if ($this->debug) {echo "<br><textarea>", print_r($response), "</textarea>";}
			
			foreach ($response->query->pages as $id => $page) {					
				$this->results[] = $this->render_page($page);
			}
			
			// workaround because media wiki api is not respoding total amount of pages
			
			if (count($this->results) >= $this->_hits_per_page) {
				$this->pages = '?';
			} else {
				$this->pages = $this->page;
			}
			
			return $this->results;
		}


		function render_page($page) {
			$data = new \esa_item\data();
			
			// title
			$data->title = $page->title;
			
			// media
			if ($page->thumbnail) {
				$data->addImages(array(
					'type' 	=>	'BITMAP',
					'url'	=>	$page->thumbnail->source,
					'fullres'=> $page->thumbnail->original
				));
			}
			
			// extract
			$data->addTable('', $page->extract . "<br><a href='{$page->fullurl}' target='_blank'>Read Full Article</a>");

			//id
			$id = $page->title . '@' . $this->params['lang'];

			return new \esa_item('wiki', $id, $data->render(), $page->fullurl);
		}
		
		function parse_result($response) {
			$response = json_decode($response);
			
			if ($this->debug) {echo "<br><textarea>", print_r($response), "</textarea>";}
			
			foreach ($response->query->pages as $page) {
				return $this->render_page($page);
			}
					
		}
			
		
	}
}
?>