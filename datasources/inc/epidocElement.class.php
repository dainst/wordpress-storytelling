<?php
class epidocElement extends SimpleXMLElement {
	function validate() {
		// is TEI?
		
		if ($this->getName() != 'TEI') {
			echo "no TEI";
			return false;
		}
		
		$atts = $this->attributes('xml', true);
		
		// is epidoc?
		if ($atts['base'] != 'ex-epidoctemplate.xml') {
			echo "no EpiDoc";
			return false;
		}
		
		return true;

		
		
	}
}
//http://www.xmlfiles.com/examples/cd_catalog.xml
//http://edh-www.adw.uni-heidelberg.de/edh/inschrift/HD000015.xml
?>