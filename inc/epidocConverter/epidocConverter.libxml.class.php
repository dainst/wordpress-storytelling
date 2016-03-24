<?php
/**
 * 
 * epidocConverter - libxml Version
 * 
 * @version 1.0
 * 
 * @year 2015 
 * 
 * @author Philipp Franck
 * 
 * @desc
 * This class uses the built in XSLT 1.0 Processor of PHP to convert Epidoc-XML-Data via XSLT into html-data
 * 
 * 
 * @tutorial
 * 
 * try {
 * 	$s = new epidocConverter\libxml(file_get_contents("/myepidocfiles/HD006705.xml"));
 * 	$xml = $s->convert();
 * 	echo '<div class="myepidocbox">' .  $xml->asXML() . '</div>';
 * } catch (Exception $e) {
 * 	echo $e->getMessage();
 * }
 * 
 * 
 * @see
 * http://php.net/manual/en/class.xsltprocessor.php
 *
 *
 * @requirements
 * Libxml >= 2.7.8 (as of PHP >= 5.4.0) 
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

namespace epidocConverter {

	require_once('epidocConverter.class.php');
	
	class libxml extends \epidocConverter {
		
		public $workingDir = ''; 
		public $xslFile = "xslShort/epidocShort.xsl"; // relative to this files' position !
		public static $cssFilePath = "xslShort/epidoc.css";
		
		public $proc;
		
		public $xmlDom;
		
		public $renderOptions = array();
		
		/**
		 * 
		 * Constructor: set up XSLT Processor and import Epidoc Data if given
		 * 
		 * @param string | simpleXmlElement $data - the epidoc
		 */
		function __construct($data = false, $noException  = false) {
			
			$this->workingDir = __DIR__;
			$this->cssFile = self::$cssFilePath;
			
			if (!class_exists('\XSLTProcessor')) {
				throw new \Exception('PHP XSLT Extension not installed');
			}
			
			if(LIBXML_VERSION < 20708) {
				throw new \Exception('libxml version too old - you need at least version 2.7.8 (as shipped with PHP 5.4.0)');
			}
			
			$this->proc = new \XSLTProcessor();
			$this->proc->registerPHPFunctions(); // that's why we love php
			
			if ($data) {
				$this->set($data);
			}
		}
		
		
		/**
		 * Raise Exception if XML Erros collected
		 * 
		 * @param string $return
		 * @throws Exception
		 */
		function raiseErrors($return = false) {
			$err = '';
			foreach ($this->errors as $error) {
				$err .= "<li>Libxml error: {$error}</li>\n";
			}
			if ($err) {
				$errorText = "Errors:<ul>$err\n</ul>\n";
				if ($return) {
					return $errorText; 
				} else {
					throw new \Exception($errorText);
				}
			}
			restore_error_handler();
		}
		
		/**
		 *
		 * Error Collector for XML Errors
		 *
		 * @param unknown $errno
		 * @param unknown $errstr
		 * @param unknown $errfile
		 * @param unknown $errline
		 */
		function collectErrors($errno, $errstr, $errfile, $errline) {
			if ($errno==E_WARNING && (substr_count($errstr,"DOMDocument::loadXML()")>0)) {
				$this->errors[] = "Dom Error: $errfile line $errfile: \t $errstr";
			} else if ($errno==E_WARNING && (substr_count($errstr,"SimpleXMLElement::")>0)) {
				$this->errors[] = "SimpleXml Error: $errfile line $errfile: \t $errstr";
			} else {
				$this->errors[] = "Unknown Error: $errno <br> $errstr <br> $errfile <br> $errline";
			}
		}
	
		
		/**
		 * 
		 * import Epidoc in String fromat.
		 * 
		 * @param unknown $str
		 */
		function importStr($str) {
			
			$str = $this->sanitizeStr($str);
			
			set_error_handler(array($this, 'collectErrors'));
			$this->xmlDom = new \DOMDocument();
			if (defined('LIBXML_HTML_NOIMPLIED') and defined('LIBXML_HTML_NODEFDTD')) {
				$this->xmlDom->loadXML($str, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_DTDLOAD);
			} else {
				$this->xmlDom->loadXML($str);
			}
			
			/*
			 
			$tei = $this->xmlDom->documentElement;
			$tei->setAttribute('xmlns', "http://www.tei-c.org/ns/1.0");
			*/
			$this->xmlDom->normalize();
	
		}
		
		/**
		 * 
		 * is everything oK?
		 * 
		 * @throws Exception
		 * @return boolean
		 */
		function status() {
			if (!class_exists('XSLTProcessor')) {
				throw new \Exception('XSLTProcessor not present');
			}
			return 'libxml mode';
		}
		
		/**
		 * converts the imported Data to String using the simple stylsheets
		 * @param $all - return full html page or just body; default: false
		 * @throws Exception
		 * @return String
		 */
		function convert($all = false) {
			
			set_error_handler(array($this, 'collectErrors'));
			
			// does xslt stylesheet exist?
			if (!file_exists($this->workingDir . '/' . $this->xslFile)) {
				throw new \Exception("File >>{$this->workingDir}/{$this->xslFile}<< does not exist." );
			}
	
			$xsl = new \DOMDocument();
			$xsl->substituteEntities = true;
			$xsl->load($this->workingDir . '/' . $this->xslFile);
		
			// let the magic happen
			$this->proc->importStylesheet($xsl);	
			$doc = $this->proc->transformToDoc($this->xmlDom);
	
			// libxml errors?
			$this->raiseErrors();
			
			// normalize
			$doc->normalize();
			$doc->normalizeDocument();
	
			// output
			$result = ($doc->saveHTML());
			
			
			return $this->_returnResult($result, $all);
	
	
		}
		
		/**
		 * private function to distinguish between output as full html or just bodypart
		 * @param $result
		 * @param boolean $all
		 */
		private function _returnResult($result, $all) {
			
			if($result) {
				//file_put_contents($this->workingDir . '/test.html', $result);
	
				
				if (!$all) {
					return trim($result);
				}
				
				return "<!DOCTYPE HTML><html><head><title>Epidoc</title><meta charset='utf-8'><style>" . file_get_contents($this->workingDir . '/' . $this->cssFile) . "</style></head><body>$result</body></html>";
				
		
			} else {
				throw new \Exception("Conversion result is null");
			}
			
		}
	}
}
?>