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
		public $info = false; // get created automatically, or enter text
		public $homeurl; // link to the dataset's homepage
		public $debug = true;
		
		public $pagination = false; // are results paginated?
		public $optional_classes = array(); // some classes, the user may add to the esa_item

		function api_search_url($query, $params = array()) {
			return "http://search.eagle.research-infrastructures.eu/solr/EMF-index-cleaned/select?wt=json&start=11&q=$query";
		}
			
		function api_single_url($id) {
			//dnetresourceidentifier:UAH\:\:92b78b60f9d00a0ac34898be97d15188\:\:01f8fcf400938969ace9675f86365c2c\:\:visual
			$id = str_replace(':', '\:', $id);
			return "http://search.eagle.research-infrastructures.eu/solr/EMF-index-cleaned/select?q=dnetresourceidentifier:$id";
		}

		
		function api_record_url($id) {
			return "";
		}
			
		function api_url_parser($string) {
			if (preg_match('#https?\:\/\/en\.wikipedia\.org\/wiki\/(.*)#', $string, $match)) {
				return "...{$match[1]}";
			}
		}
			

			
		function parse_result_set($response) {
			$response = json_decode($response);
			// = new \DOMDocument();
			//$response->loadXML($response);
			

		
			
			$this->results = array();
			foreach ($response->response->docs as $page) {

				
				$ob = new \SimpleXMLElement($page->__result[0]);
				$obj = $ob->metadata->eagleObject;
				
				if ($this->debug) {
					$i++;
					$iu = $i * 11;
					echo "<textarea style='border:2px solid silver; width: 50%; min-height: 10px'; postion: absolute; right: 0px; top: {$iu}px>", print_r($obj, 1),"</textarea>";
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
				
				echo "<textarea style='border:2px solid silver; width: 50%; min-height: 10px'; postion: absolute; right: 0px; top: {$iu}px>", print_r($data, 1),"</textarea>";
						
				if (!isset($data['text']) or !$data['text']) {
					$data['text'][] = (string) $obj->description != (string) $obj->title ? (string) $obj->description : 'kein text';
				}
				$data['table']['type'] = $obj->entityType;
				$data['table']['repositoryname'] = $page->repositoryname;
				
				$data['table'] = array_filter($data['table'], function($part) {return $part and (string) $part != '';});
				
				
				// start html rendering
				
				if (count($data['images'])) {
					$html  = "<div class='esa_item_left_column'>";
					foreach($data['images'] as $image)  {
						$html .= "<div class='esa_item_main_image' style='background-image:url(\"{$image->url}\")' title='{$image->title}'>&nbsp;</div>";
						$html .= "<div class='esa_item_subtext'>{$image->text}</div>";
					}
					$html .= "</div>";
					$html .= "<div class='esa_item_right_column'>";
				} else {
					$html = "<div class='esa_item_single_column'>";
				}
				

				$html .= "<h4>{$obj->title}</h4><br>";

				foreach ($data['text'] as $type => $text) {
					$html .= "<span class='eagle_{$type}'>$text</span>";
				}
				
				$html .= "<ul class='datatable'>";
				foreach ($data['table'] as $field => $value) {
					if ($value != '') {
						$label = $this->_label($field);
						$html .= "<li><strong>{$label}: </strong>{$value}</li>";
					}
				}
				$html .= "</ul>";

				$html .= "</div>";
					
					
				$this->results[] = new \esa_item('eagle', $page->dnetresourceidentifier, $html, $page->landingpage);
				break;
			}
			return $this->results;
		}

		function parse_result($response) {
			// if always return a whole set
			$res = $this->parse_result_set($response);
			return $res[0];
		}
		
		private function _label($of) {
			$labels = array(
					'objectType' => 'Type',
					'repositoryname' => 'Repository',
					'material' => 'Material',
					'tmid' => 'Trismegistos-Id',
					'artifactType' => 'Artifact Type',
					'objectType2' => 'Type',
					'transcription' => 'Transcription'
			);
			
			return (isset($labels[$of])) ? $labels[$of] : $of;
		}
		
		private function _document($epi, &$data) {
			
			echo "<textarea style='border:2px solid silver; width: 50%; min-height: 10px'; postion: absolute; right: 0px; top: {$iu}px>", print_r($epi, 1),"</textarea>";
				
			
			if ($epi->hasArtifact) {
				$this->_artifact($epi->hasArtifact, $data);
			}
			
			if ($epi->transcription) {
				$data['text']['transcription'] = $epi->transcription->textHtml->asXML();
			}
			
			if ($epi->translation) {
				$aut = ($epi->translation->publicationAuthor) ? $epi->translation->publicationAuthor : ($epi->translation->publicationEditor) . ' (Ed.)';
			
				$text = "{$epi->translation->text}";
			
				if ($epi->translation->author) {
					$text .= "<div class='sub1'>(Translation by {$epi->translation->author})</div>";
				}
			
				if (count($epi->translation->comments)) {
					$text .= "<div class='sub2'>{comments}</div>";
				}
			
				if ((string) $epi->translation->publicationTitle =! '1') {
					$text .= "<div class='sub2'>Publication: ";
					$text .= "{$epi->translation->publicationAuthor}: {$epi->translation->publicationTitle}. {$epi->translation->publicationPlace}, {$epi->translation->publicationYear}";
					$text .= "</div>";
				}
				
				$data['text']['translation'] = $text;
			}
				
			
			
			
			return $data;
		}
		
		
		private function _visual($visu, &$data) {
			if ($visu->hasArtifact) {
				$this->_artifact($visu->hasArtifact, $data);
			}
			
			
			$data['images'][] = (object) array(
					'url' => (string) $visu->thumbnail,
					'title' => (string) $visu->visualRepresentationIpr,
					'text'=> (string)  $visu->description
			);
			return $data;
		}
		
		
		private function _artifact($artifact, &$data) {
			if ($artifact->hasVisualRepresentation) {
				foreach($artifact->hasVisualRepresentation as $visu)  {

				}
			}
			if ($artifact->inscription->hasTmId) {
				$tabledata['tmid'] = $artifact->inscription->hasTmId->tmId;
			}
			$data['table']['material'] = $artifact->material;
			$data['table']['artifactType'] = $artifact->artifactType;
			$data['table']['objectType2'] = $artifact->objectType;
			$data['table']['inscriptionType'] = $artifact->inscriptionType;
			$data['table']['conservationPlace'] = $this->_place($artifact->conservationPlace->conservationCity, $artifact->conservationPlace->conservationCountry);
			if ($artifact->findingSpot) {
				$data['table']['findingSpotAncient'] = $this->_place($artifact->findingSpot->ancientFindSpot, $artifact->findingSpot->romanProvinceItalicRegion);
				$data['table']['findingSpotModern'] = $this->_place($artifact->findingSpot->modernFindSpot, $artifact->findingSpot->modernCountry);
			}
			
			
			return $data;
			
		}
		
		private function _place() {
			$parts = func_get_args();
			$filtered = array_filter($parts, function($part) {return $part and (string) $part != '';});
			return implode(', ', $filtered);
		}
	}
}
?>