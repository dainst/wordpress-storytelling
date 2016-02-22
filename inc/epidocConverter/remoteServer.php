<?php 
/**
 * 
 * epidocConverter - Remote Server
 * 
 * @version 1.0
 * 
 * @year 2016
 * 
 * @author Philipp Franck
 * 
 * @desc
 * Use this file if you want to have the epidoc conversion on another machine than your script.
 * 
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

/** 
 * settings 
 * 
 */

$allowedIps		= array('195.37.232.186', '95.110.197.48'); 


/** 
 * functions
 * 
 */

// in case of error
function returnError($message) {
	echo json_encode(array(
			'success'	=> false,
			'message'	=> $message,
	));
	die();
}


/**
 * go
 * 
 */

// low budget security check
$ip				= $_SERVER['REMOTE_ADDR'];
if (!in_array($ip, $allowedIps) and count($allowedIps)) {
	returnError("Not allowed, Mr. $ip!");
}


// get data
$data 		= isset($_POST['epidoc']) ? $_POST['epidoc'] : '';
$mode 		= isset($_POST['mode']) ? $_POST['mode'] : '';
$returnAll	= (isset($_POST['returnAll']) and $_POST['returnAll']);


// do the conversion thing
if ($data) {

	require_once('epidocConverter.class.php');

	$error = false;

	try {
			
		$converter = epidocConverter::create($data, $mode);

		$mode = get_class($converter);
			
		$res = $converter->convert($returnAll);
						
	} catch (Exception $e) {
		returnError($e->getMessage());
	}


} else {
	returnError('No Data');
}


// return
echo json_encode(array(
	'success'	=> true,
	'data'		=> $res,
	'mode'		=> $mode
));