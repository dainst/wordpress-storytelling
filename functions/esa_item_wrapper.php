<?php

register_activation_hook( ESA_FILE, function() {
    esa_register_esa_item_wrapper();
    flush_rewrite_rules();
});

add_action('init', 'esa_register_esa_item_wrapper');


add_action('admin_enqueue_scripts', function($hook) {
    if (($hook == 'post.php') and (get_post_type() == 'esa_item_wrapper')) {
        wp_enqueue_style('colors');
        wp_enqueue_style('media');
        wp_enqueue_style('media-views');
        wp_enqueue_style('thickbox');
        wp_enqueue_style('esa_item', plugins_url() . ESA_DIR . '/css/esa_item.css');
        esa_register_special_styles();
        wp_enqueue_script('jquery');
        wp_enqueue_script('thickbox');
        wp_enqueue_script('esa_item.js', plugins_url() . ESA_DIR . '/js/esa_item.js', array('jquery'));
        wp_enqueue_script('esa_mediamenu.js', plugins_url() . ESA_DIR . '/js/esa_mediamenu.js', array('jquery'));
    }
});



function esa_item_wrapper_edit_ui($item) {
    add_meta_box('esa_item_wrapper_preview', $item->post_excerpt, 'esa_item_wrapper_preview', null, 'normal', 'high');
    add_meta_box('esa_item_wrapper_tags', "Tags", 'esa_item_wrapper_tags', null, 'side');
}

function esa_item_wrapper_preview($item) {
    echo do_shortcode(html_entity_decode($item->post_excerpt));
}

function esa_item_wrapper_tags($item) {
    post_tags_meta_box($item, array());
}

function esa_register_esa_item_wrapper() {
    if (!count(esa_get_modules())) {
        return;
    }
    register_post_type('esa_item_wrapper', array(
        "labels" => array(
            "name" => "Embedded Objects",
            "singular_name" => "Embedded Object"
        ),
        "show_ui" => true,
        "show_in_menu" => true,
        "show_in_admin_bar" => true,
        "public" => true,
        "supports" => array(
            'comments'
        ),
        "taxonomies" => array("post_tags"),
        "register_meta_box_cb" => "esa_item_wrapper_edit_ui",
        'capability_type' => 'post',
        'capabilities' => array(
            'create_posts' => 'do_not_allow', // false < WP 4.5
        ),
        'map_meta_cap' => true,
        'has_archive' => true,
        'publicly_queryable' => true
    ));
    flush_rewrite_rules();
}

function esa_get_wrapper($esaItem) {

    if (!count(esa_get_modules())) {
        return;
    }

    global $wpdb;

    $id = esa_clean_string($esaItem->id . "---" . $esaItem->source);

    $wrappers = get_posts(array(
        'post_type' => 'esa_item_wrapper',
        'name' => $id,
        'post_status' => 'publish',
        'posts_per_page' => -1
    ));

    //echo esa_debug($esaItem);
//    echo esa_debug($wrappers);
//    echo esa_debug($id);
//    wp_die("!");
    if (!count($wrappers)) {
        $wrapper = get_post(wp_insert_post(array(
            'post_name' => $id,
            'post_title' => $esaItem->title ? $esaItem->title : '',
            'post_type' => 'esa_item_wrapper',
            'post_status' => 'publish',
            'post_excerpt' => "[esa source=\"{$esaItem->source}\" id=\"{$esaItem->id}\"]"
        )));
        $wpdb->insert(
            $wpdb->prefix . 'esa_item_to_post',
            array(
                "post_id" => $wrapper->ID,
                "esa_item_source" => $esaItem->source,
                "esa_item_id" => $esaItem->id
            )
        );
    } else {
        $wrapper = array_pop($wrappers);
        foreach ($wrappers as $item) {
            wp_delete_post($item->ID);
        }
    }



    return $wrapper;
}

function esa_clean_string($string) {
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}