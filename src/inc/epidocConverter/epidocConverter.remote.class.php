<?php 
/**
 * 
 * epidocConverter - Remote Version
 * 
 * @version 1.0
 * 
 * @year 2016
 * 
 * @author Philipp Franck
 * 
 * @desc
 * Sender! For a epidocConverterRemote-Solution
 * 
 * @tutorial
 * todo
 * 
 * 
 * 
 *
 */
/*

Copyright (C) 2015, 2016  Deutsches Archäologisches Institut

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
	
	class remote extends \epidocConverter {
		public $workingDir = '';
		
		public $theStr;
		
		public $apiurl = '';
		public $apiurlArguments = array(
			'mode' => 'saxon'
		);
		
		
		function __construct($data = false) {
			if ($data) {
				$this->set($data);
			}
		}
		
		/**
		 *
		 * import Epidoc in String fromat.
		 *
		 * @param unknown $str
		 */
		function importStr($str) {
			$this->theStr = $str;
		}
		
		function raiseErrors($return = false) {
		}
	
		
		function status() {
			return 'Remote (' . ($this->apiurlArguments['mode']  ? $this->apiurlArguments['mode'] : 'auto') . ')';
		}
		
		function convert($all = false) {
			
			if (!$this->apiurl) {
				throw new \Exception('No remote URL!');
			}

			
			$this->apiurlArguments['epidoc'] = $this->theStr;
			$this->apiurlArguments['returnAll'] = $all;
	
			if(
				!function_exists("curl_init") or
				!function_exists("curl_setopt") or
				!function_exists("curl_exec") or
				!function_exists("curl_close")
			){
				throw new \Exception('CURL missing!');
			}
	
			$ch = curl_init();
			
			$http_headers = array(
			 		"Accept: application/json",
					"Connection: close",                    // Disable Keep-Alive
					"Expect:",                              // Disable "100 Continue" server response
					"Content-Type: application/json"        // Content Type json
			);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			curl_setopt($ch, CURLOPT_VERBOSE, true);   	// Verbose mode for diagnostics
			curl_setopt($ch, CURLOPT_URL, $this->apiurl . '?' . http_build_query($this->apiurlArguments));
			//curl_setopt($ch, CURLOPT_POST, true);
			//curl_setopt($ch, CURLOPT_POSTFIELDS, );
	
			$response = curl_exec($ch);
	
			if ($response === false) {
				throw new \Exception('Curl error: (' . curl_errno($ch) . ') ' . curl_error($ch) . ' | ' . $this->apiurl);
			}
	
		
			curl_close($ch);
			$responseO = json_decode($response);
	
			if ($responseO and $responseO->success) {
				return $responseO->data;
			} else {
				throw new \Exception($responseO ? $responseO->message : 'No valid Response from ' . $this->apiurl . '! <br><b>POST</b><pre>' . htmlspecialchars(print_r($this->apiurlArguments,1)) . '</pre><b>Query</b><pre>' . http_build_query($this->apiurlArguments) . '</pre><b>Response:</b><pre>' . htmlspecialchars($response) . '</pre>');
			}
			
		}
		
		
		function getStylesheet() {
			$modes = explode(':', $this->apiurlArguments['mode']);
			$mode = array_pop($modes);
			$mode = ($mode == 'remote') ? 'libxml' : $mode;
			
			$file = dirname(__FILE__) . "/epidocConverter.$mode.class.php";
		
			if (file_exists($file)) {
				require_once($file);
			} else {
				throw new \Exception("$file does not exist.");
			}
		
			$class = "\\epidocConverter\\$mode";
			
			return file_get_contents($this->workingDir . '/' . $class::$cssFilePath);

		}

	}

}
	


?>