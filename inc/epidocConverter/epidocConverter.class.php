<?php 
/**
 * 
 * epidocConverter
 * 
 * @version 1.0
 * @year 2015 
 * @author Philipp Franck
 * 
 * @desc
 * This class employs the PHP API of the Saxon/C processor to convert Epidoc-XML-Data via XSLT into html-data
 * 
 * It takes the Epidoc-Data as String or as SimpleXMLElement and returns a SimpleXMLElement, representing the
 * body of the rendered html.
 * 
 * @tutorial
 * 
 * try {
 * 	$s = new epidocConverter(file_get_contents(("myepidocfiles/HD006705.xml")));
 * 	$xml = $s->convert();
 * 	echo '<div class='myepidocbox'>' .  $xml->asXML() . '</div>';
 * } catch (Exception $e) {
 * 	echo $e->getMessage();
 * }
 * 
 * 
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


class epidocConverter {
	
	// position of xslt stylsheets and doctype dtd
	public $xslFile = "xsl/start-edition.xsl";	
	public $dtdPath = 'tei-epidoc.dtd';
	
	// the processor
	public $saxon = NULL;
	
	// the data in xdm format
	public $xdm = null;

	/**
	 * 
	 * @param string | SimpleXMLElement $data
	 * @throws Exception
	 */
	function __construct($data = false) {
		// check for saxon
		
		if (class_exists('SaxonProcessor'))  {
						
			$this->createSaxon();
            
			if ($data) {
				$this->set($data);
			}
			
		} else {
			throw new Exception('Saxon XSLT Processor PHP Module not available.');
		}
		
	}
	
	/**
	 * creates an instance of the SaxonProcessor
	 * can be called to reset the error log and stuff
	 */
	function createSaxon() {
		//On Windows we recommend setting the cwd using the overloaded constructor
		//because there remains an issue with building Saxon/C with PHP when using the function VCWD_GETCWD. i.e. $proc = new SaxonProcessor('C://www/html//trax//');
		$this->saxon = new SaxonProcessor();
	}
	
	/**
	 * 
	 * takes the epidoc data as SimpleXMLElement or string and imports it 
	 * 
	 * @param SimpleXMLElement | string $data
	 */
	function set($data) {
		if ($data instanceof SimpleXMLElement) {
			$this->importStr($data->asXML());
		} else {
			$this->importStr((string) $data);
		}
		
		$this->raiseErrors();
	}
	
	/**
	 * 
	 * Looks for Errors registered by the Saxon Processor and raises Exception
	 * 
	 * @throws exception
	 */
	function raiseErrors($return = false) {
		
		$error = '';
		for ($i = 0; $i < $this->saxon->getExceptionCount(); $i++) {
			$error .= '<li>' . $this->saxon->getErrorCode($i) . ': ' . $this->saxon->getErrorMessage($i) . "</li>\n";
		}

		if ($error) {
			$errorText = "Saxon processor found some XML errors: <ul>\n$error\n</ul>";
			if ($return) {
				return $errorText; 
			} else {
				throw new exception($errorText);
			}
		}
		
		return false;
	}
	
	/**
	 * imports a string, wich is assumed to be epidoc
	 * makes a lot of tests and modifications if necessesary
	 * @param string $str
	 * @return string | bool
	 * @throws Exception
	 */
	function importStr(/*string */ $str) {
		
		// make sure it is a string
		$str = (string) $str;
		
		// make sure it is not empty
		if (!$str) {
			throw new Exception('Empty XML String');
		}
		
		// correct dtd-path if necessary
		$str = preg_replace('#\<!DOCTYPE TEI.*\>#m', '<!DOCTYPE TEI SYSTEM "' . $this->dtdPath . '">', $str);
		
		// correct TEI Version if necessary
		$str = str_ireplace(array('<TEI.2', '</TEI.2'), array('<TEI', '</TEI'), $str);
		
		// correct namespace if necessary and check if TEI Document
		$doc = new DOMDocument();
		$doc->loadXML($str);
		$tei = $doc->documentElement;
		if (strtoupper($tei->tagName) != 'TEI') {
			throw new Exception('no TEI');
		} 
		$tei->setAttribute('xmlns', "http://www.tei-c.org/ns/1.0");
		$str = $doc->saveXML();

		//echo "<textarea style='width:100%'>$str</textarea><hr>";
		
		// try to import
		$this->xdm = $this->saxon->parseString($str);
		
		// if at first you don't succeed, you can dust it off and try again 
		if ($error = $this->raiseErrors(true)) {
		
			if (strpos($error, 'Content is not allowed in prolog')) {
				$str = preg_replace('#[^\\x20-\\x7e\\x0A]#', '', $str);
				$this->createSaxon(); // to reset error log
				$this->importStr($str);
			} else {
				throw new Exception('e:' . $error);
			}
		}
		
		return true;

	}
	
	
	/**
	 * checks if the SaxonProcessor is still available - raises exception if not or Version Information
	 * @throws Exception
	 * @return string
	 */
	function status() {
		if (class_exists('SaxonProcessor'))  {
			return "Saxon Processor: " . $this->saxon->version();
		} else {
			throw new Exception('Saxon XSLT Processor PHP Module not available.');
		}
	}
	
	/**
	 * converts the imprted Data to SimpleXMLElement using the stylsheets
	 * @throws Exception
	 */
	function convert() {

		// does xslt stylesheet exist?
		if (file_exists(!$this->xslFile)) {
			throw new Exception("File $xslFile does not exist.");
		}
		
		$this->saxon->setSourceValue($this->xdm);
		$this->saxon->setStylesheetFile($this->xslFile);
		
		$result = $this->saxon->transformToString();
		if($result != null) {
			$simpleXml = new SimpleXMLElement($result);
			
			file_put_contents('test.html', $simpleXml->asXML());
			
			return $simpleXml->body;
		} else {
			throw new Exception("Conversion result is null");
		}
		$this->saxon->clearParameters();
		$this->saxon->clearProperties();
	}
	
	
	
}
?>