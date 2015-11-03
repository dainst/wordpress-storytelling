<?php
namespace esa_item {
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