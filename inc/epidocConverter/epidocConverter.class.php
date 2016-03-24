<?php
/**
 *
 * epidocConverter
 *
 * @version 1.5
 * 
 * @year 2016
 * 
 * @author Philipp Franck
 *
 * @desc
 * This is an abstract class, wich is used by both implementations. You can use it, if you want to select the best converter automatically.
 * 
 *
 * @tutorial
 *
 * $mode = 'saxon'; //'libxml', 'remote:saxon', 'remote:libxml'
 *
 * try {
 * 	$conv = epidocConverter::create($xmlData, $mode);
 * } catch (Exception $e) {
 * 	echo $e->getMessage();
 * }
 * 
 * @see epidocConverterSaxon.class.php and epidocConverterFallback.class.php for more hints
 * 
*/
/*

Copyright (C) 2015  Deutsches Archäologisches Institut

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
abstract class epidocConverter {
	
	public $version = '1.5';
	
	// position of xslt stylsheets & so on
	public $workingDir; // usually __DIR__
	public $xslFile;
	public $dtdPath = 'tei-epidoc.dtd'; //can be set to anywhere, but default is working directory
	
	public static $cssFilePath;
	public $cssFile;
	
	public $errors = array(); // tmp array for errors
	
	public $renderOptions	= array();
	public $renderOptionset = array(
		'apparatus-style' =>  	array(
				'description' =>  	"supported values are 'default' (generate apparatus from tei:div[@type='apparatus']) and 'ddbdp' (generate apparatus from tei:app, tei:subst, tei:choice, tei:hi etc. elements in the text)",
				'options' => 		array('default', 'ddbdp')
			),					
		'edition-type' =>		array(
				'description' =>  	"diplomatic' prints edition in uppercase, no restored, corrected, expanded characters, etc.)",
				'options' => 		array('interpretive', 'diplomatic')
			),
		'edn-structure' =>		array(
				'description' =>  	'',
				'options' => 		array('default', 'ddbdp', 'hgv', 'inslib', 'iospe', 'edh', 'edh-db', 'rib', 'sammelbuch', 'eagle', 'igcyr'),
			),
		'leiden-style' =>		array(
				'description' =>  	'These change minor variations in local Leiden usage; brackets for corrected text, display of previously read text, illegible characters, etc.',
				'options' => 		array('panciera', 'ddbdp', 'dohnicht', 'eagletxt', 'edh-itx', 'edh-names', 'edh-web ', 'ila', 'iospe', 'london', 'petrae', 'rib', 'sammelbuch', 'seg'),
			),
		'line-inc' =>			array(
				'description' =>  	'Show line number every ... line.',
				'options' => 		array(5, 10, 25, 20, 1, 2, 3, 4),
				'type' =>			'int'
			),
		'topNav' =>				array(
				'description' =>  	'',
				'options' => 		array('default', 'ddbdp'),
			),
		'verse-lines' =>		array(
				'description' =>  	'when a text of section of text is tagged using <lg> and <l> elements [instead of <ab>] then edition is formatted and numbered in verse lines rather than epigraphic lines',
				'options' => 		array('off', 'on')
			)
	);
	
	
	/**
	 * 
	 * Takes the Epidoc-Data and passes to the available XSLT Processor.
	 * 
	 * use this function if you want to select converter automatically:
	 * 
	 * @tutorial 
	 * $conv = epidocConverter::create($xmlData);
	 * This selects the SaxonProcessor if possible and if not the internal XSLT 1.0 Processor.
	 * 
	 * @param string $epidoc
	 * @param string $mode 'saxon' 'libxml' 'remote' 'remote:saxon' 'remote:libxml'
	 * @param assoc $settings allowed are all public variables of this class and all keys of the $renderOptions array (for example array('apiurl' =>  'http://myepidocservice'))
	 * @return epidocConverterFallback|epidocConverterSaxon
	 */
	static function create($epidoc = false, $mode = 'libxml', $settings  = array()) {

		@list($mode, $mode2) = explode(':', $mode);

		$file = dirname(__FILE__) . "/epidocConverter.$mode.class.php";
		
		if (file_exists($file)) {
			require_once($file);
		} else {
			throw new \Exception("$file does not exist.");
		}
		
		try {
			$class = "\\epidocConverter\\$mode";
			$conv = new $class('');
		} catch (Exception $e) {
			if ($mode != 'libxml') { // fallback to mode libxml
				$conv = epidocConverter::create($epidoc, 'libxml');
			}
			throw $e;
		}		

		// imports xslt options and other settings
		foreach ($settings as $key => $val) {
			if (property_exists($conv, $key)) {
				$conv->$key = $val;
			}
			
			if (isset($conv->renderOptionset[$key])) {
				$conv->setRenderOption($key, $val);
			}
			
		}
		
		if (!empty ($mode2) and ($mode == 'remote')) {
			$conv->apiurlArguments['mode'] = $mode2;
		}

		if (!empty($epidoc)) {
			$conv->set($epidoc);
		}
		

		return $conv;
	}
	
	/**
	 * 
	 * @param string $key
	 * @param string $val
	 * @throws \Exception when rende option is not registered
	 * @return boolean - true when render option value was registered, false if not
	 */
	function setRenderOption($key, $val) {
		if (!isset($this->renderOptionset[$key])) {
			throw new \Exception("Unknown render option: '$key'.");
		}
		
		$type = isset($this->renderOptionset[$key]['type']) ? $this->renderOptionset[$key]['type'] : 'string';
		$this->renderOptions[$key] = $val;
		settype($this->renderOptions[$key], $type);
		
		return in_array($val, $this->renderOptionset[$key]['options']);
	}
	
	/**
	 *
	 * import Epidoc Data
	 *
	 * @param string | simpleXmlElement $data
	 */
	function set($data) {
		if (empty($data)) {
			throw new \Exception('Empty String');
		}
		
		if ($data instanceof SimpleXMLElement) {
			$this->importStr($data->asXML());
		} else {
			$this->importStr((string) $data);
		}
	
		$this->raiseErrors();
	}
	
	/**
	 *
	 * import Epidoc in String fromat.
	 *
	 * @param unknown $str
	 */
	abstract function importStr($str);
	
	/**
	 * 
	 * before importing a String to epidoc, this makes a lot of tests and modifications with it if necessesary
	 * 
	 * @param unknown $str
	 * @throws Exception
	 */
	public function sanitizeStr($str) {		
		// make sure it is a string
		$str = (string) $str;
		
		// make sure it is not empty
		if (!$str) {
			throw new Exception('Empty XML String');
		}

		// correct dtd-path if necessary
		$this->dtdPath = ($this->dtdPath == 'tei-epidoc.dtd') ? $this->workingDir . '/' . $this->dtdPath : $this->dtdPath;
		$str = preg_replace('#(\<!DOCTYPE TEI.*?\>)#mi', '<!DOCTYPE TEI SYSTEM "' . $this->dtdPath . '">', $str);
		
		// correct TEI Version if necessary
		$str = str_ireplace(array('<TEI.2', '</TEI.2'), array('<TEI', '</TEI'), $str);
		
		// correct namespace if necessary and check if TEI Document
		$doc = new DOMDocument();
		$doc->loadXML($str, LIBXML_DTDLOAD);
		$tei = $doc->documentElement;
		if (strtoupper($tei->tagName) != 'TEI') {
			throw new Exception('no TEI (root Element is called >>' . $tei->tagName . '<<)');
		}
		$tei->setAttribute('xmlns', "http://www.tei-c.org/ns/1.0");
		$str = $doc->saveXML();
		
		//echo "<textarea style='width:100%'>$str</textarea><hr>";
		
		return $str;
	}
	
	/**
	 * Raise Exception if XML Erros collected
	 *
	 * @param string $return - should errors be returned or raised as exception? defaults to true
	 * @throws Exception
	 */
	abstract function raiseErrors($return = false);
	

	
	/**
	 *
	 * is everything oK?
	 *
	 * @throws Exception if not
	 * @return boolean
	 */
	abstract function status();
	

	/**
	 * just print the stylesheet
	 * @return string
	 */
	function getStylesheet() {
		return file_get_contents($this->workingDir . '/' . $this->cssFile);
	}
	
	/**
	 * converts the imported Data to String using the simple stylsheets
	 * @param $all - return full html page or just body; default: false
	 * @throws Exception
	 * @return String
	 */
	abstract function convert($all = false);
	
	
	/**
	 * private function to distinguish between output as fuill html or just bodypart
	 * @param $result
	 * @param boolean $all
	 */
	private function _returnResult($result, $all) {
		;
	}
}
?>