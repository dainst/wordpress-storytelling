<?php
/**
 * ******************************************* the esa_item shortcode / URL Embed
 *
 *
 *  attribute
 *  id (mandatory) - unique id of the datatset as used in the external data source
 *  source (mandatory) - name of the datasource as used in this plugin
 *
 *  height - height of the item in px
 *  width - width of the item in px
 *  align (left|right|none) - align of the object in relation to the surrounding text (deafult is right)
 *
 *  bsp:
 *
 *  [esa source="wiki" id="berlin"]
 *
 */

add_action('init', function() {
    add_shortcode('esa', 'esa_shortcode');
});

add_filter('the_excerpt', function($excerpt) {
    return do_shortcode($excerpt);
}, 10, 1);


function esa_shortcode($atts, $context) {

    if (!isset($atts['source']) or !isset($atts['id'])) {
        return "";
    }

    $css = array();
    $classes = array();

    if (isset($atts['height'])) {
        $css['height'] = $atts['height'] . 'px';
    }
    if (isset($atts['width'])) {
        $css['width'] = $atts['width'] . 'px';
    }

    if (isset($atts['align'])) {
        if ($atts['align'] == 'right') {
            $classes[] = 'esa_item_right';
        } elseif ($atts['align'] == 'left') {
            $classes[] = 'esa_item_left';
        } else {
            $classes[] = 'esa_item_none';
        }
    }

    $item = new esa_item($atts['source'], $atts['id'], false, false, false, $classes, $css);

    $content = $item->html(true);

    if (!is_admin()) {
        foreach(esa_get_modules() as $mod) {
            $content .= call_user_func("esa_get_module_content_$mod", $item);
        }
    }

    return $content;
}

add_action('wp_ajax_esa_shortcode', function() {
    if (isset($_POST['shortcode'])) {

        $result = array();

        $result['shortcode'] = rawurldecode($_POST['shortcode']);

        $result['esa_item'] = do_shortcode(str_replace('\\', '', rawurldecode($_POST['shortcode'])));

        $result['debug'] = str_replace('\\', '', rawurldecode($_POST['shortcode']));

        echo json_encode($result);

        wp_die();
    }
    wp_send_json_error(array(
        'type' => 'esa',
        'message' => 'no shortcode'
    ));
});

add_action('wp_ajax_esa_url_checker', function() {

    if (isset($_POST['esa_url'])) {

        $url = rawurldecode($_POST['esa_url']);

        $result = array();

        $result['debug'] = array();
        $result['debug'][] = 'check url: ' . $url;

        $datasources = json_decode(get_option('esa_datasources'));
        if (!is_array($datasources)) {
            $datasources  = array();
        }
        foreach ($datasources as $ds) {
            $dso = get_esa_datasource($ds);

            $result['debug'][] = "check against: $ds";
            // @todo try & catch
            $maybe_item = (!$dso->id_is_url) ? $dso->get_by_url($url) : false;

            if ($maybe_item instanceof \esa_item)  {
                $result['shortcode'] = "[esa source=\"{$maybe_item->source}\" id=\"{$maybe_item->id}\"]";
                $result['esa_item'] = $maybe_item->html(true);
                wp_send_json_success($result);
            }

        }
        //wp_send_json_error($result);wp_die();
        $_POST['shortcode'] = "[embed src=\"$url\"]";
        wp_ajax_parse_embed();

    }

    wp_send_json_error(array(
        'type' => 'esa_error',
        'subtype' => 'no_url',
        'message' => 'no esa url'
    ));
});

add_action('save_post', function($post_id) {

    $post = get_post($post_id);
    global $wpdb;

    if (!wp_is_post_revision($post_id) and is_esa($post->post_type)) {

        $regex = get_shortcode_regex();
        preg_match_all("#$regex#s", $post->post_content, $shortcodes, PREG_SET_ORDER);

        $sql = "delete from {$wpdb->prefix}esa_item_to_post where post_id=$post_id";
        $wpdb->query($sql);

        if ($shortcodes) {

            foreach($shortcodes as $shortcode) {
                if ($shortcode[2] == 'esa') {
                    $atts = shortcode_parse_atts($shortcode[3]);

                    foreach(esa_get_modules() as $mod) {
                        call_user_func("esa_get_module_store_shortcode_action_$mod", $post, $atts);
                    }

                }
            }
        }
    }
});