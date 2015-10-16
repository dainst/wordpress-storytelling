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
 */


namespace esa_datasource {
	class epidoc extends abstract_datasource {

		public $title = 'Epidoc'; // Label / Title of the Datasource
		public $info = 
			'Some Projects using the EpiDoc-Format:<ul>
				<li>
				<li>
				</ul>'; 
		public $homeurl = ''; // link to the dataset's homepage
		public $debug = true;
		
		public $pagination = false; // are results paginated?
		public $optional_classes = array(); // some classes, the user may add to the esa_item

		function api_search_url($query, $params = array()) {
			//http://edh-www.adw.uni-heidelberg.de/edh/inschrift/HD000015.xml
			if ($this->_ckeck_url($query)) {
				return $query;
			}
			return "";
		}
			
		function api_single_url($id) {
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
			require_once('inc/epidocElement.class.php');
			
			$xml = new \epidocElement($response);
			
			if (!$xml->validate()) {
				return $this->error('No Epidoc File detected!');
			}
			
			
			echo "<textarea>", $xml->asXML(), "</textarea>";
			
			
			$publicationStmt = array();
			foreach ($xml->teiHeader->fileDesc->publicationStmt->idno as $idno) {
				$publicationStmt[(string) $idno->type] = (string) $idno;
			}
			
			
			if (!$xml->validate()) {
				$this->error('No Epidoc File detected!');
			}
			

			echo "<textarea>", $xml->asXML(), "</textarea>";
			
			
			$publicationStmt = array();
			foreach ($xml->teiHeader->fileDesc->publicationStmt->idno as $idno) {
				$atts = $idno->attributes();
				$publicationStmt[(string) $atts->type] = (string) $idno;
			}
			print_r($publicationStmt);
			
			$data = array(
					'title'	=> (string) $xml->teiHeader->fileDesc->titleStmt->title,
					'table'	=> array(
						'provider'	=> (string) $xml->teiHeader->fileDesc->publicationStmt->authority,
						'tmid'		=> $publicationStmt['TM'],
						
						
					),
				'url' => $publicationStmt['URI']
			);		
			
			return new \esa_item('epidoc', $this->query, $this->render_item($data), $data['url']);

		}
		

	}
}
?>