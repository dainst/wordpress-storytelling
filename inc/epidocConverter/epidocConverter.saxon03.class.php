<?php 
/**
 * 
 * epidocConverter - Saxon/C Version for Saxon 0.3
 * 
 * @version 1.0
 * 
 * @year 2015 
 * 
 * @author Philipp Franck
 * 
 * @desc
 * This is the older Version of epidocConverter.saxon.class.php wich was build for Saxon/C 0.3.
 * The Saxon/C changed a lot of things, so this class is not usable anymore. I include it in the package anyway,
 * in case someone can use it.
 * 
 * 
 * 
 * This class employs the PHP API of the Saxon/C processor to convert Epidoc-XML-Data via XSLT into html-data
 * 
 * It takes the Epidoc-Data as String or as SimpleXMLElement and returns a String, representing the
 * body of the rendered html.
 * 
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
	
	class saxon extends \epidocConverter {
		
		// position of xslt stylsheets and doctype dtd
		public $xslFile = "xsl/start-edition.xsl"; // relative to this files' position !
		public $dtdPath = 'tei-epidoc.dtd'; //can be set to anywhere, but default is working directory
		public $workingDir = ''; //set in __construct, but is public value in case you want to change (see footnote 1)
		public $cssFile = "xsl/global.css";
		
		// the processor
		public $saxon = NULL;
		
		// the data in xdm format
		public $xdm = null;
	
		/**
		 * 
		 * @param string | SimpleXMLElement $data
		 * @param noException | if true this does not throw exception if saxon not present (so you can use it for getting stylesheet etc.)
		 * @throws Exception
		 */
		function __construct($data = false, $noException  = false) {
			
			// set up working dir (see footnote 1)
			$this->workingDir = __DIR__;
			$this->dtdPath = $this->workingDir . '/' . $this->dtdPath;
			
			// check for saxon	
			if (class_exists('SaxonProcessor')) {
							
				$this->createSaxon();
	            
				if ($data) {
					$this->set($data);
				}
				
			} else {
				if (!$noException) {
					throw new \Exception('Saxon XSLT Processor PHP Module not available.');
				}
			}
			
		}
		
		/**
		 * creates an instance of the SaxonProcessor
		 * can be called to reset the error log and stuff
		 */
		function createSaxon() {
			//On Windows we recommend setting the cwd using the overloaded constructor
			//because there remains an issue with building Saxon/C with PHP when using the function VCWD_GETCWD. i.e. $proc = new SaxonProcessor('C://www/html//trax//');
			$this->saxon = new \SaxonProcessor($this->workingDir);
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
					throw new \Exception($errorText);
				}
			}
			
			return false;
		}
		
		
		
		/**
		 * imports a string, wich is assumed to be epidoc
		 * makes a lot of tests and modifications if necessesary -> most of them are in sanitizeStr, because the fallBack Processor uses them as well
		 * @param string $str
		 * @return string | bool
		 * @throws Exception
		 */
		function importStr(/*string */ $str) {
			
			$str = $this->sanitizeStr($str);
			
			// try to import
			$this->xdm = $this->saxon->parseString($str);
			
			// if at first you don't succeed, you can dust it off and try again 
			if ($error = $this->raiseErrors(true)) {
			
				if (strpos($error, 'Content is not allowed in prolog')) {
					$str = preg_replace('#[^\\x20-\\x7e\\x0A]#', '', $str);
					$this->createSaxon(); // to reset error log
					$this->importStr($str);
				} else {
					throw new \Exception('Import Error: ' . $error);
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
				throw new \Exception('Saxon XSLT Processor PHP Module not available.');
			}
		}
		
		/**
		 * converts the imported Data to String or SimpleXMLElement using the stylsheets
		 * @param $all - return full html page or just body; default: false
		 * @throws Exception
		 * @return String
		 */
		function convert($all = false) {
	
			// does xslt stylesheet exist?
			if (!file_exists($this->workingDir . '/' . $this->xslFile)) {
				throw new \Exception("File >>{$this->xslFile}<< does not exist." );
			}
			
			$this->saxon->setSourceValue($this->xdm);
			$this->saxon->setStylesheetFile($this->xslFile);
			
			// notice! because of bug in Saxon/X API 0.3 this does not work with string values			
			foreach ($this->renderOptions as $name => $value) {
				$this->saxon->setParameter($name, $this->saxon->createXdmValue($value));
			}
			
			$result = $this->saxon->transformToString();
			
			// tidy up
			$this->raiseErrors();
			$this->saxon->clearParameters();
			$this->saxon->clearProperties();
			
			return $this->returnResult($result, $all);
	
	
		}
		
		function returnResult($result, $all) {
			if($result) {
				//file_put_contents($this->workingDir . '/test.html', $result);
				
				if ($all) {
					return trim($result);
				}
				
				$dom = new \DOMDocument();
				$dom->loadXML($result);
				
				$bodies = $dom->getElementsByTagName('body');
	
				foreach ($bodies as $body) {
					return trim(str_replace(array('<body>', '</body>'), '', $dom->saveHTML($body)));
				}
					
			} else {
				throw new \Exception("Conversion result is null");
			}
		}
		
	
	}
}

/**
 * 
 * Footnotes
 * 
 * 1) the working dir
 * There is a thing in the SAXON/C API. As you can see in php_saxon.cpp, l. 69ff on linux it is using VCWD_GETCWD  to get the working directory,
 * and gives it in xsltProcessor.cpp, l. 242 to the Java-powered Saxon Processor. Appereantly that one searches in this working directory for the 
 * stylesheets. Therefore it throws an error, if you want to give a absolute path as stylesheets. They implented a way for windows users to define the
 * working directory manually, because there VCWD_GETCWD dows not work. We use this (on linux) to define the right working directory. 
 * 
 * 
 * 
 * 
 */

?>