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

function esa_add_complex_tag(string $termName, array $params = array()) : array {
    $parent = isset($params['parent']) ? $params['parent'] : 0;
    $term = term_exists($termName, "esa_complex_tags", $parent);

    if (!$term) {
        $term = wp_insert_term($termName, "esa_complex_tags", $params);
    }

    if ($term instanceof WP_Error) {
        throw new Exception("[$termName] " . $term->get_error_message());
    }

    return $term;
}


add_action("esa_get_wrapper", function(\esa_item $esaItem, \WP_Post $wrapper) {


    // TODO only if none are allready stored!

    $terms = array();

    try {
        foreach ($esaItem->getRawdata() as $key => $languages) {
            foreach ($languages as $language => $values) {

                $keyTerm = esa_add_complex_tag($key);
                $terms[] = $keyTerm['term_id'];

                foreach ($values as $value) {

                    $params = array();

                    if (!in_array($language, array("", "#", "?", "??", null))) {
                        $params["description"] = $language;
                    }

                    $params["parent"] = $keyTerm['term_id'];

                    $terms[] = esa_add_complex_tag($value, $params)['term_id'];

                    // TODO alias_of ... but need other stored structure (wich is not a problem)
                }

            }
        }

        $set = wp_set_post_terms($wrapper->ID, $terms, "esa_complex_tags", false);

        if ($set instanceof WP_Error) {
            throw new Exception($set->get_error_message());
        }

        if ($set === false) {
            throw new Exception('$post_id evaluates as false');
        }

        if(!is_array($set)) {
            throw new Exception("Error:" . esa_debug($set));
        }


    } catch (Exception $e) {
        die("<div class='error'>" . $e->getMessage() . "</div>");
    }

}, 10, 2);

add_action("esa_flush_cache", function($wrappers) {
    global $wpdb;
    if ($wrappers) {
        $terms = get_terms(array('taxonomy' => 'esa_complex_tags', 'hide_empty' => false));
        foreach ($terms as $value) {
            wp_delete_term($value->term_id, 'esa_complex_tags');
        }
    }
}, 20, 1);