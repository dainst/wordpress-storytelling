<?php
/**
 *
 * ******************************************* Search
 *
 * Make search able to search inside of esa_item_cache to find entries by it's content in esa item.
 */

add_action('save_post', function($post_id) {

    $post = get_post($post_id);
    global $wpdb;

    if (!wp_is_post_revision($post_id) and is_esa($post->post_type)) {

        $regex = get_shortcode_regex();
        preg_match_all("#$regex#s", $post->post_content, $shortcodes, PREG_SET_ORDER);

        //echo "<pre>", print_r($shortcodes,1), "</pre>";

        $sql = "delete from {$wpdb->prefix}esa_item_to_post where post_id=$post_id";
        $wpdb->query($sql);

        if ($shortcodes) {

            foreach($shortcodes as $shortcode) {
                if ($shortcode[2] == 'esa') {
                    $atts = shortcode_parse_atts($shortcode[3]);
                    // echo "<pre>", print_r($atts,1), "</pre>";

                    $wpdb->insert(
                        $wpdb->prefix . 'esa_item_to_post',
                        array(
                            "post_id" => $post_id,
                            "esa_item_source" => $atts['source'],
                            "esa_item_id" => $atts['id']
                        )
                    );


                }
            }
        }
    }
});

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

function esa_get_module_settings_search() {
    return array(
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
}

function esa_get_module_content_search() {
    // nothing, but function must exist
}

function esa_get_module_scripts_search() {
    // nothing, but function must exist
}