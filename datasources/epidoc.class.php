<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: epidoc
 * @link 		http://sourceforge.net/projects/epidoc/
 * @author 		Philipp Franck
 *
 * Status: Beta
 * 
 * Takes a link to a ressource encoded in epidoc and builds a Esa Item around it.
 *
 *
 * 
 * 
 */


namespace esa_datasource {
	class epidoc extends abstract_datasource {
		
		public $title = 'Epidoc'; // Label / Title of the Datasource
		public $index = 25; // where to appear in the menu
		public $info = "<p>
							<a href='http://sourceforge.net/projects/epidoc/' target='_blank'>EpiDoc</a> is an international, 
							collaborative effort that provides guidelines and tools 
							for encoding scholarly and educational editions of ancient documents. It uses a subset 
							of the TEI standard for the representation of texts in digital 
							form and was developed initially for the publication of digital editions of ancient 
							inscriptions.
						</p>
						<p>
							You can paste an URL to any Epidoc-encoded Document here to embed it's content in the Text.
						</p>
						<p>
							If you are unsure where to find such things, have a look <i>for example</i> at 
							<a href='#' class='toggle' data-toggle='epidoclist'>theese databases</a>
							and	search for Buttons named 'export as XML' or 'export as Epidoc' to get URL.
						</p>
						<ul class='toggleable toggled' id='epidoclist'>
							<li><a href='http://www.eagle-network.eu/advanced-search/' 		target='_blank'>Eagle Network</a></li>
							<li><a href='http://edh-www.adw.uni-heidelberg.de/' 			target='_blank'>Epigraphische Datenbank Heidelberg</a></li>
							<li><a href='http://iospe.kcl.ac.uk/' 							target='_blank'>Ancient Inscriptions of the Northern Black Sea</a></li>
							<li><a href='https://igcyr.unibo.it/' 							target='_blank'>Inscriptions of Greek Cyrenaicaica </a></li>
							<li><a href='http://library.brown.edu/cds/projects/iip/search/' target='_blank'>Inscriptions of Israel/Palestine</a></li>
							<li><a href='http://www.trismegistos.org/' 						target='_blank'>trismegistos.org: Papyrological and epigraphical resources formerly Egypt and the Nile valley</a></li>
							<li><a href='http://papyri.info' 								target='_blank'>Papyri.info: Aggregated material from the Advanced Papyrological Information System</a></li>
							<li><a href='http://inslib.kcl.ac.uk/' 							target='_blank'>The Inscriptions of Roman Tripolitania</a></li>
							<li><a href='http://vindolanda.csad.ox.ac.uk/' 					target='_blank'>Vindolanda Tablets</a></li>
							<li><a href='http://insaph.kcl.ac.uk/iaph2007/index.html' 		target='_blank'>Inscriptions of Aphrodisias</a></li>
							<li><a href='http://steinheim-institut.de' 						target='_blank'>Datenbank zur jüdischen Grabsteinepigraphik</a></li>
					
						</ul>
						";
		public $homeurl = ''; // link to the dataset's homepage
		public $examplesearch = 'e. g. http://edh-www.adw.uni-heidelberg.de/edh/inschrift/HD000106.xml';
		public $searchbuttonlabel = 'Import';
		
		public $debug = false;
		
		public $pagination = false; // are results paginated?
		public $optional_classes = array(); // some classes, the user may add to the esa_item

		public $require = array(
		    'inc/epidocConverter/epidocConverter.class.php'
		);
		
		public $id_is_url = true;
		
		
		function api_search_url($query, $params = array()) {
			//http://edh-www.adw.uni-heidelberg.de/edh/inschrift/HD000015.xml
			if ($this->_ckeck_url($query)) {
				return $query;
			}
			return "";
		}
			
		function api_single_url($id, $params = array()) {
			if ($this->_ckeck_url($id)) {
				return $id;
			}
			return "";
		}
		
		function api_record_url($id, $params = array()) {
			return $id;
		}
			
		function parse_result_set($response) {
			return array($this->parse_result($response[0]));
		}
		
		function parse_result($response) {
			error_reporting(true);
			ini_set('display_errors', '1');
			$c = \epidocConverter::create($response, $this->epidoc_settings['mode'], $this->epidoc_settings['settings']);
			$epi = $c->convert(true);
			
			$map = array(
				'edition' => true,
				'translation' => true
			);
						
			$epiDom = new \DOMDocument();
			@$epiDom->loadHTML($epi);
			
			$divs = $epiDom->getElementsByTagName('div');
			
			$data = new \esa_item\data();
			
			foreach ($divs as $div) {
					
				
				$idx = explode('_', $div->getAttribute('id'));
				
				$id = $idx[0];
				$lang = isset($idx[1]) ? $idx[1] : 0; 
				
				
				if (!isset($map[$id])) {
					continue;
				}
				
				$field = $map[$id] ? 'text' : 'table';
				$title = $id;

				
				// saxon stylesheets provide some headlines
				$h2s = $div->getElementsByTagName('h2');
				foreach ($h2s as $h2) {
					//$title = $h2->nodeValue;
					$h2->parentNode->removeChild($h2);
				}
				$h3s = $div->getElementsByTagName('h3');
				foreach ($h3s as $h3) {
					//$title = $h3->nodeValue;
					$h3->parentNode->removeChild($h3);
				}
				
				// remove trailing <br> tag
				$firstchild = $div->firstChild;
				if ($firstchild->nodeName == 'br') {
					$firstchild->parentNode->removeChild($firstchild);
				}
				
				$data->{$field}[$title][$lang] = trim($epiDom->saveHTML($div));
			}
			
			// get translation, english if avalable
			foreach ($map as $title => $isText) {
				if (!isset($data->{$field}[$title])) {
					continue;
				}
				
				
				$field = $isText ? 'text' : 'table';
				if (isset($data->{$field}[$title]['en'])) {
					$data->{$field}[$title] = $data->{$field}[$title]['en'];
				} else {
					$last = array_pop($data->{$field}[$title]);
					$data->{$field}[$title] = $last;
				}
			}
			
			
			
			// fetch the rest of relevant data manually
			
			$xml = new \SimpleXMLElement($response);
			
			// teiHeader
			// teiHeader->fileDesc
			$data->title = $this->_get(
				$xml->teiHeader->fileDesc->titleStmt->title
			);

			$data->table['provider'] = $this->_get(
				$xml->teiHeader->fileDesc->publicationStmt->authority
			);
			
			// teiHeader->fileDesc->sourceDesc
			
			$data->table['objectType'] = $this->_get(
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->physDesc->objectDesc->supportDesc->support->objectType,
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->physDesc->objectDesc->supportDesc->support->p->objectType
			);
			$data->table['material'] = $this->_get(
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->physDesc->objectDesc->supportDesc->support->material,
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->physDesc->objectDesc->supportDesc->support->p->material
			);				
			$data->table['execution'] = $this->_get(
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->physDesc->objectDesc->layoutDesc->layout->execution
			);
			$data->table['modernFindSpot'] = $this->_get(
					$xml->teiHeader->fileDesc->sourceDesc->msDesc->history->provenance->placeName,
					$xml->teiHeader->fileDesc->sourceDesc->msDesc->history->provenance->p->placeName
			);
			$data->table['ancientFindSpot'] = $this->_get(
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->history->origin->origPlace->placeName,
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->history->origin->origPlace
			);
			$data->table['origDate'] = $this->_get(
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->history->origin->origDate
			);

			$data->table['urls'] = $this->_get(
					$xml->teiHeader->fileDesc->publicationStmt->idno
			);
			$data->url = $this->_get(
					(string) $xml->teiHeader->fileDesc->publicationStmt->idno[0],
					$this->id
			);
			
			
			$data->images = $this->_getImage(
				$xml->facsimile->graphic
			);
			
			$data->table['xslt'] = $c->status();
			
			//$debug = '<textarea>'. print_r($data['text'], 1) . '</textarea>';

			return new \esa_item('epidoc', $this->query, $debug . $data->render(), $data->url);
		}
		
		
		private function _getImage() {
			$alternatives = func_get_args();
				
			while (count($alternatives)) {
				$elems = array_shift($alternatives);
				if (!$elems instanceof \SimpleXMLElement) {
					continue;
				}
				
				if (count($elems)) {
					$list = array();
						
					foreach($elems as $elem) {
						$img = new \esa_item\image(array(
							'url' => $elem['url'],
							'text' => (string) $elem->desc,
							'title' => (string) $elem->desc->ref
						));
						

						
						$list[] = $img;
					}
					return $list;
				}
			}

			
		}
		
		
		private function _get() {
			$alternatives = func_get_args();
			
			while (count($alternatives)) {
				$elems = array_shift($alternatives);
				if (!$elems instanceof \SimpleXMLElement) {
					return (string) $elems;
				}
				
				
								
				if (isset($elems) and count($elems)) {
					
					$texts = array();
					
					foreach($elems as $elem) {
						$text = (string) $elem;
						
						// rs elements
						if (($text == 'rs') && (isset($elem['type']))) {
							$text = $elem['type'];
						}
						
						// links
						if (isset($elem['ref'])) {
							$text = "<a target='_blank' href='{$elem['ref']}'>$text</a>";
						}
						if (isset($elem['type']) and ($elem['type'] == 'URI')) {
							$text = "<a target='_blank' href='{$text}'>$text</a>";
						}
						
						$texts[] = $text;
					}
					
					
					return implode(', ', array_unique($texts));
					
				}
			}
			
			return '';
			
		} 
		
		
		function dependency_check() {
			$c = \epidocConverter::create('', $this->epidoc_settings['mode']);
			return $c->status();
		}
		
		function stylesheet() {

			$c = \epidocConverter::create('', $this->epidoc_settings['mode']);
			$css =
				$c->getStylesheet() . "
				.esa_item_collapsed .textpart  {
					left: 0em;
				}
				
				.esa_item_collapsed .linenumber {
					display: none
				}";
			
			return array(
				'css' => $css,
				'name' => 'epidoc'
			);
			
		}

	}
}
?>