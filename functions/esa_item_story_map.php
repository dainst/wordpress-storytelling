<?php
/**
 *
 * Displays a map of all posts with esa_items which have geographic coordinates.
 *
 * can be used in template like <?php esa_item_map(); ?>
 *
 * or as widget  (see below)
 *
 * the map will be filled per ajax
 *
 */
function esa_item_map() {
    echo "<div id='esa_items_overview_map'>&nbsp;</div>";
}

function wp_ajax_esa_get_overview_map() {

    global $wpdb;

    $post_types = "'" . implode("', '", esa_get_post_types()) . "'";

    $sql = "
            select
                esa_item.latitude,
                esa_item.longitude,
                concat (
                    '<span class=\"esa_inmap_popup\">',
                    if (
                        count(post.ID) > 1,
                        concat('<h1>', count(post.ID), ' stories here:', '</h1><ul>',
                            group_concat('<li><a href=\"',post.guid ,'\">', post.post_title, '</a>' separator ''),
                        '</ul>'),
                        concat('<a href=\"', post.guid,'\">', '<h1>', post.post_title, '</h1><p class=\"excerpt\">', post.post_excerpt, '</p>', '</a>')
                    ),
                    '</span>'
                ) as textbox
                
            from
                {$wpdb->prefix}esa_item_cache as esa_item
                left join {$wpdb->prefix}esa_item_to_post as i2p on (i2p.esa_item_source =  esa_item.source and i2p.esa_item_id =  esa_item.id)
                left join {$wpdb->prefix}posts as post on (post.ID = i2p.post_id)
            
            where
                post.post_status = 'publish' and
                esa_item.latitude is not null and
                esa_item.latitude != 0  and
                esa_item.longitude is not null and
                esa_item.longitude != 0 and
                post.post_type in ($post_types)
                
            group by 
                longitude, 
                latitude
            ";



    $result = $wpdb->get_results($sql);

    echo json_encode($result);

    wp_die();
}

add_action('wp_ajax_esa_get_overview_map','wp_ajax_esa_get_overview_map');
add_action('wp_ajax_nopriv_esa_get_overview_map','wp_ajax_esa_get_overview_map');

add_action('widgets_init', function(){
    register_widget('esa_map_widget');
});