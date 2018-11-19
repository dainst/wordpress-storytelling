<?php

add_filter('esa_collect_datasource_files', function($ds_list) {
    $ds_list['shap_easydb'] = ESA_PATH . "plugins/shap_easydb/shap_easydb.class.php";
    return $ds_list;
});