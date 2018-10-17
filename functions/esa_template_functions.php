<?php
/**
 * ******************************************* useful functions (for templates)
 */

/**
 * checks whether actual selected post is of a post type which is selected for the esa functions
 * if this in templates
 *
 * @param string $post_type
 * @return boolean
 */
function is_esa($post_type = false) {
    global $is_esa_story_page; // if you want to set manually a page as esa page in template use this
    if (!$post_type) {
        global $post;
        $post_type = (is_object($post)) ? $post->post_type : '';
    }
    return (in_array($post_type, esa_get_settings('post_types'))) or $is_esa_story_page;
}

/**
 * returns an object instance of a datasource called by slug
 *
 * @param string $engine
 * @return esa_datasource
 */
function get_esa_datasource($engine) {
    if (!$engine) {
        return;
    }
    // get engine interface
    if (!file_exists(ESA_PATH . "datasources/$engine.class.php")) {
        echo "Error: Sub-Plugin engine '$engine' not found!"; return;
    }

    require_once(ESA_PATH . "datasources/$engine.class.php");
    $ed_class = "\\esa_datasource\\$engine";
    return new $ed_class;
}