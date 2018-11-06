<?php

/**
 * the caching mechanism for esa_items
 *
 * how it works: everytime a esa_item get displayed, it look in the cache if there is a non expired cache of this item. if not,
 * it fetches the contents from the corresponding api and caches it
 * that has two reasons:
 * - we want to make the embedded esa_items searchable
 * - page loading would be quite slow, if every items content had to be fetched again from the api
 *
 * how long may content be kept in cache? that has to be discussed.
 *
 *
 */

function esa_get_module_content_cache() {
    // nothing, but function must exist
}

function esa_get_module_scripts_cache() {
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
                'label' => 'Activate Caching Feature (only disable this for debug)',
            )
        )
    );
}


add_action('admin_action_esa_flush_cache', function() {
    global $wpdb;

    $sql = "truncate {$wpdb->prefix}esa_item_cache;";

    $wpdb->query($sql);

    if (isset($_POST['wrappers'])) {
        $allposts= get_posts(array('post_type' => 'esa_item_wrapper', 'numberposts' => -1));
        foreach ($allposts as $eachpost) {
            wp_delete_post($eachpost->ID, true);
        }
    }

    wp_redirect($_SERVER['HTTP_REFERER']);
    exit();

});

add_action('admin_action_esa_refresh_cache', function() {
    global $wpdb;

    $sql = "truncate {$wpdb->prefix}esa_item_cache;";

    $wpdb->query($sql);

    $sql = "
        select
            esa_item_source as source,
            esa_item_id as id
        from
             {$wpdb->prefix}esa_item_to_post
         
        group by
            esa_item_source,
            esa_item_id
    ";

    foreach ($wpdb->get_results($sql) as $row) {
        $item = new \esa_item($row->source, $row->id);
        $item->html(true);
        $e = count($item->errors);
    }

    wp_redirect($_SERVER['HTTP_REFERER']);
    exit();

});