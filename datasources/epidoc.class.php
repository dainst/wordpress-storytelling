<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: epidoc
 * @link 		
 * @author 		
 *
 * Status: Alpha 1
 * 
 * Takes a link to a ressource encoded in epidoc and builds a Esa Item around it.
 *
 *
 * Proovided List of EpiDoc Using Projects
 * 
 * * http://www.eagle-network.eu/advanced-search/
 * 
 * * Ancient Inscriptions of the Northern Black Sea - http://iospe.kcl.ac.uk/
 * * Epigraphische Datenbank Heidelberg - http://edh-www.adw.uni-heidelberg.de/
 * * http://agp.wlu.edu/search
 * * Inscriptions of Greek Cyrenaicaica - https://igcyr.unibo.it/
 * * Inscriptions of Israel/Palestine - http://library.brown.edu/cds/projects/iip/search/

 * 
 * * The Inscriptions of Roman Tripolitania: http://inslib.kcl.ac.uk/
 * * Vindolanda Tablets Online: http://vindolanda.csad.ox.ac.uk/
 * * Inscriptions of Aphrodisias: http://insaph.kcl.ac.uk/iaph2007/index.html
 * 
 * 
 * * Datenbank zur jüdischen Grabsteinepigraphik - http://steinheim-institut.de
 * 
 * 
 * http://edh-www.adw.uni-heidelberg.de/edh/inschrift/HD006705.xml
 * http://vindolanda.csad.ox.ac.uk/Search/tablet-xml-files/128.xml
 * 
 */


namespace esa_datasource {
	class epidoc extends abstract_datasource {

		public $title = 'Epidoc'; // Label / Title of the Datasource
		public $info = 'http://edh-www.adw.uni-heidelberg.de/edh/inschrift/HD006705.xml<br>http://iospe.kcl.ac.uk/5.7.xml'; 
		public $homeurl = ''; // link to the dataset's homepage
		public $debug = true;
		
		public $pagination = false; // are results paginated?
		public $optional_classes = array(); // some classes, the user may add to the esa_item

		public $require = array('inc/epidocConverter/epidocConverter.class.php');
		
		
		
		function api_search_url($query, $params = array()) {
			//http://edh-www.adw.uni-heidelberg.de/edh/inschrift/HD000015.xml
			if ($this->_ckeck_url($query)) {
				return $query;
			}
			return "";
		}
			
		function api_single_url($id) {
			if ($this->_ckeck_url($id)) {
				return $id;
			}
			return "";
		}


		
		function api_record_url($id) {
			return "";
		}
			
		function api_url_parser($string) {
			return $string;
		}
			
		function parse_result_set($response) {
			return array($this->parse_result($response));
		}

		function parse_result($response) {	

			$c = new \epidocConverter($response);

			$epi = $c->convert();
			
			$mainTextMap = array(
				'edition',
				'translation'
			);
			
			foreach ($epi->children() as $tagname => $div) {
				if ($tagname == 'div') {
					
					$id = (string) $div['id'];
					
					$field = in_array($id, $mainTextMap) ? 'text' : 'table';
					
					$data[$field][$id] = $div->asXML();
					
				}
			}

			return new \esa_item('epidoc', $this->query, $this->render_item($data), $data['url']);
		}
		
		
		function dependency_check() {
			

			$c = new \epidocConverter;
			
			try {
				$c->status();
			} catch (\Exception $e) {
				return $e->getMessage();
			}
			
			return true;
			
			
		}

	}
}
?>