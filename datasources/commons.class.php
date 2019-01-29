<?php
/**
 * @package 	Wikimedia Commons
 * @subpackage	Search in Datasources | Subplugin: Wikimedia Commons
 * @link 		
 * @author 		Philipp Franck
 *
 * Status: Beta
 *
 */


namespace esa_datasource {
	class commons extends abstract_datasource {

		public $title = 'Wikimedia Commons'; // Label / Title of the Datasource
		public $index = 70; // where to appear in the menu
		public $info = false; // get created automatically, or enter text
		public $homeurl = 'https://commons.wikimedia.org'; // link to the dataset's homepage
		public $debug = false;
		public $examplesearch = 'a search term like "res gestae" or an Wikimedia Commons url like "https://upload.wikimedia.org/wikipedia/commons/7/77/Res_Gestae_Divi_Augusti.jpg"'; // placeholder for search field
		//public $searchbuttonlabel = 'Search'; // label for searchbutton
		
		public $pagination = true; // are results paginated?
		private $_hits_per_page = 9;

		
		function api_search_url($query, $params = array()) : string {
			$offset = $this->_hits_per_page * ($this->page - 1);
			$query = urlencode($query);
			return "https://commons.wikimedia.org/w/api.php?action=query&prop=imageinfo&format=json&iiprop=url|size|mime|mediatype|extmetadata&iiurlwidth=150&generator=search&gsrsearch=$query&gsrnamespace=6&gsrlimit={$this->_hits_per_page}&gsroffset={$offset}";
		}
			
		function api_single_url($id, $params = array()) : string {
			return "https://commons.wikimedia.org/w/api.php?action=query&prop=imageinfo&format=json&iiprop=url|size|mediatype|extmetadata|mime&iiurlwidth=150&pageids=$id";
		}
		
		function api_record_url($id, $params = array()) : string {
			return  "https://commons.wikimedia.org/wiki/?curid=$id";
		}
			
		public $url_parser = '#https?\:\/\/commons.wikimedia.org\/wiki\/(.*\#\/media\/)?(File\:.*)#';
		function api_url_parser($string) {
			if (preg_match(urldecode($this->url_parser), $string, $match)) {
				
				$title = urlencode($match[2]);
				$url = "https://commons.wikimedia.org/w/api.php?action=query&prop=imageinfo&format=json&iiprop=url|size|mediatype|extmetadata|mime&iiurlwidth=150&titles=$title";
				
				if ($this->debug) {
					echo "<br><textarea>", print_r($url), "</textarea>";
				}
				
				return $url;
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
		function parse_result_set($response) : array {

			$response = json_decode($response);
			$this->results = array();
			if ($this->debug) {echo "<br><textarea>", print_r($response), "</textarea>";}
			if (count((array) $response->query->pages)) {
				foreach ($response->query->pages as $pageId => $page) {
					$data = $this->fetch_information($page);
					$this->results[] = new \esa_item(
					    'commons',
                        $pageId,
                        $data->render(),
                        $data->title,
                        $page->imageinfo[0]->descriptionurl,
                        array(),
                        array(),
                        $data->latitude,
                        $data->longitude
                    );
				}				
			}

			// workaround because media wiki api is not responding total amount of pages

			if (count($this->results) >= $this->_hits_per_page) {
				$this->pages = '?';
			} else {
				$this->pages = $this->page;
			}
			
			return $this->results;
		}

		
		function fetch_information($page) {
			$data = new \esa_item\data();
			
			// title
			preg_match('#File\:(.*)\..*#', $page->title, $matches);
			$data->title = $matches[1];
			
			// media
			if ($page->imageinfo[0]->mediatype == 'BITMAP') {
				$data->addImages(array(
					'type' 	=>	$page->imageinfo[0]->mediatype,
					'url'	=>	$page->imageinfo[0]->thumburl,
					'fullres'=> $page->imageinfo[0]->url,
					'mime'	=>	$page->imageinfo[0]->mime,
					'text'	=>	isset($page->imageinfo[0]->extmetadata->ImageDescription)
                        ? strip_tags($page->imageinfo[0]->extmetadata->ImageDescription->value)
                        : ""
				));
			} elseif ($page->imageinfo[0]->mediatype == 'OFFICE') {
				$data->addImages(array(
					'type' 	=>	'DOWNLOAD',
					'title'	=>	'Download file: ' . $page->imageinfo[0]->url,
					'url'	=>	$page->imageinfo[0]->thumburl,
					'fullres'=> $page->imageinfo[0]->url,
					'mime'	=>	$page->imageinfo[0]->mime,
					'text'	=>	isset($page->imageinfo[0]->extmetadata->ImageDescription)
                        ? strip_tags($page->imageinfo[0]->extmetadata->ImageDescription->value)
                        : ""
				));
			} else {
				$data->addImages(array(
					'type' 	=>	$page->imageinfo[0]->mediatype,
					'url'	=>	$page->imageinfo[0]->url,
					'mime'	=>	$page->imageinfo[0]->mime,
					'text'	=>	isset($page->imageinfo[0]->extmetadata->ImageDescription)
                        ? strip_tags($page->imageinfo[0]->extmetadata->ImageDescription->value)
                        : ""
				));
			}
			// coordinates
			$data->latitude = null;
			$data->longitude = null;
			if (isset($page->imageinfo[0]->extmetadata->GPSLatitude) and 
				isset($page->imageinfo[0]->extmetadata->GPSLongitude) and 
				$page->imageinfo[0]->extmetadata->GPSMapDatum->value == 'WGS-84') { //because we cannot recalculate other coordinate systems
					
				$data->latitude = (float) $page->imageinfo[0]->extmetadata->GPSLatitude->value;
				$data->longitude = (float) $page->imageinfo[0]->extmetadata->GPSLongitude->value;
				$data->addTable('Position', $data->latitude . ' | ' . $data->longitude);
			}

			
			// rest
			foreach ($page->imageinfo[0]->extmetadata as $meta => $val) {
				if (in_array($meta, array('ImageDescription', 'DateTime', 'Artist'))) {
					$data->addTable($meta, $val->value);
				}
			}

			// licence
			if (isset($page->imageinfo[0]->extmetadata->LicenseUrl)) {
                $data->addTable('Licence', "<a href='{$page->imageinfo[0]->extmetadata->LicenseUrl->value}' target='_blank'>{$page->imageinfo[0]->extmetadata->LicenseShortName->value}</a>");
            }

			return $data;			
		}
		
		
		function parse_result($response) : \esa_item {
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

	}
}
?>