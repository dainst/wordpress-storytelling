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
				$this->table[$key] = $value . ', ' . $this->table[$key];
			}
			$this->table[$key] = $value;
		}
		
		function addText($key, $value) {
			if (isset($this->table[$key])) {
				$this->table[$key] = $value . '<br>' . $this->table[$key];
			}
			$this->table[$key] = $value;
		}
		
		function addImages($image) {
			if ($image instanceof image) {
				return $this->images[] = $image;
			}
			if (is_array($image)) {
				return $this->images[] = new image($image);
			}
		}
		/**
		 * transforms an array to a esa_item html
		 */
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
					foreach($this->images as $image)  {
						if ($image instanceof \esa_item\image) { //vorrübergehend beide Lösungen akzeptiren
							$html .= $image->render();
								
						} else {
							$html .= "<div class='esa_item_main_image' style='background-image:url(\"{$image->url}\")' title='{$image->title}'>&nbsp;</div>";
							$html .= "<div class='esa_item_subtext'>{$image->text}</div>";
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
					$value = trim($value);
					if ($value) {
						$label = $this->_label($field);
						$html .= "<li><strong>{$label}: </strong>{$value}</li>";
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
					'origDate' => 'Date'
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
			
			$image = "<div class='esa_item_main_image' style='background-image:url(\"{$this->url}\")' title='{$this->title}'>&nbsp;</div>";
			if(($this->type == 'BITMAP') and ($this->fullres)) {
				$image = "<a href='{$this->fullres}' title='{$this->title}' class='thickbox'>$image</a>";
			} 
			$image = "$image<div class='esa_item_subtext'>{$this->text}</div>";

			switch($this->type) {
				case 'BITMAP':$html = $image; break;
				case 'AUDIO': $html = "<audio controls><source src='{$this->url}' type='{$this->mime}'>$drlink</audio><div class='esa_item_subtext'>{$this->text}</div>"; break;
				case 'VIDEO': $html = "<video controls><source src='{$this->url}' type='{$this->mime}'>$drlink</video><div class='esa_item_subtext'>{$this->text}</div>"; break;
				case 'DRAWING': break;
			}
				
			return $html;
		}
	
	}
}
?>