<?php
/**
 *
 * epidocConverter - TestSuite
 *
 * @version 1.0
 *
 * @year 2016
 *
 * @author Philipp Franck
 *
 * @desc
 * UI to test the converter. Open in Browser.
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
include ('epidocConverter.saxon.class.php');
	
$file = (isset($_GET['file'])) ? $_GET['file'] : '';
$mode = (isset($_GET['mode'])) ? $_GET['mode'] : '';
$remoteAddress = (isset($_GET['remoteAddress'])) ? $_GET['remoteAddress'] : '';
$remote = (isset($_GET['remote']) and $_GET['remote']);

$ownServer = 'http://' . $_SERVER["SERVER_NAME"] . dirname($_SERVER["SCRIPT_NAME"]) . '/remoteServer.php';

$message = '';
$res = '';
$xml = '';
$error = false;

if ($file) {

	$xml = file_get_contents($file);

	require_once('epidocConverter.class.php');
   
	try {
		
		if ($remote) {
			$message = "Remote";
			$converter = epidocConverter::create($xml, 'remote');
			$converter->apiurl = $remoteAddress;
			$converter->apiurlArguments['mode'] = $mode;
		} else {
			$converter = epidocConverter::create($xml, $mode);
		}
		
   		//
   		//$converter = new epidocConverterRemote($xml, $mode);
   		//$converter->apiurl = 'http://195.37.232.186/eagle/wp-content/plugins/eagle-storytelling/inc/epidocConverter/remoteServer.php';
   		$message = "Processor: " . (get_class($converter));
		
   		$res = $converter->convert();
		
		$styles = $converter->getStylesheet();
		
	} catch (Exception $e) {
		$res = '';
		$error = $e->getMessage();
		$message = "Error";
	}

}
?>
<html>
	<head>
	<title><?php echo $file  .  ' | ' . $mode; ?></title>
	<meta charset="utf-8">
	<style><?php echo $styles ?></style>
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.9.1/styles/default.min.css">
	<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
  	<script src="//code.jquery.com/jquery-1.10.2.js"></script>
  	<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/8.9.1/highlight.min.js"></script>
	<script>
		$(document).ready(function() {
		  $('code').each(function(i, block) {
		    hljs.highlightBlock(block);
		  });
		  $( "#tabs" ).tabs({
			  active: <?php echo $error ? 3 : ($res ? 1 : 0); ?>
		  });
		  $( "#tabs2" ).tabs({});
		});
	</script>

</head>


<body>

	
</div>

	<div id='tabs2' style="height:100%; width: 49%; float: left; overflow: auto; border: 1px solid black; padding: 2px;">
		<ul>
			<li><a href="#tabs2-0">Epidoc</a></li>
		</ul>
		<div id='#tabs2-0'>
			<code class="xml" style="margin:0px; white-space: pre"><?php echo htmlspecialchars($xml); ?></code>
		</div>
	</div>

	
	
	<div style="height:100%; width: 49%; float: right; overflow: auto; border: 1px solid black; padding: 2px" id='tabs'>
		<ul>
	    	<li><a href="#tabs-0">Query</a></li>
	    	<?php if ($res) { ?>
	    		<li><a href="#tabs-1">Result</a></li>
    			<li><a href="#tabs-2">Result Source Code</a></li>
    		<?php } ?>	
    		<?php if ($error) { ?>
    			<li><a href="#tabs-3">Error</a></li>
    		<?php } ?>
		</ul>
		
		<div id="tabs-0">
			<h1>Epidoc Converter Testing Suite</h1>
			<p><?php echo $message; ?></p>
			<form action="testSuite.php">
				Epidoc Data:
				<input type="text" value="<?php echo $file ?>" name="file" placeholder="File path or URI to epidoc file" />
				<br>
				Renderer:
				<select size="1" name="mode" id="select_mode">
					<option value='saxon'  <?php echo $mode == 'saxon' ? "selected='selected'" :'' ?>>Saxon/C</option>
					<option value='libxml'<?php echo $mode == 'libxml' ? "selected='selected'" :'' ?>>libxml (fallback)</option>
				</select>
				<br>
				<input type="checkbox" <?php echo $remote ? "checked='checked'" : '' ?> name="remote" id="chk_remote"><label for="chk_remote">Render remotely</label>;
				Remote Address: <input type="text" value="<?php echo $ownServer ?>" name="remoteAddress" placeholder="remote adress" />
				<br>
				<input type="submit" style="border: 1px solid silver;" value="Submit" />
			</form>
			
			<p style='font-size: 75%'>
				epidocConverter, Version 1.1<br>
				<br>
				written by Philipp Franck<br>
				<br>
				Copyright (C) 2015, 2016  Deutsches Archäologisches Institut<br>
				<br>
				This program is free software; you can redistribute it and/or
				modify it under the terms of the GNU General Public License
				as published by the Free Software Foundation; either version 2
				of the License, or (at your option) any later version.<br>
				<br>
				This program is distributed in the hope that it will be useful,
				but WITHOUT ANY WARRANTY; without even the implied warranty of
				MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
				GNU General Public License for more details.<br>
				<br>
				You should have received a copy of the GNU General Public License
				along with this program; if not, write to the Free Software
				Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
			</p>
			
			
		</div>
		
		<div id="tabs-1">
			<?php echo $res; ?>
		</div>
		
		<div id="tabs-2">
			<code style='white-space: pre'><?php echo htmlspecialchars($res); ?></code>
		</div>	
		
		<div id="tabs-3">
			<?php echo $error ?>
		</div>	
	</div>

</body>
</html>