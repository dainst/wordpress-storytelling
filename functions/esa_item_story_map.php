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



function esa_item_map($display='both') {
    if (esa_get_settings('modules', 'map', 'activate')) {
        echo "<div class='esa_items_overview_map' data-display='$display'>&nbsp;</div>";
    }
}


function esa_map_query() {

    global $wpdb;

    $sql = "
            select
                esa_item.latitude,
                esa_item.longitude,
                
                post.ID = '$post_id' as selected
                
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
                post.post_type in (esa_item_wrapper)
                
            group by 
                longitude, 
                latitude
            ";



    $result = $wpdb->get_results($sql);

    echo json_encode($result);
}



function esa_map($post, $basemapLayer = "osm") {

    // TODO check if wrapper is enabled

    if ($post->post_type !== "esa_item_wrapper") {
        echo "Error: wrong post type";
    }

    $item = esa_get_item_by_wrapper($post);

    $img = new \esa_item\image(array(
        "type"      => "MAP",
        "marker"    =>  array($item->latitude, $item->longitude),
        "layer"     =>  $basemapLayer
    ));

    $tmp = new \esa_item("", "", $img->render());

    echo $tmp->html();
}

function wp_ajax_esa_get_overview_map() {

    global $wpdb;
    global $post;

    $url     = wp_get_referer();
    $post_id = url_to_postid( $url );

    $display = isset($_POST['display']) ? $_POST['display'] : 'both';
    if ($display == 'embedded') {
        $types = esa_get_settings('post_types');
    } else if ($display == 'wrapper') {
        $types = array("esa_item_wrapper");
    } else {
        $types = esa_get_post_types();
    }
    $post_types = "'" . implode("', '", $types) . "'";


    $sql = "
            select
                esa_item.latitude,
                esa_item.longitude,
                concat (
                    '<span class=\"esa_inmap_popup\">',
                    if (
                        count(post.ID) > 1,
                        concat(
                          '<ul>',
                          group_concat('<li><a href=\"?=', post.ID ,'\">', post.post_title, '</a></li>' separator ''),
                          '</ul>'
                        ),
                        concat(
                          '<a href=\"?p=', post.ID, '\">', '<h1>', post.post_title, '</h1><p class=\"excerpt\">', 
                          if (
                            post.post_type = 'esa_item_wrapper',
                            '',
                            post.post_excerpt
                          ), 
                          '</p></a>'
                        )
                    ),
                    '</span>'
                ) as textbox,
                post.ID = '$post_id' as selected
                
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

add_action("esa_get_module_scripts", function() {
    wp_register_style('leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.3.4/leaflet.css');
    wp_register_style('leaflet-markercluster', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.4.1/MarkerCluster.css', array('leaflet'));
    wp_register_style('leaflet-markercluster-default', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.4.1/MarkerCluster.Default.css', array('leaflet', 'leaflet-markercluster'));
    wp_enqueue_style('leaflet');
    wp_enqueue_style('leaflet-markercluster');
    wp_enqueue_style('leaflet-markercluster-default');
});

add_filter("esa_get_module_settings", function($settings) {
    $settings["map"] = array(
        'label' => "Overview map of embedded content",
        'info' => "Add a Map to your page here: <a href='widgets.php'>" . __('Widgets') . "</a>",
        'children' => array(
            'activate' => array(
                'default' => true,
                'type' => 'checkbox',
                'label' => "Activate Feature",
                ''
            ),
        )
    );
    return $settings;
});