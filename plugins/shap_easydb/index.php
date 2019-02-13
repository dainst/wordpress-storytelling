<?php

add_filter('esa_collect_datasource_files', function($ds_list) {
    $ds_list['shap_easydb'] = ESA_PATH . "plugins/shap_easydb/shap_easydb.class.php";
    return $ds_list;
});


add_filter('esa_get_modules', function($modules) {
    $modules[] = 'shap_easydb';
    return $modules;
});


add_filter("esa_get_module_settings", function($settings) {
    $settings["shap_easydb"] = array(
        'label' => "Connection to the Easy-DB of the SHAP project",
        'info' => "Leave out username and password to use anonymous",
        'children' => array(
            // is the comment feature active
            'activate' => array(
                'default' => true,
                'label' => "Activate Feature",
                'type' => 'checkbox'
            ),
            'easyurl' => array(
                'default' => "https://syrian-heritage.5.easydb.de/api/v1",
                'type' => 'text',
                'label' => "EasyDB-Url"
            ),
            'easyuser' => array(
                'default' => "-",
                'type' => 'text',
                'label' => "EasyDB-Username"
            ),
            'easypass' => array(
                'default' => "-",
                'type' => 'password',
                'label' => "EasyDB-Password"
            )
        )
    );
    return $settings;
});