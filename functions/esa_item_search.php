<?php
/**
 *
 * ******************************************* Search
 *
 * Make search able to search inside of esa_item_cache to find entries by it's content in esa item.
 */



add_filter('query_vars', function($public_query_vars) {
    $public_query_vars[] = 'esa_item_source';
    $public_query_vars[] = 'esa_item_id';
    return $public_query_vars;
});

add_filter('posts_search', function($sql, $query) {

    if (!$query->is_main_query()) {
        return $sql;
    }

    $post_types = "'" . implode("', '", esa_get_post_types()) . "'";
    $sqlr = "";
    $story = false;

    global $wp_query;
    global $wpdb;

    $sqst = "select
                esai2post.post_id
            from
                {$wpdb->prefix}esa_item_to_post as esai2post
            left join {$wpdb->prefix}esa_item_cache as esai on (esai.id = esai2post.esa_item_id and esai.source = esai2post.esa_item_source)
                where";

    if (isset($wp_query->query['s']) and ($wp_query->query['s'] != '')) {
        $where = "\n\t esai.searchindex like '%{$wp_query->query['s']}%'";
        $sqlr = "AND (({$wpdb->prefix}posts.ID in ($sqst $where) and {$wpdb->prefix}posts.post_type in ($post_types)) or (1 = 1 $sql))";
    }

    if (isset($wp_query->query['x1']) and isset($wp_query->query['x2']) and isset($wp_query->query['y1']) and isset($wp_query->query['y2'])) {
        $x1 = min(180, max(0, floatval($wp_query->query['x1'])));
        $y1 = min(90, max(-90, floatval($wp_query->query['y1'])));
        $x2 = min(180, max(0, floatval($wp_query->query['x2'])));
        $y2 = min(90, max(-90, floatval($wp_query->query['y2'])));

        if (($x1 < $x2) or ($y1 < $y2)){
            $x1 ^= $x2;
            $y1 ^= $y2;
        }

        $where = "\n\t (esai.longitude <= $x1) and (esai.longitude >= $x2) and (esai.latitude <= $y1) and (esai.latitude >= $y2) ";
        $sqlr = "AND (({$wpdb->prefix}posts.ID in ($sqst $where) and {$wpdb->prefix}posts.post_type in ($post_types)) or (1 = 1 $sql))";
    }

    if (isset($wp_query->query['esa_item_source']) and isset($wp_query->query['esa_item_id'])
        and $wp_query->query['esa_item_source'] and $wp_query->query['esa_item_id']) {
            $story = true;
            $where = "esai.id = '{$wp_query->query['esa_item_id']}' and esai.source = '{$wp_query->query['esa_item_source']}'";
            $sqlr = "AND {$wpdb->prefix}posts.ID in ($sqst $where)";
    }

    if ((isset($wp_query->query['post_type']) and !in_array($wp_query->query['post_type'], esa_get_post_types()))
        or (!$sql and !$story)) {
            return $sql;
    }

    return $sqlr;

}, 10, 2);

add_filter("esa_get_module_settings", function($settings) {
    $settings["search"] = array(
        'label' => 'Include Esa-Items to search',
        'info' => '',
        'children' => array(
            'activate' => array(
                'default' => true,
                'type' => 'checkbox',
                'label' => 'Show both, posts which contain Esa-Items and Esa-Items themselves in search results',
            )
        )
    );
    return $settings;
});

add_action("esa_get_module_store_shortcode", function($post, $atts) {
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'esa_item_to_post',
        array(
            "post_id" => $post->ID,
            "esa_item_source" => $atts['source'],
            "esa_item_id" => $atts['id']
        )
    );
}, 10, 2);