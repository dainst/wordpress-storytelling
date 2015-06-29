<?php

/**
 * Settings, which differ from server to server
 * 
 * may be replaced by a wordpress settings page in the future?
 * 
 */

// show debug info
define('ESA_DEBUG', false);

// list of available data sources (must correspondent with files in /datasources)
$esa_datasources = array(
		'idai'			=> __('Search in iDAI Gazetteer'),
		'europeana' 	=> __('search in Europeana'),
		'wiki' 			=> __('TEST Data-Engine: Wikipedia'),
		
);
?>