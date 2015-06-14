<?php
/**
 * @package eagle-storytelling
 * @version 0.6
 */
/*
Plugin Name: Eagle Storytelling Application
Plugin URI:  http://wordpress.org/plugins/eagle-storytelling/
Description: Create your own EAGLE story! 
Author:	     Wolfgang Schmidle
Author URI:	 http://www.dainst.org/
Version:     0.6

*/


/****************************************/

function esa_create_keywords_widget() {

    $labels = array(
        'name' => _x('Keywords', 'taxonomy general name', 'Flexible'),
        'singular_name' => _x('Keyword', 'taxonomy singular name', 'Flexible'),
        'search_items' => __('Search Keywords', 'Flexible'),
        'all_items' => __('All Keywords', 'Flexible'),
        'parent_item' => __('Parent Keyword', 'Flexible'),
        'parent_item_colon' => __('Parent Keyword:', 'Flexible'),
        'edit_item' => __('Edit Keyword', 'Flexible'),
        'update_item' => __('Update Keyword', 'Flexible'),
        'add_new_item' => __('Add New Keyword', 'Flexible'),
        'new_item_name' => __('New Keyword Name', 'Flexible'),
        'menu_name' => __('Keyword', 'Flexible')
    );

    register_taxonomy('story_keyword', array('story'), array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => apply_filters('et_portfolio_category_rewrite_args', array('slug' => 'portfolio'))
    ));
}

add_action('init', 'esa_create_keywords_widget', 0);


/****************************************/

function esa_create_portfolio_taxonomies() {

    $labels = array(
        'name' => _x('Categories', 'taxonomy general name', 'Flexible'),
        'singular_name' => _x('Category', 'taxonomy singular name', 'Flexible'),
        'search_items' => __('Search Categories', 'Flexible'),
        'all_items' => __('All Categories', 'Flexible'),
        'parent_item' => __('Parent Category', 'Flexible'),
        'parent_item_colon' => __('Parent Category:', 'Flexible'),
        'edit_item' => __('Edit Category', 'Flexible'),
        'update_item' => __('Update Category', 'Flexible'),
        'add_new_item' => __('Add New Category', 'Flexible'),
        'new_item_name' => __('New Category Name', 'Flexible'),
        'menu_name' => __('Category', 'Flexible')
    );

    register_taxonomy('story_category', array('story'), array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => apply_filters('et_portfolio_category_rewrite_args', array('slug' => 'portfolio'))
    ));
}

add_action('init', 'esa_create_portfolio_taxonomies', 0);


/****************************************/
/* create editor page for stories */ 

function esa_register_story_post_type() {

    $labels = array(
        'name' => _x('Stories', 'post type general name', 'Flexible'),
        'singular_name' => _x('Story', 'post type singular name', 'Flexible'),
        'add_new' => _x('Add New', 'story item', 'Flexible'),
        'add_new_item' => __('Add New Story', 'Flexible'),
        'edit_item' => __('Edit Story', 'Flexible'),
        'new_item' => __('New Story', 'Flexible'),
        'all_items' => __('All Stories', 'Flexible'),
        'view_item' => __('View Story', 'Flexible'),
        'search_items' => __('Search Stories', 'Flexible'),
        'not_found' => __('Nothing found', 'Flexible'),
        'not_found_in_trash' => __('Nothing found in Trash', 'Flexible'),
        'parent_item_colon' => ''
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => apply_filters('et_portfolio_posttype_rewrite_args', array('slug' => 'story', 'with_front' => false)),
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'revisions', 'custom-fields')
    );

    register_post_type('story', $args);
}

add_action('init', 'esa_register_story_post_type' );


/****************************************/
/* register template for the story pages */ 

function esa_get_story_post_type_template($single_template) {
     global $post;

     if ($post->post_type == 'story') {
          $single_template = dirname( __FILE__ ) . '/template/single-story.php';
     }
     return $single_template;
}

add_filter( 'single_template', 'esa_get_story_post_type_template' );


/****************************************/
/* register template for page "stories" */ 

function esa_get_stories_page_template( $page_template )
{
    if ( is_page( 'stories' ) ) {
        $page_template = dirname( __FILE__ ) . '/template/page-stories.php';
    }
    return $page_template;
}

add_filter( 'page_template', 'esa_get_stories_page_template' );


/****************************************/
/* register template for page "search stories" */ 

function esa_get_search_stories_page_template( $page_template )
{
//    if ( is_page( 'search-stories' ) ) {
//        $page_template = dirname( __FILE__ ) . '/template/search-stories.php';
//    }

//    if ( is_search() and ($_GET["post_type"] == "story") ) {
//        $page_template = dirname( __FILE__ ) . '/template/search-stories.php';
//    }
    return $page_template;
}

// add_filter( 'page_template', 'esa_get_search_stories_page_template' );


function searchfilter($query) {
    if ($query->is_search && $query->post_type == "story") {
        $query->set('meta_key','_wp_page_template');
        $query->set('meta_value', dirname( __FILE__ ) . '/template/search-stories.php');    }
return $query;
}

//add_filter('pre_get_posts','searchfilter');


/****************************************/
/* register template for page "create story" (which exists but is not yet used!) */ 

function esa_get_create_story_page_template( $page_template )
{
    if ( is_page( 'create-story' ) ) {
        $page_template = dirname( __FILE__ ) . '/template/page-create-story.php';
    }
    return $page_template;
}

add_filter( 'page_template', 'esa_get_create_story_page_template' );


/****************************************/

?>
