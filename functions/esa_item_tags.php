<?php

require_once(ABSPATH  . 'wp-admin/includes/taxonomy.php');
require_once(ABSPATH  . 'wp-admin/includes/meta-boxes.php');

function esa_get_module_scripts_tags() {
    if (!esa_get_settings('modules', 'tags', 'activate')) {
        return;
    }
    $dev_suffix = esa_get_settings('script_suffix');

    wp_register_style('esa_item-tags', plugins_url(ESA_DIR . '/css/esa_item-tags.css'));
    wp_enqueue_style('esa_item-tags');

    wp_enqueue_script(
        'esa_item_tags.js',
        plugins_url() . ESA_DIR . '/js/esa_item_tags.js',
        array('jquery')
    );
    wp_enqueue_script(
        'tags-suggest',
        admin_url() . "js/tags-suggest$dev_suffix.js",
        array('jquery', 'jquery-ui-autocomplete'),
        false,
        true
    );
    wp_enqueue_script(
        'tags-box',
        admin_url() . "js/tags-box$dev_suffix.js",
        array('jquery', 'tags-suggest'),
        false,
        true
    );
    wp_enqueue_script(
        'jquery-ui-autocomplete',
        "/wp-includes/js/jquery/ui/autocomplete$dev_suffix.js",
        array( 'jquery-ui-menu', 'wp-a11y' ),
        '1.11.4',
        true
    );
    wp_localize_script('jquery-ui-autocomplete', 'uiAutocompleteL10n', array(
        'noResults' => __('No results found.'),
        'oneResult' => __('1 result found. Use up and down arrow keys to navigate.'),
        'manyResults' => __('%d results found. Use up and down arrow keys to navigate.'),
        'itemSelected' => __('Item selected.'),
    ));
    wp_localize_script( 'tags-suggest', 'tagsSuggestL10n', array(
        'tagDelimiter' => _x(',', 'tag delimiter'),
        'removeTerm'   => __('Remove term:'),
        'termSelected' => __('Term selected.'),
        'termAdded'    => __('Term added.'),
        'termRemoved'  => __('Term removed.'),
    ));
    wp_localize_script('esa_item_tags.js', 'esaItemTagsOptions', array(
        'color' => array(
                'red' => (int) esa_get_settings('modules', 'tags', 'color', 'red'),
                'green' => (int) esa_get_settings('modules', 'tags', 'color', 'green'),
                'blue' => (int) esa_get_settings('modules', 'tags', 'color', 'blue'),
        )
    ));
    wp_add_inline_script('tags-suggest', "var ajaxurl = '" . admin_url('admin-ajax.php') . "';", "before");

}

function esa_get_module_settings_tags() {
    return array(
        'label' => "Tags on Esa-Items",
        'info' => "Manage Tags here: <a href='edit-tags.php?taxonomy=post_tag'>" . __('Posts') . " > " . __('Keywords') . "</a>",
        'children' => array(
            // is the tagging feature active
            'activate' => array(
                'default' => true,
                'type' => 'checkbox',
                'label' => "Activate Feature"
            ),
            // can tags be edited at the frontend
            'visitor_can_add' => array(
                'default' => true,
                'type' => 'checkbox',
                'label' => "Visitor can Add Tags to Items without Login"
            ),
            // can new tags be created in the frontend?
            'visitor_can_create' => array(
                'default' => true,
                'type' => 'checkbox',
                'label' => "Visitor can even Add previously unused Tags to Items without Login\""
            ),
            // can tags be deleted in the frontend?
            'visitor_can_delete' => array(
                'default' => true,
                'type' => 'checkbox',
                'label' => "Visitor can Delete Tags to Items without Login"
            ),
            // rgb color for the tags. channels which are set to false will get an automatic values
            'color' => array(
                'label' => "Tag Color",
                'children' => array(
                    'red' => array(
                        'default' => -1,
                        'label' => "Tag color: red channel (0 to 255, -1 for automatic)",
                        'type' => 'number',
                        'min' => -1,
                        'max' => 255
                    ),
                     'green' => array(
                        'default' => -1,
                        'label' => "Tag color: green channel (0 to 255, -1 for automatic)",
                        'type' => 'number',
                        'min' => -1,
                        'max' => 255
                    ),
                    'blue' => array(
                        'default' => -1,
                        'label' => "Tag color: blue channel (0 to 255, -1 for automatic)",
                        'type' => 'number',
                        'min' => -1,
                        'max' => 255
                    )
                )
            )
        )
    );
}

add_action('init', function() {
    remove_action("wp_ajax_get-tagcloud", "wp_ajax_get_tagcloud", 1);
    add_action('wp_ajax_get-tagcloud', 'esa_ajax_tag_cloud', 1);
    add_action('wp_ajax_nopriv_get-tagcloud', 'esa_ajax_tag_cloud', 1);
    add_action("wp_ajax_nopriv_ajax-tag-search", "esa_ajax_tag_search");

    add_action("wp_ajax_esa-update-tags", "esa_ajax_update_tags");
    add_action("wp_ajax_nopriv_esa-update-tags", "esa_ajax_update_tags");
    add_action("wp_ajax_esa-get-tags", "esa_ajax_get_tags");
    add_action("wp_ajax_nopriv_esa-get-tags", "esa_ajax_get_tags");
});

add_filter('user_has_cap', function($allcaps, $caps, $args) {

    if (!in_array($args[0], array('assign_post_tags', 'delete_post_tags'))) {
        return $allcaps;
    }

    if (esa_get_settings('modules', 'tags', 'visitor_can_add')) {
        $allcaps['assign_post_tags'] = 1;
        $allcaps['edit_posts'] = 1;
    }

    if (esa_get_settings('modules', 'tags', 'visitor_can_delete')) {
        $allcaps['delete_post_tags'] = 1;
        $allcaps['edit_posts'] = 1;
    }

    return $allcaps;
}, 10, 3);

// include wrappers into tag archives
add_action('pre_get_posts', function($query) {
    if ($query->is_tag() && $query->is_main_query()) {
        $query->set('post_type', array('post', 'esa_item_wrapper'));
    }
});

function esa_ajax_get_tags() {

    if (!isset($_POST['esa_item_wrapper_id'])) {
        wp_die("wrapper id missing");
    }
    $wrapper = get_post($_POST['esa_item_wrapper_id']);
    if (!$wrapper or $wrapper->post_type != "esa_item_wrapper") {
        wp_die("wrapper not found");
    }

    $stored_tags = array();
    foreach(wp_get_object_terms($wrapper->ID, "post_tag") as $term) {
        $stored_tags[rawurlencode($term->name)] = array(
            'name' => $term->name,
            'url' => get_tag_link($term->term_id),
            'id' => $term->term_id
        );
    }

    wp_die(json_encode($stored_tags));
}

function esa_ajax_update_tags() {

    if (!isset($_POST['esa_item_wrapper_id'])) {
        wp_die("wrapper id missing");
    }
    $wrapper = get_post($_POST['esa_item_wrapper_id']);
    if (!$wrapper or $wrapper->post_type != "esa_item_wrapper") {
        wp_die("wrapper not found");
    }

    $tags = isset($_POST['tags']) ? $_POST['tags'] : array();
    if (esa_get_settings('modules', 'tags', 'visitor_can_delete') or (current_user_can('delete_post_tags'))) {
        wp_set_object_terms($wrapper->ID, null, "post_tag");
    }
    foreach ($tags as $tag) {
        if (!esa_get_settings('modules', 'tags', 'visitor_can_create')) {
            if (!get_term_by('name', $tag, 'post_tag')) {
                continue;
            }
        }

        $tag = substr($tag, 0, 32);

        if (!wp_set_object_terms($wrapper->ID, $tag, "post_tag", true)) {
            wp_die("could not save tag:" . $tag);
        }
    }

    esa_ajax_get_tags();
}

function get_esa_item_tag_box($esaItem) {
    $wrapper = esa_get_wrapper($esaItem);
    ob_start();
    $taxonomy = get_taxonomy('post_tag');
    $user_can_assign_terms = esa_get_settings('modules', 'tags', 'visitor_can_add') or current_user_can($taxonomy->cap->assign_terms);
    $user_can_remove_terms = esa_get_settings('modules', 'tags', 'visitor_can_delete') or current_user_can($taxonomy->cap->delete_terms);
    $comma = _x(',', 'tag delimiter');
    $terms_to_edit = get_terms_to_edit($wrapper->ID, 'post_tag');
    if (!is_string($terms_to_edit)) {
        $terms_to_edit = '';
    }
    ?>
    <div class="esa-item-tags tagsdiv" id="esa_post_tag-<?php echo $wrapper->ID ?>" data-esa-item-wrapper-id="<?php echo $wrapper->ID ?>">
        <div class="jaxtag">

            <textarea name="tax_input[post_tag]" rows="3" cols="20" class="esa-hide the-tags" <?php disabled(!$user_can_assign_terms); ?> autocomplete="off">
                <?php echo str_replace(',', $comma . ' ', $terms_to_edit); ?>
            </textarea>

            <ul class="tagchecklist <?php echo $user_can_remove_terms ? "" : "no-delete-btn" ?>" role="list"></ul>

            <?php if ($user_can_assign_terms) : ?>
                <div class="add-tag-buttons esa-module-buttons">
                    <input type="button" class="tag-suggest-button button-link tagcloud-link" aria-expanded="false" value="&#xf318;" id="link-esa_post_tag-<?php echo $wrapper->ID ?>" title="<?php echo $taxonomy->labels->choose_from_most_used; ?>" />
                    <div class="ajaxtag">
                        <input type="text" maxlength="32" data-wp-taxonomy="post_tag" name="newtag[post_tag]" class="newtag form-input-tip" size="16" autocomplete="off" value="" />
                        <input type="button" class="button tag-add-button tagadd" value="&#xf502;" title="<?php echo $taxonomy->labels->add_new_item; ?>"/>
                    </div>
                </div>
            <?php elseif (empty($terms_to_edit)) : ?>
                <p><?php echo $taxonomy->labels->no_terms; ?></p>
            <?php endif; ?>

        </div>


    </div>
    <?php
    return ob_get_clean();
}

function esa_ajax_tag_search() {
    wp_ajax_ajax_tag_search();
}

function esa_ajax_tag_cloud() {
    if (!isset($_POST['tax'])) {
        wp_die(0);
    }
    if (substr($_POST['tax'],0, 4) == "esa_") {
        $_POST['tax'] = 'post_tag';
        wp_ajax_get_tagcloud();
    }

    wp_ajax_get_tagcloud();
}

function esa_get_module_content_tags($esaItem) {
    return get_esa_item_tag_box($esaItem);
}

function esa_get_module_store_shortcode_action_tags($post, $attrs) {
    $item = new esa_item($attrs['source'], $attrs['id']);
    $item->html(true);
    esa_get_wrapper($item);
}