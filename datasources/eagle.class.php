<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: Eagle
 * @link 		
 * @author 		
 *
 * Searches in the Eagle Database directly.
 *
 */


namespace esa_datasource {
	class eagle extends abstract_datasource {

		public $title = 'Eagle'; // Label / Title of the Datasource
		public $index = 10; // where to appear in the menu
		public $info = '<p>Search the Eagle Database</p>'; // get created automatically, or enter text
		public $homeurl; // link to the dataset's homepage
		public $debug = false;
		
		public $converter_ffm = false; // force fallback mode of EpidocConverter ?
		
		public $pagination = true; // are results paginated?
		public $optional_classes = array(); // some classes, the user may add to the esa_item
		private $_hits_per_page = 10;

		/**
		 * eagle store specific
		 */
		public $eagle_store = false;
		public $eagle_store_user_id;
		public $eagle_store_data_path;
		
		
		function api_search_url($query, $params = array()) {

			//  identify eagle id
			if (substr_count($query, '::') == 2) {
				return $this->api_single_url($query);
			}
			
			// search string
			$query = str_replace(':', '\:', $query);
			return "http://search.eagle.research-infrastructures.eu/solr/EMF-index-cleaned/select?rows={$this->_hits_per_page}&wt=json&q=$query";
		}
			
		function api_single_url($id, $params = array()) {
			//dnetresourceidentifier:UAH\:\:92b78b60f9d00a0ac34898be97d15188\:\:01f8fcf400938969ace9675f86365c2c\:\:visual
			$id = str_replace(':', '\:', $id);
			//$id = rawurlencode($id);
			return 'http://search.eagle.research-infrastructures.eu/solr/EMF-index-cleaned/select?wt=json&q=dnetresourceidentifier:'.$id;
		}

		
		function api_record_url($id, $params = array()) {
			return "";
		}
			
		function api_url_parser($string) {
			// eagle search has no URIS!
			return false;
			
		}
			
		function api_search_url_next($query, $params = array()) {
			$this->page += 1;
			$start = 1 + ($this->page - 1) * $this->_hits_per_page;
			return "http://search.eagle.research-infrastructures.eu/solr/EMF-index-cleaned/select?rows={$this->_hits_per_page}&wt=json&start=$start&q=$query";
					}
			
		function api_search_url_prev($query, $params = array()) {
			$this->page -= 1;
			$start = 1 + ($this->page - 1) * $this->_hits_per_page;
			return "http://search.eagle.research-infrastructures.eu/solr/EMF-index-cleaned/select?rows={$this->_hits_per_page}&wt=json&start=$start&q=$query";
		}
			
		function api_search_url_first($query, $params = array()) {
			return "http://search.eagle.research-infrastructures.eu/solr/EMF-index-cleaned/select?rows={$this->_hits_per_page}&wt=json&q=$query";
		}
			
		function api_search_url_last($query, $params = array()) {
			$this->page = $this->pages;
			$last = 1 + ($this->pages) * $this->_hits_per_page;
			return "http://search.eagle.research-infrastructures.eu/solr/EMF-index-cleaned/select?rows={$this->_hits_per_page}&wt=json&start=$last&q=$query";
		}
		

		
		/**
		 * Functions making use of the Store function from the eagle serach plugin used in eagle-network.eu
		 * 
		 */
		
		/**
		 * is overwritten because we want to acces items from lcoal stash on http://www.eagle-network.eu as well maybe...
		 * @see \esa_datasource\abstract_datasource::search()
		 */
		function search($query) {
			$query = (isset($_POST['esa_ds_query'])) ? $_POST['esa_ds_query'] : $query;
			if (($query == '') and ($this->eagle_store))  {
				try {
					$this->parse_result_set($this->eagle_store_get_object());
					return true;
				} catch (\Exception $e) {
					$this->error($e->getMessage());
				}
			}
			return parent::search($query);
		}
		
		
		
		/**
		 * @see \esa_datasource\abstract_datasource::construct()
		 */
		function construct() {
			// check if we are in wordpress context and have the eagle-serach plugin installes
			if (function_exists('is_plugin_active') and is_plugin_active('eagle-search/eagle-search.php')) {
				$this->eagle_store_data_path = WP_CONTENT_DIR;
				$this->eagle_store_user_id = get_current_user_id();
				$this->eagle_store = true;
				require_once '/var/www/eagle/wp-content/plugins/eagle-search' . '/class/EagleSearch.php';
				require_once '/var/www/eagle/wp-content/plugins/eagle-search' . '/class/EagleSaveSystem.php';
				$this->info = "<p>Type in a keyword to search in the Eagle database or <a href='http://www.eagle-network.eu/basic-search/' target='_blank'>use the genuine Eagle search Interface</a> and the save function to display them here.</p>";
			}
		}
		
		/**
		 * @see \esa_datasource\abstract_datasource::dependency_check()
		 */
		function dependency_check() {
			$this->construct();
			return 'O. K.' . ($this->eagle_store ? ' (Eagle Search Plugin connected)' : '');
		}
		

		/**
		 * get an object or the a list of object from the private storing space, set up in eagle search plugin
		 * @param string $object_id
		 * @throws \Exception
		 * @return string
		 */
		function eagle_store_get_object($object_id = false) {
	        $eagle = new \EagleSearch();
	        $eagle->setSavePath($this->eagle_store_data_path);
	        
	        if ($object_id) {
	        	if (!$dbRow = \EagleSaveSystem::getObjectFromDB($object_id, $this->eagle_store_user_id)) {
	        		throw new \Exception('Error in EagleSaveSystem');
	        	}
	        	$dbRows = array($dbRow);
	        } else {
	        	$dbRows = \EagleSaveSystem::getLastObjectSaved($this->eagle_store_user_id);
	        }
	        
	        $result = array();
	        
			foreach($dbRows as $dbRow) {
	        
		        try {
	           		$largelist = json_decode($eagle->getSavedList($dbRow->resource));
		        } catch (\Exception $e) {
		        	throw new \Exception('Error in eagle-search ("' . $e->getMessage() . '"). Most likely item ' . $object_id  . ', ' . $user_id . ' is written in db, but cached file ' . $dbRow->resource . ' does not exist.');
	 	        }
	                       
	            // get whole list
	            /*
	            foreach ($largelist->grouped->tmid->groups as $groups) {
	            	$result = array_merge($result, $groups->doclist->docs);
	            }
				*/
	            
	            $result[] = $largelist->grouped->tmid->groups[0]->doclist->docs[$dbRow->row];
	            
			}

		    return json_encode((object) array('response' => array( 'docs' =>  $result)));

		}
		
		function show_pagination() {
			$query = (isset($_POST['esa_ds_query'])) ? $_POST['esa_ds_query'] : '';
			if (($query == '') and ($this->eagle_store))  {
				echo "<h3>My item archive<a href='http://www.eagle-network.eu/archives/' target='_blank'>↗</a></h3>";
			} else {
				parent::show_pagination();
			}
		}
		 
		
		
		/**
		 * Render results
		 * @see \esa_datasource\abstract_datasource::parse_result_set()
		 */
		function parse_result_set($response) {
			$response = json_decode($response);
			
					
			$this->results = array();
			foreach ($response->response->docs as $page) {

				$ob = new \SimpleXMLElement($page->__result[0]);
				$obj = $ob->metadata->eagleObject;
				
				if ($this->debug) {
					$i++;
					$iu = $i * 11;
					echo "<textarea style='border:2px solid silver; width: 100%; min-height: 150px'; postion: absolute; right: 0px; top: {$iu}px>", print_r($obj, 1),"</textarea>";
				}
				
				// different Types of entity
				$data = new \esa_item\data();

				if ($obj->entityType == 'artifact') {
					$this->_artifact($obj->artifact, $data);
				} else if ($obj->entityType == 'documental') {
					$data = $this->_document($obj->documentalManifestation, $data);
				} else if ($obj->entityType == 'visual') {
					$this->_visual($obj->visualRepresentation, $data);
				}
				
				/*		
				if (!isset($data->text) or !$data->text) {
					$data->text[] = (string) $obj->description != (string) $obj->title ? (string) $obj->description : 'kein text';
				}*/
				//$data->table['type'] = $obj->entityType;
				
				$data->table['repositoryname'] = $page->repositoryname;
								
				$data->table = array_filter($data->table, function($part) {return $part and (string) $part != '';});
				
				$data->title = $obj->title;
				
				$id = (isset($page->dnetresourceidentifier)) ? $page->dnetresourceidentifier : $obj->dnetResourceIdentifier; //wtf
				
				$this->results[] = new \esa_item('eagle', $id, $data->render(), $page->landingpage);
				//break;
			}
			
			$this->pages = round($response->response->numFound / $this->_hits_per_page);
				
			return $this->results;
		}

		function parse_result($response) {
			// if always return a whole set
			$res = $this->parse_result_set($response);
			return $res[0];
		}
		
		private function _document($epi, &$data) {
			
			//echo "<textarea style='border:2px solid silver; width: 50%; min-height: 10px'; postion: absolute; right: 0px; top: {$iu}px>", print_r($epi, 1),"</textarea>";
				
			
			if ($epi->hasArtifact) {
				foreach($epi->hasArtifact as $artifact) {
					$this->_artifact($artifact, $data);
				}
			}
			
			if ($epi->transcription) {
				$this->_transcription($epi->transcription, $data);
			}
			
			if ($epi->translation) {
				$aut = ($epi->translation->publicationAuthor) ? $epi->translation->publicationAuthor : ($epi->translation->publicationEditor) . ' (Ed.)';
			
				$data->text['translation'] = "{$epi->translation->text}";
			
				if ($epi->translation->author) {
					$data->table['Translation'] = $epi->translation->author;
				}
			
				/*if (count($epi->translation->comments)) {
					$text .= "<div class='sub2'>{comments}</div>";
				}*/
			
				if ((string) $epi->translation->publicationTitle != '1') {
					$publicationTitle = preg_replace('#\.$#', '', $epi->translation->publicationTitle, -1);
					$publicationTitle = str_replace('Originally published in ', '', $publicationTitle);
					$at = $this->_merge(': ', $epi->translation->publicationAuthor, $publicationTitle);
					$dp = $this->_merge(', ', $epi->translation->publicationPlace, $epi->translation->publicationYear);
					$pub = $this->_merge('. ', $at, $dp);
					$pub .= ($pub) ? '.' : '';
					$data->table['publication'] = $pub;
					//$text .= ($pub) ? "<div class='sub2'>Publication: $pub</div>" : '';
				}
				
			}
				
			
			
			
			return $data;
		}
		
		
		private function _visual($visu, &$data) {
			if ($visu->hasArtifact) {
				$this->_artifact($visu->hasArtifact, $data);
			}
			
			if ($visu->hasTranscription) {
				$this->_transcription($visu->hasTranscription, $data);
			}
			
			$data->images[] = new \esa_item\image(array(
					'url' => (string) $visu->thumbnail,
					'title' => (string) $visu->visualRepresentationIpr,
					'text'=> (string)  $visu->description
			));
			return $data;
		}
		
		private function _transcription($trans, &$data) {
			$data->text['transcription'] = $trans->text;
			//$data->text['transcription'] = $trans->textHtml->asXML();
			//$data->text['transcription'] = $trans->textEpidoc->asXML();
			$xml = $trans->textEpidoc->asXML();
			if ($xml and ($xml != '<textEpidoc/>')) {
				try {
					$this->_require('inc/epidocConverter/epidocConverter.class.php');
					$xml = "<TEI><text><body><div type='edition'>$xml</div></body></text></TEI>";
					$c = \epidocConverter::create($xml,$this->converter_ffm);

					$epi = $c->convert();
					
					// remove trailing <br> tag
					$epi = preg_replace("/>\s+</", "><", $epi);
					$epiDom = new \DOMDocument();
					@$epiDom->loadHTML(mb_convert_encoding($epi, 'HTML-ENTITIES', 'UTF-8'));
					//$epiDom->normalize();
					$divs = $epiDom->getElementsByTagName('div');
					foreach ($divs as $div) {
						$firstchild = $div->firstChild;
						if ($firstchild->nodeName == 'br') {
							$firstchild->parentNode->removeChild($firstchild);
						}
						$data->text['transcription'] = trim($epiDom->saveHTML($div));
					}
					
					//$data->table['debug'] = $c->status();
					//$data->text['transcription'] = $epi;
				} catch (\Exception $e) {
					$data->table['debug'] = $e->getMessage();
				}
			}
		}
		
		private function _artifact($artifact, &$data) {
			if ($artifact->hasVisualRepresentation) {
				foreach($artifact->hasVisualRepresentation as $visu)  {
					$this->_visual($visu, $data);
				}
			}
			
			if ($artifact->inscription) {
				$this->_inscription($artifact->inscription, $data);
			}
			if ($artifact->hasTmId) {
				$data->table['tmid'] = $artifact->hasTmId->tmId;
			}
			$data->table['material'] = $artifact->material;
			$data->table['artifactType'] = $artifact->artifactType;
			$data->table['objectType2'] = $artifact->objectType;
			$data->table['inscriptionType'] = $artifact->inscriptionType;
			$data->table['conservationPlace'] = $this->_place($artifact->conservationPlace->conservationCity, $artifact->conservationPlace->conservationCountry);
			$data->table['originDating'] = $artifact->originDating;
			if ($artifact->findingSpot) {
				$data->table['findingSpotAncient'] = $this->_place($artifact->findingSpot->ancientFindSpot, $artifact->findingSpot->romanProvinceItalicRegion);
				$data->table['findingSpotModern'] = $this->_place($artifact->findingSpot->modernFindSpot, $artifact->findingSpot->modernCountry);
			}
			
			
			return $data;
			
		}
		
		private function _inscription($ins, &$data) {
			if ($ins->hasTmId) {
				$data->table['tmid'] = $ins->hasTmId->tmId;
			}
			$data->table['paleographicCharacteristics'] = $ins->paleographicCharacteristics;
			$data->table['honorand'] = $ins->honorand;
			
			if ($ins->hasTranscription) {
				$this->_transcription($ins->hasTranscription, $data);
			}
			
			if ($ins->hasTranslation) {
				$data->text['translation'] = $ins->hasTranslation->text;
			}
			
		}
		
		private function _merge() {
			$parts = func_get_args();
			if (count($parts) == 0) {
				return '';
			}
			if (count($parts) == 1) {
				return $parts[0];
			}
			if (count($parts) == 2) {
				return $parts[1];
			}
			$glue = array_shift($parts);
			$filtered = array_filter($parts, function($part) {return $part and (string) $part != '';});
			return implode($glue, $filtered);
		}
		
		private function _place() {
			$parts = func_get_args();
			$filtered = array_filter($parts, function($part) {return $part and (string) $part != '';});
			return implode(', ', $filtered);
		}
		
		
		
		function stylesheet() {
			$this->_require('inc/epidocConverter/epidocConverter.class.php');
			$c = \epidocConverter::create('', $this->converter_ffm);
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