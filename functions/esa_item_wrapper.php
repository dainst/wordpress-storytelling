<?php
add_action('init', 'esa_register_esa_item_wrapper');

function esa_install_register_esa_item_wrapper() {
    esa_register_esa_item_wrapper();
    flush_rewrite_rules();
}

function esa_register_esa_item_wrapper() {
    register_post_type('esa_item_wrapper', array(
        "labels" => array(
            "name" => "Embedded Objects",
            "singular_name" => "Embedded Object"
        ),
        "show_ui" => true,
        "show_in_menu" => true,
        "show_in_admin_bar" => true,
        "supports" => array(
            'title' => true,
            'editor' => true,
            'author' => false,
            'thumbnail' => false,
            'excerpt' => false,
            'trackbacks' => false,
            'custom-fields' => false,
            'comments' => true,
            'revisions' => false,
            'page-attributes' => false,
            'post-formats' => false
        ),
        "taxonomies" => array("post_tags")
    ));
}

function get_esa_item_wrapper($esaItem) {

    $id = sanitize_title($esaItem->id . "---" . $esaItem->source);

    $wrappers = get_posts(array(
        'post_type' => 'esa_item_wrapper',
        'title' => $id,
        'post_status' => 'publish',
        'posts_per_page' => -1
    ));

    if (!count($wrappers)) {
        $wrapper = get_post(wp_insert_post(array(
            'post_title' => $id,
            'post_type' => 'esa_item_wrapper',
            'post_status' => 'publish',
            'post_content' => "[esa source='{$esaItem->source}' id='{$esaItem->id}']"
        )));
    } else {
        $wrapper = array_pop($wrappers);
        foreach ($wrappers as $item) {
            wp_delete_post($item->ID);
        }
    }

    return $wrapper;
}