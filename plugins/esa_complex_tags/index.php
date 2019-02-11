<?php
add_action('init', function() {
    register_taxonomy(
        'esa_complex_tags',
        'esa_item_wrapper',
        array(
            'label'                 => __('Tags'),
            'rewrite'               => array('slug' => 'data'),
            'hierarchical'          => true,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
        )
    );
});