<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: Wikipedia
 * @link 		http://www.europeana.eu/
 * @author 		Philipp Franck
 * 
 * Status: Beta
 * 
 *  
 * 
 */
namespace esa_datasource {
	/**
	 * 
	 * @author Philipp Franck
	 *
	 */
	class wiki extends abstract_datasource {
		
			public $pagination = false;
			
			
		
			//public $apiurl = "https://en.wikipedia.org/w/api.php?action=query&format=json&list=allimages&titles=%s";
			function api_search_url($query, $params = array()) {
				$query = urlencode($query);				
				return "https://en.wikipedia.org/w/api.php?action=query&prop=extracts|categories|links|info&inprop=url&pllimit=max&exintro=&format=json&redirects=&titles=$query";
			}
			
			function api_single_url($id) {				
				$id = urlencode($id);
				return "https://en.wikipedia.org/w/api.php?action=query&prop=extracts|info&inprop=url&exintro=&format=json&redirects=&titles=$id";
			}

			function api_record_url($id) {
				return "https://en.wikipedia.org/?curid=$id";
			}
			
			function api_url_parser($string) {
				if (preg_match('#https?\:\/\/en\.wikipedia\.org\/wiki\/(.*)#', $string, $match)) {
					return $this->api_single_url($match[1]);
				}
			}
			
			public $info = "Use the field above to search for articles in the <a href='https://en.wikipedia.org/' target='_blank'>english Wikipedia</a> or insert a link to page from the english wikipedia.";    
			public $title = 'Wikipedia';
			
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
				$title = urlencode($page->title);
				$url = "https://en.wikipedia.org/w/api.php?action=query&prop=imageinfo&format=json&iiprop=url|size|mediatype|extmetadata&iiurlwidth=150&titles=$title&generator=images&redirects=";
					
				$imageData = $this->_fetch_external_data($url);
				$imageData = json_decode($imageData);
				
				//echo "<hr><pre>".print_r($url,1)."</pre>";
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
					
				return new \esa_item('wiki', $page->title, $html, $page->fullurl);
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