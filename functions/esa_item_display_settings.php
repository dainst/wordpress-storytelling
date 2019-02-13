<?php
add_filter('esa_get_modules', function($modules) {
    $modules[] = 'shap_easydb';
    return $modules;
});


add_filter("esa_get_module_settings", function($settings) {
    $settings["esa_item_display_settings"] = array(
        'label' => "Display Settings",
        'info' => "",
        'children' => array(
            'dont_collapse_esa_items' => array(
                'default' => false,
                'type' => 'checkbox',
                'label' => "Don't collapse embedded content boxes"
            ),
        )
    );
    return $settings;
});