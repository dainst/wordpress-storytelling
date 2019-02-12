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

function esa_add_complex_tag(string $termName, int $parent = 0) : array {
    $term = term_exists($termName, "esa_complex_tags", $parent);

    if (!$term) {
        $term = wp_insert_term($termName, "esa_complex_tags", array("parent" => $parent));
    }

    if ($term instanceof WP_Error) {
        throw new Exception("[$termName] " . $term->get_error_message());
    }

    return $term;
}


add_action("esa_get_wrapper", function(\esa_item $esaItem, $wrapper) {

    $terms = array();

    try {
        foreach ($esaItem->getRawdata() as $key => $languages) {
            foreach ($languages as $language => $values) {
                $langTerm = esa_add_complex_tag($language);
                $keyTerm = esa_add_complex_tag($key, $langTerm['term_id']);

                foreach ($values as $value) {
                    $terms[] = esa_add_complex_tag($value, $keyTerm['term_id']);
                }

            }
        }

        die(esa_debug($terms));
    } catch (Exception $e) {

        die($e->getMessage());
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