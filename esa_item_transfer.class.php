<?php
namespace esa_item {
	
	class data {
		public $title = '';
		public $url = '';
		public $images = array();
		public $table = array();
		public $text = array();
		
		
		function addTable($key, $value) {
			if (isset($this->table[$key])) {
				return $this->table[$key] = $this->table[$key] . ', ' . $value;
			}
			return $this->table[$key] = $value;
		}
		
		function addText($key, $value) {
			if (isset($this->text[$key])) {
				return $this->text[$key] = $this->text[$key] . '<br>' . $value;
			}
			return $this->text[$key] = $value;
		}
		
		function addImages($image) {
			if ($image instanceof image) {
				return $this->images[] = $image;
			}
			if (is_array($image)) {
				return $this->images[] = new image($image);
			}
			if (is_string($image)) {
				return $this->images[] = new image(array('url' => $image, 'fullres' => $image));
			}
		}

		function render() {
				
			if (count($this->images) || count($this->text)) {
				$html  = "<div class='esa_item_left_column_max_left'>";
		
				if (count($this->text)) {
					foreach ($this->text as $type => $text) {
						if ($text) {
							$html .= "<div class='esa_item_text {$type}'>$text</div>";
						}
					}
				}
		
				if (count($this->images)) {
					$i = 0;
					foreach($this->images as $image)  {
						if ($image instanceof \esa_item\image) {
							$html .= $image->render();
							$i++;
							$html .= (($i % 4) == 0) ? "<div class='esa_item_divider'>&nbsp;</div>" : '';
						} 
					}
				}
		
				$html .= "</div>";
				$html .= "<div class='esa_item_right_column_max_left'>";
			} else {
				$html = "<div class='esa_item_single_column'>";
			}
				
				
			$html .= "<h4>{$this->title}</h4><br>";
				
			if (count($this->table)) {
				$html .= "<ul class='datatable'>";
				foreach ($this->table as $field => $value) {
					$value = (is_array($value)) ? implode(', ', $value) : trim($value);
					if ($value) {
						$label = $this->_label($field);
						$label = $label ? "<strong>{$label}: </strong>" : '';
						$html .= "<li>$label $value</li>";
						//$html .='<textarea>' . print_r($value,1) . "</textarea>";
					}
				}
				$html .= "</ul>";
			}
		
			$html .= "</div>";
				
			return $html;
		}
		
		
		private function _label($of) {
			$labels = array(
					'objectType' => 'Type',
					'repositoryname' => 'Repository',
					'material' => 'Material',
					'tmid' => 'Trismegistos-Id',
					'artifactType' => 'Artifact Type',
					'objectType2' => 'Type',
					'transcription' => 'Transcription',
					'provider' => 'Content Provider',
					'ancientFindSpot' => 'Ancient find spot',
					'modernFindSpot' =>  'Modern find spot',
					'origDate' => 'Date',
					'ImageDescription' => 'Description',
					'description' => 'Description',
					'DateTime' => "Created at",
					'place_category' => 'Place Category'
			);
		
			return (isset($labels[$of])) ? $labels[$of] : $of;
		}
	}
	
	class image {
		public $url = '';
		public $fullres = '';
		public $type = 'BITMAP';
		public $mime = '';
		public $title = '';
		public $text = '';
	
		/**
		 * create like
		 * new \esa_item\image(array(
		 *		'url' => (string) $thumbnail,
		 *		'title' => (string) $title,
		 *		'text'=> (string)  $text
		 *	));
		 * 
		 * @param unknown $data
		 */
		public function __construct($data) {
			foreach ($data as $att => $val) {
				$this->$att = $val;
			}
				
			if (!isset($this->title)) {
				$this->title = $this->url;
			}
				
		}
	
		public function render() {

			
			$drlink = "<a href='{$this->url}' target='_blank'>{$this->title}</a>";
			
			$text = $this->text ? "<div class='esa_item_subtext'>{$this->text}</div>" : '';
			
			$this->title = str_replace(array('"', "'"), '', $this->title);
			
			switch($this->type) {
				
				case 'DRAWING':
					$class = 'esa_item_svg';
				case 'BITMAP':
				
				case 'IMAGE':
					$drurl = ($this->fullres) ? $this->fullres : $this->url;
					$encurl = $drurl;
					$image = "<div class='esa_item_main_image' style='background-image:url(\"{$this->url}\")' title='{$this->title}'>&nbsp;</div>";
					if($this->fullres or ($this->type == 'DRAWING')) {
						$image = "<a href='$encurl' title='{$this->title}' class='thickbox'>$image<img class='esa_item_fullres $class' src='' data-fullsize='$drurl' alt='{$this->title}' /></a>";
					} else {
						$image = "$image<img class='esa_item_fullres' src='' data-fullsize='$drurl' alt='{$this->title}' />";
					}
					$html = $image; 
				break;
				
				case 'AUDIO': 
					$html = "<audio controls class='esa_item_multimedia'><source src='{$this->url}' type='{$this->mime}'>$drlink</audio>"; 
				break;
				
				case 'VIDEO': 
					$html = 
						"<video controls class='esa_item_multimedia'>
							<source src='{$this->url}' type='{$this->mime}'>$drlink
						</video>";
				break;
				
				case 'DOWNLOAD':
					$html = "<a target='_blank' href='{$this->fullres}'><div class='esa_item_main_image' style='background-image:url(\"{$this->url}\")' title='{$this->title}'>&nbsp;</div></a>";
				break;
				
				case 'MAP':
					
					$shape = ($this->shape) ? "data-shape='" . json_encode($this->shape) .  "'" : '';
					$id = (isset($this->id)) ? $this->id : md5(implode('|', array($this->shape, $this->marker[0], $this->marker[1])));
					$html = "<div class='esa_item_map' id='esa_item_map-{$id}' data-latitude='{$this->marker[0]}' data-longitude='{$this->marker[1]}' $shape>&nbsp;</div>";
					
				break;

			}
				
			return "<div class='esa_item_media_box $class'>$html $text</div>";
		}
	
	}
}
?>