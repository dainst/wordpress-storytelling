<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Settings File
 * @link 		http://www.europeana.eu/
 * @author 		Philipp Franck
 *
 *
 * Some Settings, which may differ from server to server
 * 
 * may be replaced by a wordpress settings page in the future?
 * 
 */

// show debug info
define('ESA_DEBUG', false);

// list of available data sources (must correspondent with files in /datasources)
$esa_datasources = array(
		'europeana' 	=> __('Europeana'),
		'idai'			=> __('iDAI Gazetteer'),
		'wiki' 			=> __('Wikipedia'),
		
);

$esa_post_type = 'story';

?>