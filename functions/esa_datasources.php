<?php

/**
 * returns an object instance of a datasource called by slug
 *
 * @param string $ds
 * @return esa_datasource
 */
function get_esa_datasource($ds) {
    if (!$ds) {
        return null;
    }

    $ds_class = "\\esa_datasource\\$ds";

    if (class_exists($ds_class)) {
        return new $ds_class;
    }

    $ds_files = esa_collect_datasource_files();

    if (!isset($ds_files[$ds])) {
        echo "Error: Datasource '$ds' not found!";
        return null;
    };

    if (!file_exists($ds_files[$ds])) {
        echo "Error: File '{$ds_files[$ds]}' not found!";
        return null;
    }

    require_once($ds_files[$ds]);

    return new $ds_class;
}


function esa_collect_datasource_files() {
    $ds_files = glob(ESA_PATH . "datasources/*.class.php");
    $ds_list = array();
    foreach ($ds_files as $filename) {
        $ds_name = basename($filename, '.class.php');
        $ds_list[$ds_name] = $filename;
    }
    $ds_list = apply_filters('esa_collect_datasource_files', $ds_list);
    return $ds_list;
}