<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: finds.org.uk
 * @link 		https://finds.org.uk/
 * @author 		Philipp Franck
 *
 * Status: Beta
 *
 */


namespace esa_datasource {
	
	class finds_org extends abstract_datasource {

		public $title = 'Finds.org'; // Label / Title of the Datasource
		public $index = 110; // where to appear in the menu
		public $info = false; // get created automatically, or enter text
		public $homeurl = 'https://finds.org.uk/database/search'; // link to the dataset's homepage
		public $debug = false;
		public $examplesearch = 'Bronze Age hoard found in the Forest of Dean'; // placeholder for search field

		public $pagination = true; // are results paginated?

		public $url_parser = '#https?\:\/\/(www\.)?finds\.org\.uk\/database\/artefacts\/record\/id\/(.*)#'; // url regex (or array)
		
		public $force_curl = true;
		
		
		function api_search_url($query, $params = array()) {
			$query = rawurlencode($query);
			return "https://finds.org.uk/database/search/results/q/$query/format/json";
		}

		function api_single_url($id, $params = array()) {
			return "https://finds.org.uk/database/search/results/q?id=570344&format=json";
			/*
			 * Why not going directly to the artifact like below page but using the serach?
			 * Because the artifact page
			 * https://finds.org.uk/database/artefacts/record/id/767518
			 * sometimes misses some fields, like 3D.
			 */ 
		}

		function api_record_url($id, $params = array()) {
			return "https://finds.org.uk/database/artefacts/record/id/$id";
		}
			
		

		function api_search_url_next($query, $params = array()) {
			$p = $this->page + 1;
			return $this->api_search_url($query, $params) . '/page/' . $p;
		}
			
		function api_search_url_prev($query, $params = array()) {
			$p = $this->page - 1;
			return $this->api_search_url($query, $params) . '/page/' . $p;
		}
			
		function api_search_url_first($query, $params = array()) {
			$p = $this->page + 1;
			return $this->api_search_url($query, $params) . '/page/1';
		}
			
		function api_search_url_last($query, $params = array()) {
			$p = $this->page + 1;
			return $this->api_search_url($query, $params) . '/page/' . $this->pages;
		}
		
		
		private function _item2esa($item) {
			$data = new \esa_item\data();
			
			$data->title = $item->broadperiod . ' ' . $item->objecttype;
			
			$data->addTable('Found',
                $this->_list(
                    isset($item->parish) ? $item->parish : "",
                    isset($item->district) ? $item->district: "",
                    isset($item->county) ? $item->county : "",
                    isset($item->region) ? $item->region : ""));
			$data->addTable('Time', ucfirst(strtolower($item->broadperiod)) .
                ' (' . (isset($item->dateFromQualifier) ? $item->dateFromQualifier : "") .
                ' ' . $item->fromdate .
                ' to ' . (isset($item->dateToQualifier) ? $item->dateToQualifier : "") .
                ' ' . $item->todate . ' )');
			$data->addTable('description', nl2br($item->description));
			if (isset($item->notes)) {
                $data->addTable('notes', nl2br($item->notes));
            }
			if (isset($item->rulerName)){
                $data->addTable('Ruler', $item->rulerName);
            }
			if (isset($item->moneyerName)) {
                $data->addTable('Moneyer Name', $item->moneyerName);
            }
				
			if (!empty($item->obverseLegend) and (!in_array($item->obverseLegend, array('[]', '[...]')))) {
				$data->addText('obverse', '<div id="edition"><div class="textpart"><span class="linenumber">O: </span>' . $item->obverseLegend . '</div></div>');
			}
			if (!empty($item->reverseLegend) and (!in_array($item->reverseLegend, array('[]', '[...]')))) {
				$data->addText('reverse', '<div id="edition"><div class="textpart"><span class="linenumber">R: </span>' . $item->reverseLegend . '</div></div>');
			}
            if (isset($item->obverseDescription)) {
                $data->addTable('Obverse', $item->obverseDescription);
            }
			if (isset($item->reverseDescription)) {
                $data->addTable('Reverse', $item->reverseDescription);
            }

			$data->addTable('Diameter', !empty($item->diameter) ? $item->diameter . 'mm' : false);
			$data->addTable('Weight', !empty($item->weight) ? $item->weight . 'g' : false);
			$data->addTable('Material', $item->materialTerm);
			
			$latitude = !empty($item->fourFigureLat) ? (float) $item->fourFigureLat : false;
			$longitude =!empty($item->fourFigureLon) ? (float) $item->fourFigureLon : false;
			//$data->addTable('schwabbel', "$latitude | $longitude");
			
			if (!empty($item->filename)) {
				$imgurl = "https://finds.org.uk/{$item->imagedir}medium/{$item->filename}";
				$data->addImages(array(
						'url' 		=> $imgurl,
						'fullres' 	=> $imgurl,
						'type' 	=> 'BITMAP'
				));
				//$data->addTable('test', $imgurl);
			}
			
			if (!empty($item->{"3D"})) {
				$data->addImages(array(
						'url' 	=> $item->{"3D"},
						'type' 	=> 'SKETCHFAB'
				));
				//ec61fa899e8748c5afdfbc7a5f4f97fe
			}
			
			return new \esa_item('finds_org', $item->id, $data->render(), $this->api_record_url($item->id), array(), array(), $latitude, $longitude);
		}
		
		private function _list() {
			$args = func_get_args();
			$r = array();
			foreach ($args as $arg) {
				if (empty($arg)) {
					continue;
				}
				$r[] = $arg;
			}
			return implode(', ', array_unique($r));
		}
		
		function parse_result_set($response) {
			
			$response = json_decode($response);
			
			// pagination
			$this->pages = 1 + (int) ($response->meta->totalResults / $response->meta->resultsPerPage);
			$this->page  = $response->meta->currentPage;
			
			//echo "<textarea style='width:100%'>many|", print_r($response,1), '</textarea>';
			$this->results = array();
			foreach ($response->results as $item) {
			 	$this->results[] = $this->_item2esa($item);
			}
			return $this->results;
		}
		
		function parse_result($response) {
			$a = $this->parse_result_set($response);
			return array_pop($a); // there will be only one because we're seaching for ujnique id
			
			$response = json_decode($response);
			$this->results = array();
			foreach ($response[1] as $item) {
				echo "<textarea style='width:100%'>one|", print_r($item,1), '</textarea>';
				return $this->_item2esa($item);
			}
		}



	}
}
?>