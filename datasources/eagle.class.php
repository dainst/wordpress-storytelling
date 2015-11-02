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
		public $info = '<p>Search the Eagle Database</p>'; // get created automatically, or enter text
		public $homeurl; // link to the dataset's homepage
		public $debug = false;
		
		public $converter_ffm = false; // force fallback mode of EpidocConverter ?
		
		public $pagination = true; // are results paginated?
		public $optional_classes = array(); // some classes, the user may add to the esa_item
		private $_hits_per_page = 10;

		function api_search_url($query, $params = array()) {
			
			//  identify eagle id
			if (substr_count($query, '::') == 2) {
				return $this->api_single_url($query);
			}
			
			$query = str_replace(':', '\:', $query);
			return "http://search.eagle.research-infrastructures.eu/solr/EMF-index-cleaned/select?rows={$this->_hits_per_page}&wt=json&q=$query";
		}
			
		function api_single_url($id) {
			//dnetresourceidentifier:UAH\:\:92b78b60f9d00a0ac34898be97d15188\:\:01f8fcf400938969ace9675f86365c2c\:\:visual
			$id = str_replace(':', '\:', $id);
			//$id = rawurlencode($id);
			return 'http://search.eagle.research-infrastructures.eu/solr/EMF-index-cleaned/select?wt=json&q=dnetresourceidentifier:'.$id;
		}

		
		function api_record_url($id) {
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
				$data = array();

				if ($obj->entityType == 'artifact') {
					$this->_artifact($obj->artifact, $data);
				} else if ($obj->entityType == 'documental') {
					$data = $this->_document($obj->documentalManifestation, $data);
				} else 	if ($obj->entityType == 'visual') {
					$this->_visual($obj->visualRepresentation, $data);
				}
				
				/*		
				if (!isset($data['text']) or !$data['text']) {
					$data['text'][] = (string) $obj->description != (string) $obj->title ? (string) $obj->description : 'kein text';
				}*/
				//$data['table']['type'] = $obj->entityType;
				$data['table']['repositoryname'] = $page->repositoryname;
								
				$data['table'] = array_filter($data['table'], function($part) {return $part and (string) $part != '';});
				
				$data['title'] = $obj->title;
									
				$this->results[] = new \esa_item('eagle', $page->dnetresourceidentifier, $this->render_item($data), $page->landingpage);
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
			
				$data['text']['translation'] = "{$epi->translation->text}";
			
				if ($epi->translation->author) {
					$data['table']['Translation'] = $epi->translation->author;
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
					$data['table']['publication'] = $pub;
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
			
			$data['images'][] = (object) array(
					'url' => (string) $visu->thumbnail,
					'title' => (string) $visu->visualRepresentationIpr,
					'text'=> (string)  $visu->description
			);
			return $data;
		}
		
		private function _transcription($trans, &$data) {
			$data['text']['transcription'] = $trans->text;
			//$data['text']['transcription'] = $trans->textHtml->asXML();
			//$data['text']['transcription'] = $trans->textEpidoc->asXML();
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
					$epiDom->loadHTML(mb_convert_encoding($epi, 'HTML-ENTITIES', 'UTF-8'));
					//$epiDom->normalize();
					$divs = $epiDom->getElementsByTagName('div');
					foreach ($divs as $div) {
						$firstchild = $div->firstChild;
						if ($firstchild->nodeName == 'br') {
							$firstchild->parentNode->removeChild($firstchild);
						}
						$data['text']['transcription'] = trim($epiDom->saveHTML($div));
					}
					
					$data['table']['debug'] = $c->status();
					//$data['text']['transcription'] = $epi;
				} catch (\Exception $e) {
					$data['table']['debug'] = $e->getMessage();
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
				$data['table']['tmid'] = $artifact->hasTmId->tmId;
			}
			$data['table']['material'] = $artifact->material;
			$data['table']['artifactType'] = $artifact->artifactType;
			$data['table']['objectType2'] = $artifact->objectType;
			$data['table']['inscriptionType'] = $artifact->inscriptionType;
			$data['table']['conservationPlace'] = $this->_place($artifact->conservationPlace->conservationCity, $artifact->conservationPlace->conservationCountry);
			$data['table']['originDating'] = $artifact->originDating;
			if ($artifact->findingSpot) {
				$data['table']['findingSpotAncient'] = $this->_place($artifact->findingSpot->ancientFindSpot, $artifact->findingSpot->romanProvinceItalicRegion);
				$data['table']['findingSpotModern'] = $this->_place($artifact->findingSpot->modernFindSpot, $artifact->findingSpot->modernCountry);
			}
			
			
			return $data;
			
		}
		
		private function _inscription($ins, &$data) {
			if ($ins->hasTmId) {
				$data['table']['tmid'] = $ins->hasTmId->tmId;
			}
			$data['table']['paleographicCharacteristics'] = $ins->paleographicCharacteristics;
			$data['table']['honorand'] = $ins->honorand;
			
			if ($ins->hasTranscription) {
				$this->_transcription($ins->hasTranscription, $data);
			}
			
			if ($ins->hasTranslation) {
				$data['text']['translation'] = $ins->hasTranslation->text;
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