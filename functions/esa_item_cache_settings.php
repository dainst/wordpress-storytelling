<?php

function esa_get_module_content_cache() {
    // nothing, but function must exist
}

function esa_get_module_scripts_cache() {
    // nothing, but function must exist
}

function esa_get_module_store_shortcode_action_cache($post, $atts) {
    // nothing, but function must exist
}

function esa_get_module_settings_cache() {
    return array(
        'label' => 'Use Cache for Esa-Items',
        'info' => '',
        'children' => array(
            'activate' => array(
                'default' => true,
                'type' => 'checkbox',
                'label' => 'Activae Caching Feature (only disable this for debug)',
            )
        )
    );
}