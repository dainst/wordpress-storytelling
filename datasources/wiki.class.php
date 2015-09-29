<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: Wikipedia
 * @link 		http://www.europeana.eu/
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
		
		public $info = "Use the field above to search for articles in the <a href='https://en.wikipedia.org/' target='_blank'>Wikipedia</a> or insert a link to page from the english wikipedia.";
	
		public $pagination = false;
		
		public $params = array(
			'lang'  => 'en'
		);
				
		//public $apiurl = "https://en.wikipedia.org/w/api.php?action=query&format=json&list=allimages&titles=%s";
		function api_search_url($query) {
			$query = urlencode($query);
			return "https://{$this->params['lang']}.wikipedia.org/w/api.php?action=query&prop=extracts|categories|links|info&inprop=url&pllimit=max&exintro=&format=json&redirects=&titles=$query";
		}
		
		function api_single_url($id) {
			$id = $this->real_id($id);
			$id = urlencode($id);
			return "https://{$this->params['lang']}.wikipedia.org/w/api.php?action=query&prop=extracts|info&inprop=url&exintro=&format=json&redirects=&titles=$id";
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
		
		function api_image_url($title) {
			$title = urlencode($title);
			return "https://{$this->params['lang']}.wikipedia.org/w/api.php?action=query&prop=imageinfo&format=json&iiprop=url|size|mediatype|extmetadata&iiurlwidth=150&titles=$title&generator=images&redirects=";
		}
		
		// ids look like that: wikiID@wikiLANG  -> this function strips them
		function real_id($id) {
			$ex = explode('@', $id);
			$this->params['lang'] = (isset($ex[1])) ? $ex[1] : $this->params['lang'];
			return $ex[0];
		}
		

		
		function parse_result_set($response, $subquery = false) {

			$results = array();
			
			$response = json_decode($response);
			
			//echo "<pre>", print_r($response, 1), "</pre>";
			
			foreach ($response->query->pages as $page) {
				
				if (property_exists($page, 'missing')) {
					continue;
				}					
				
				// finde Kategorien
				$categories  = array(); 			
				if (isset($page->categories)) {			
					foreach ($page->categories as $category) {
						$categories[] = $category->title;
					}
				}
					
				// Sonderfall: Disambiguation Page
				if (in_array('Category:All disambiguation pages', $categories)) {
					//echo "<hr><pre>".print_r($page,1)."</pre></hr>";
					foreach ($page->links as $link) {
						
						if ($link->ns != 0) {
							continue;
						}
						
						$d = $this->_fetch_external_data($this->api_single_url($link->title));
						//echo "<hr><pre>".print_r($d,1)."</pre></hr>";
						$results = array_merge($results, $this->parse_result_set($d, true));
					}
				
				// normaler Fall
				} else {						
					$results[] = $this->render_page($page);
				}

			}
			
			if (!$subquery) {
				$this->results = $results;
			}
			
			return $results;
		}

		
		function render_page($page) {
				
			// fetch image / media data
			
			//$url = "https://en.wikipedia.org/w/api.php?action=query&prop=imageinfo&format=json&iiprop=url|size|mime|mediatype|extmetadata&iiurlwidth=150&titles=$title&redirects=";
				
			$imageData = $this->_fetch_external_data($this->api_image_url($page->title));
			$imageData = json_decode($imageData);
			
			//echo "<hr><pre>".print_r($this->api_image_url($page->title),1)."</pre>";
			//echo "<pre>", print_r($imageData,1), "</pre>";

			$subhtml = '';
			if (isset($imageData->query->pages)) {
				foreach ($imageData->query->pages as $imageItem) {
					
					//$skip_images = array( 'File:Commons-logo.svg', 'File:Boxed East arrow.svg', 'File:Openstreetmap logo.svg', 'File:PD-icon.svg', 'File:Question book-new.svg', 'File:Wiktionary-logo-en.svg', 'File:Folder Hexagonal Icon.svg', 'File:Wikiquote-logo.svg', 'File:Edit-clear.svg', 'File:Wikisource-logo.svg', 'File:Crystal personal.svg', 'File:Portal-puzzle.svg', 'File:Ambox important.svg', 'File:Text document with red question mark.svg', 'File:P vip.svg', 'File:Gloriole blur.svg', 'File:Star empty.svg', 'File:Star full.svg', 'File:Star half.svg', 'File:WPanthroponymy.svg', 'File:Gnome-dev-cdrom-audio.svg'
					//if (in_array($imageItem->title, $skip_images)) {continue;}
					
					$text = strip_tags($imageItem->imageinfo[0]->extmetadata->ImageDescription->value);
					$drlink = "<a href='{$imageItem->imageinfo[0]->url}' target='_blank'>{$imageItem->imageinfo[0]->title}</a>";
					
					switch($imageItem->imageinfo[0]->mediatype) {
						case 'BITMAP':$subhtml .= "<div class='esa_item_main_image' style='background-image:url(\"{$imageItem->imageinfo[0]->thumburl}\")' title='{$imageItem->title}'>&nbsp;</div><div class='esa_item_subtext'>{$text}</div>"; break;
						case 'AUDIO': $subhtml .= "<audio controls><source src='{$imageItem->imageinfo[0]->url}' type='{$imageItem->imageinfo[0]->mime}'>$drlink</audio><div class='esa_item_subtext'>{$text}</div>"; break;
						case 'VIDEO': $subhtml .= "<video controls><source src='{$imageItem->imageinfo[0]->url}' type='{$imageItem->imageinfo[0]->mime}'>$drlink</video><div class='esa_item_subtext'>{$text}</div>"; break;
						case 'DRAWING': break;
						default: $subhtml .= $drlink;
					}
								
					//$subhtml .= "<b>{$imageItem->imageinfo[0]->mediatype}</b>";
					//$subhtml .= "<textarea>".print_r($imageData,1)."</textarea>";
					//$subhtml .= '<hr>';
				}
			}
				
			if ($subhtml) {	
				$html  = "<div class='esa_item_left_column'>";
				$html .= $subhtml;
				$html .= "</div>";
				$html .= "<div class='esa_item_right_column'>";
			} else {
				$html .= "<div class='esa_item_single_column'>";
			}
				

			$html .= "<h4>{$page->title}</h4>";
			$html .= "<p>{$page->extract}</p>";
			$html .= "</div>";
			
			$id = $page->title . '@' . $this->params['lang'];
			
			return new \esa_item('wiki', $id, $html, $page->fullurl);
		}
		
		function parse_result($response) {
			// wikipedia always return a whole set
			$response = json_decode($response);
			
			//echo "<pre>", print_r($response, 1), "</pre>";die();
			
			foreach ($response->query->pages as $page) {
				return $this->render_page($page);
			}
					
		}
			
		
	}
}
?>