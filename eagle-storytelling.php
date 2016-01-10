<?php
/**
 * @package eagle-storytelling
 * @version 1.0 BETA Tester Version
 */
/*
Plugin Name: Eagle Storytelling Application
Plugin URI:  http://www.eagle-network.eu/stories/
Description: Create your own EAGLE story! 
Author:	     Philipp Franck
Author URI:	 http://www.dainst.org/
Version:     1.0 BETA Tester Version
*/
/*

Copyright (C) 2015  Deutsches Archäologisches Institut

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	die();
}

/**
 * ******************************************* Settings
 */
define('ESA_DEBUG', false);

$esa_settings = array(
	'post_types' => array('post', 'page'),
	'add_media_entry' => 'Eagle Storytelling Application'
);

require_once('esa_datasource.class.php');
require_once('esa_item.class.php');
require_once('esa_item_transfer.class.php');


/**
 * ******************************************* Installation
 */
function esa_install () {

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	global $wpdb;
	
	// need a special table for that cache
	$table_name = $wpdb->prefix . "esa_item_cache";
	$sql =
	"CREATE TABLE $table_name (
		source VARCHAR(12) NOT NULL,
		id VARCHAR(200) NOT NULL,
		content LONGTEXT NULL,
		searchindex TEXT NULL,
		url TEXT NULL,
		timestamp DATETIME NOT NULL,
		latitude FLOAT NULL,
		longitude FLOAT NULL
		PRIMARY KEY  (source, id)
	)
	COLLATE utf8_general_ci
	ENGINE = MYISAM
	;";

	dbDelta( $sql );

	// because esa_item has wo columns as index, we can't solve this with a taxonomy...
	$table_name = $wpdb->prefix . "esa_item_to_post";
	$sql =
	"CREATE TABLE $table_name (
		post_id BIGINT(20) UNSIGNED NOT NULL,
		esa_item_source VARCHAR(12) NOT NULL,
		esa_item_id VARCHAR(200) NOT NULL
	)
	COLLATE utf8_general_ci
	ENGINE = MYISAM
	;";

	dbDelta( $sql );

}

register_activation_hook( __FILE__, 'esa_install');


		
		
/**
 *  ******************************************* Settings page
 */

add_action('admin_menu', function () {
	
	
	//create new top-level menu
	add_menu_page('Eagle Storytelling Application', 'Eagle Storytelling Application', 'administrator', __FILE__, function() {
		
		global $esa_settings;
		$url = admin_url('admin.php');
		
		$datasources = json_decode(get_option('esa_datasources'));
		if (!is_array($datasources)) {
			$datasources  = array();
		}
		
		//update_option('esa_datasources') = json_encode($list);
		
		$dsfiles = glob(dirname(__file__) . "/datasources/*.class.php");
		
		echo "<div class='wrap'><h2>Eagle Storytelling Application</h2>";
		
		
		echo "<h3>Settings</h3>";
	
		echo "<form method='POST' action='$url'>";
		echo "<h4>Available Data Sources</h4>";
		$labels = array();
		foreach ($dsfiles as $filename) {
			$name = basename($filename, '.class.php');
			$ds = get_esa_datasource($name);
			$label = $ds->title;
			$labels[$name] = $label;
			try  {
				$is_ok = true;
				$status = $ds->dependency_check();
			} catch(\exception $e) {
				$is_ok = false;
				$status = $e->getMessage();
			}
			$status = ($is_ok === true) ? "<span style='color:green'>($status)</span>" : "<span style='color:red'>(Error: $status)</span>";
			$checked = ((in_array($name, $datasources)) and ($is_ok === true)) ?  'checked="checked"' : '';
			$disabled = ($is_ok === true) ? '' : 'disabled="disabled"';
			echo "<div><input type='checkbox' name='esa_datasources[]' value='$name' id='esa_activate_datasource_$name' $checked $disabled /><label for='esa_activate_datasource_$name'>$label $status</label></div>";
		}
		update_option('esa_datasource_labels', json_encode($labels));
		wp_nonce_field('esa_save_settings', 'esa_save_settings_nonce');
		echo "<input type='hidden' name='action' value='esa_save_settings'>";
		echo "<input type='submit' value='Save' class='button button-primary'>";
		echo "</form>";
		
		
		
		echo "<h3>Cache</h3>";
		echo "<form method='POST' action='$url'>";
    	echo "<input type='hidden' name='action' value='esa_flush_cache'>";
    	echo "<input type='submit' value='Flush esa_item cache!' class='button'>";
		echo "</form>";
		echo "<form method='POST' action='$url'>";
		echo "<input type='hidden' name='action' value='esa_refresh_cache'>";
		echo "<input type='submit' value='Refresh esa_item cache!' class='button'>";
		echo "</form>";
		
		echo "</div>";
	});

});

/**
 * the caching mechanism for esa_items
 *
 * how it works: everytime a esa_item get displayed, it look in the cache if there is a non expired cache of this item. if not,
 * it fetches the contents from the corresponding api and caches it
 * that has two reasons:
 * - we want to make the embedded esa_items searchable
 * - page loading would be quite slow, if every items content had to be fetched again from the api
 *
 * how long may content be kept in cache? that has to be diskussed.
 *
 *
 */

add_action('admin_action_esa_flush_cache', function() {
	global $wpdb;
	
	$sql = "truncate {$wpdb->prefix}esa_item_cache;";
	
	$wpdb->query($sql);
	
    wp_redirect($_SERVER['HTTP_REFERER']);
    exit();
	
});

add_action('admin_action_esa_refresh_cache', function() {
	global $wpdb;

	echo "<ul><li>Cache empty</li>";
	
	$sql = "truncate {$wpdb->prefix}esa_item_cache;";

	$wpdb->query($sql);
	
	$sql = "
		select
			esa_item_source as source,
			esa_item_id as id
		from
			 {$wpdb->prefix}esa_item_to_post
		 
		group by
			esa_item_source,
			esa_item_id
	";
	
	foreach ($wpdb->get_results($sql) as $row) {
		$item = new \esa_item($row->source, $row->id);
		$item->html(true);
		$e = count($item->errors);
		echo "<li>{$item->id} of {$item->source} cached with $e errors.</li>";
	}
	
	echo "</ul>";


	wp_redirect($_SERVER['HTTP_REFERER']);
	exit();

});



add_action('admin_action_esa_save_settings' ,function() {
	if (!check_admin_referer('esa_save_settings', 'esa_save_settings_nonce')) {
	   echo "Nonce failed";
	}
	
	print_r($_POST);
	if (isset($_POST['esa_datasources'])) {
		update_option('esa_datasources', json_encode(array_map('sanitize_text_field', $_POST['esa_datasources'])));
	}		
	wp_redirect($_SERVER['HTTP_REFERER']);
    exit();
});



/**
 * ******************************************* Register style sheets and javascript
 */

add_action('wp_enqueue_scripts', function() {
	global $post;

	if (is_esa($post->post_type)) {

		// css
		wp_enqueue_style('thickbox');
		
		wp_register_style('eagle-storytelling', plugins_url('eagle-storytelling/css/eagle-storytelling.css'));
		wp_enqueue_style('eagle-storytelling' );
		
		wp_register_style('esa_item', plugins_url('eagle-storytelling/css/esa_item.css'));
		wp_enqueue_style('esa_item');
		
		esa_item_special_styles();
		
		
		//js
		wp_enqueue_script('esa_item.js', plugins_url() .'/eagle-storytelling/js/esa_item.js', array('jquery'));
		wp_enqueue_script('thickbox');

		wp_localize_script('esa_item.js', 'esa', array('ajax_url' => admin_url('admin-ajax.php')));
		//wp_enqueue_script('leaflet.js', 'http://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/leaflet.js');
		
	}
});

add_action('admin_init', function() {

	/**
	 * tinyMCE with esa_objects
	 */

	add_filter("mce_external_plugins", function($plugin_array) {
		$plugin_array['esa_mce'] = plugins_url() . '/eagle-storytelling/js/esa_mce.js';
		$plugin_array['esa_item'] = plugins_url() . '/eagle-storytelling/js/esa_item.js';
		//$plugin_array['noneditable'] = plugins_url() . '/eagle-storytelling/js/mce_noneditable.js';
		add_editor_style(plugins_url() .'/eagle-storytelling/css/esa_item.css');
		add_editor_style(plugins_url() .'/eagle-storytelling/css/esa_item-mce.css');
		return $plugin_array;
	});
	
	//wp_enqueue_script('leaflet.js', 'http://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/leaflet.js');

	add_filter('mce_buttons', function($buttons) {
		array_push($buttons, 'whatever');
		return $buttons;
	});

	// stylesteeht
	wp_enqueue_style('esa_item-admin', plugins_url() .'/eagle-storytelling/css/esa_item-admin.css');
	esa_item_special_styles();
});

// registers additional stylesheet for enabled datasources
function esa_item_special_styles() {

	$datasources = json_decode(get_option('esa_datasources'));
	if (!is_array($datasources)) {
		$datasources  = array();
	}
	$css = array();
	foreach ($datasources as $ds) {
		$dso = get_esa_datasource($ds);
		
		if (!$dso) {
			continue;
		}
		
		// stylsheets
		$cssInfo = $dso->stylesheet();
		if (isset($cssInfo['css']) and $cssInfo['css']) {
			$css[$cssInfo['name']] = "\n\n/* {$cssInfo['name']} styles ($ds)  */\n" . $cssInfo['css']; // names to avoid dublication if some datasources share the same styles e. g. epidoc
		}
		if (isset($cssInfo['file']) and $cssInfo['file']) {
			wp_enqueue_style('esa_item_' . $cssInfo['name'], $cssInfo['file']);
		}


	}

	wp_add_inline_style('esa_item', implode('\n', $css));

};



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
					echo "<pre>", print_r($atts,1), "</pre>";
						
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
	
	$args = func_get_args();
	
	if (!$query->is_main_query()) {
		return $sql;
	}
	
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
		$sqlr = "AND (({$wpdb->prefix}posts.ID in ($sqst $where)) or (1 = 1 $sql))";
	}
	if (isset($wp_query->query['esa_item_source']) and isset($wp_query->query['esa_item_id'])
		and $wp_query->query['esa_item_source'] and $wp_query->query['esa_item_id']) {
		$story = true;
		$where = "esai.id = '{$wp_query->query['esa_item_id']}' and esai.source = '{$wp_query->query['esa_item_source']}'";
		$sqlr = "AND {$wpdb->prefix}posts.ID in ($sqst $where)";
	}
	
	
	//echo "<pre>"; print_r($wp_query->query); die($sql);
	
	if (($wp_query->query['post_type'] != 'story') or (!$sql and !$story)) { //@todo: enable generic version 
		return $sql;
	}
	
	//echo '<pre style="border:1px solid red; background: silver">', $sql, '</pre>';				
	//echo '<pre style="border:1px solid red; background: silver">', print_r($sqlr, 1), '</pre>';
	
	return $sqlr;
	
}, 10, 2);


/* 
// view Query
add_action('found_posts', function() {
	global $wp_query;
	if (is_search()) {
		echo '<pre style="border:1px solid red; background: silver">', print_r($wp_query->request, 1), '</pre>';
	}
});*/



/** 
 * *******************************************add media submenu!
 */

// add them to media menu

add_filter('media_upload_tabs', function($tabs) {
	global $post;
	global $esa_settings;
	return (!is_object($post) or is_esa($post->post_type)) ?
    	array_merge($tabs, array('esa' => $esa_settings['add_media_entry'])) :
		$tabs;
});

// create submenu

add_action('media_upload_esa', function() {
	
	add_action('admin_print_styles-media-upload-popup', function() {
		wp_enqueue_style('colors');
		wp_enqueue_style('media');
		wp_enqueue_style('media-views');
		wp_enqueue_style('thickbox');
		wp_enqueue_style('esa_item', plugins_url() .'/eagle-storytelling/css/esa_item.css');
		esa_item_special_styles();
		wp_enqueue_style('esa_item-mediaframe', plugins_url() .'/eagle-storytelling/css/esa_item-mediaframe.css');
	});
	
	
	add_action('admin_print_scripts-media-upload-popup', function() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('thickbox');
		wp_enqueue_script('esa_item.js', plugins_url() .'/eagle-storytelling/js/esa_item.js', array('jquery'));
		wp_enqueue_script('esa_mediamenu.js', plugins_url() .'/eagle-storytelling/js/esa_mediamenu.js', array('jquery'));
		
	});
	
	/**
	 * this builds the iframe wich is in fact the add media dialogue of our plugin!
	 *
	 */
	return wp_iframe('media_esa_dialogue'); 
});


function media_esa_dialogue() {
	
	$time = microtime(true);
	
	global $esa_settings;
	$esa_datasources = json_decode(get_option('esa_datasources'));
	$labels = (array) json_decode(get_option('esa_datasource_labels'));
	// get current search engine
	$eds = get_esa_datasource(isset($_GET['esa_source']) ? $_GET['esa_source'] : $esa_datasources[0]);
	
	$post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;
	$item_id = isset($_GET['esa_id']) ? $_GET['esa_id'] : null;
	
	//media_upload_header();
	
	echo "<div id='esa-mediaframe'>";
	
	echo "<div class='media-frame-router'>";
	echo "<div class='media-router'>";
	
	// create search engine menu
	foreach ($esa_datasources as $source) {
		$sel = ($source == $engine) ? 'active' : '';
		$label = $labels[$source];
		echo "<a class='media-menu-item $sel' href='?tab=esa&esa_source=$source'>$label</a>";
	}
	echo "</div>";
	echo "</div>"; //esa_item_list_sidebar
	
	
	//Sidebar
	echo "<div id='esa_item_list_sidebar'>";
	echo "<div id='esa_item_preview' class='esa_item esa_item_$engine'></div>";
	
	echo '<div id="esa_item_settings"><form>';
	echo '<p>Some <strong>optional</strong> parameters to define <br />the looks of your Item. Leave out for <a title="reset to default settings" href="#" onclick="esa_ds.reset_form()"> default</a>.</p>';
	
	echo '<div class="esa_item_setting">';
	echo '<label for="height">' . __('Height') . '</label>';
	echo '<input type="number" min="0" name="height" value="">';
	echo "</div>";
	
	echo '<div class="esa_item_setting">';
	echo '<label for="width">' . __('Width') . '</label>';
	echo '<input type="number" min="0" name="width" value="">';
	echo "</div>";
	
	echo '<div class="esa_item_setting">';
	echo '<label for="align">' . __('Align') . '</label>';
	echo '<select height="1" name="align">
			<option value="" selected>none</option>
			<option value="left">Left</option>
			<option value="right">Right</option>
		</select>';
	echo "</div>";
	
	if (count($eds->optional_classes)) {
		echo '<div class="esa_item_setting">';
		echo '<label for="mode">' . __('Modus') . '</label>';
		echo '<select height="1" name="mode">
				<option value="" selected>none</option>';
		foreach ($eds->optional_classes as $key => $caption) {
			echo "<option value='$key'>$caption</option>";
		}
		echo '</select>';
	
		echo "</div>";
	}
	
	echo "</form></div>";
	
	
	echo "</div>"; //esa_item_list_sidebar
	
	echo "<div class='media-frame-content'>";
	
	echo "<div class='attachments-browser'>";

	
	//echo "<h3 class='media-title'>{$eds->title}</h3>";
	$query = $item_id ? $eds->api_record_url($item_id) : null;
	$success = $eds->search($query);	
	$eds->search_form();
	echo '<div id="media-items">';
	if ($success) {
		$eds->show_result();
	} else {
		$eds->show_errors();
	}
	echo '</div>'; //media-items
	
	echo '</div>'; //attachments-browser
	
	echo "</div>"; //media-frame-content
	
	echo "<div class='media-frame-toolbar'>";
	echo "<div class='media-toolbar'>";
	echo '<div class="media-toolbar-primary search-form">';
	echo '<input type="button" class="button button-primary media-button" id="go_button" disabled="disabled" onclick="esa_ds.insert()" value="' . esc_attr__('Insert into Story') . '" />';
	echo "</div>"; //media-toolbar-primary search-form
	echo "</div>"; //media-toolbar
	echo "</div>"; //media-frame-toolbar

	echo "</div>"; // esa-mediaframe
	//echo "<div class='timestamp'>Time: ", microtime(true) -$time, "</div>";
}


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
	
	// shortcode
	add_shortcode('esa', 'esa_shortcode');

});




function esa_shortcode($atts, $context) {
	
	if (!isset($atts['source']) or !isset($atts['id'])) {
		return;
	}
	
	$css = array();
	$classes = array();
	$tags = array();
	
	if (isset($atts['height'])) {
		$css['height'] = $atts['height'] . 'px';
	}
	if (isset($atts['width'])) {
		$css['width'] = $atts['width'] . 'px';
	}

	if (isset($atts['align'])) {
		if ($atts['align'] == 'right') {
			$classes[] = 'esa_item_right';
			//$css['float'] = 'left';
		} elseif ($atts['align'] == 'left') {
			$classes[] = 'esa_item_left';
			//$css['float'] = 'right';
		} else {
			$classes[] = 'esa_item_none';
			//$css['float'] = 'none';
		}
	}
	
	$item = new esa_item($atts['source'], $atts['id'], false, false, $classes, $css);

	
	
	return $item->html(true);
	

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



/**
 *  ******************************************* thumbnailing
 *  esa thumbnail > regular thumbnail
 *  
 */

add_action('wp_ajax_esa_set_featured_image', function() {

	if (isset($_POST['image_url']) and isset($_POST['post'])) {

		$image_url = $_POST['image_url'];
		$post_id = $_POST['post'];
		
		delete_post_meta($post_id, 'esa_thumbnail');
		
		if (($image_url != '%none%') and !add_post_meta($post_id, 'esa_thumbnail', $image_url, true)) {
			update_post_meta($post_id, 'esa_thumbnail', $image_url);
		}


		wp_send_json_success(array('image_url' => $image_url));

	}
	
	wp_send_json_error(array(
		'type' => 'esa',
		'message' => 'set featured image error',
		'post' => $_POST
	));
});


function esa_thumbnail($post, $return = false) {
	
	// check if esa thumbnail exists
	if ($esa_thumbnail_url = get_post_meta($post->ID, 'esa_thumbnail', true)) {
		$thumbnail = "<img src='$esa_thumbnail_url' alt='thumbnail' />";
	}
	
	// check if regular thumbnail exists
	if (!$thumbnail) {
		$thumbnail = get_the_post_thumbnail($post->ID, array(150, 150));
	}

	if (!$return) {
		echo "<div class='story-thumbnail'>$thumbnail</div>";
	}

	return $thumbnail;
	
} 

add_filter('admin_post_thumbnail_html', function($html) {
	
	global $post;
	

	// check if esa or regular thumbnail exists
	$esa_thumbnail_url = get_post_meta($post->ID, 'esa_thumbnail', true);
	$class = $esa_thumbnail_url ? 'hasEsathumbnail' : '';
	
	$reg_thumbnail = get_the_post_thumbnail($post->ID);
	$class .= $reg_thumbnail_url ? 'hasthumbnail ' : '';
	


	
	return "<div id='esa_thumbnail_chooser' class='$class'>
				<span id='esa_thumbnail_current_esa'>
					<img id='esa_thumbnail_admin_picture' src='$esa_thumbnail_url' alt='' /></p>
					<p><a href='#' id='esa_unfeatured_btn'>Remove featured image</a></p>
				</span>
				<span id='esa_thumbnail_set_esa' style='display: none;'>
					<p><a href='#' id='esa_featured_btn'>Use selected item as featured Image</a></p>
				</span>
				
				<span id='esa_thumbnail_set_other'>
					<p>Select thumbanil from harddrive or media library:</p>
					$html
				</span>
			</div>
";
});








/**
 * ******************************************* useful functions
 */
function is_esa($post_type) {
	global $is_esa_story_page;
	global $esa_settings;
	//print_r($esa_settings['post_types'] );die();
	return (in_array($post_type, $esa_settings['post_types'])) or $is_esa_story_page;
}

function get_esa_datasource($engine) {
	// get engine interface
	if (!$engine or !file_exists(plugin_dir_path(__FILE__) . "datasources/$engine.class.php")) {
		echo "Error: Search engine $engine not found!"; return;
	}
	
	require_once(plugin_dir_path(__FILE__) . "datasources/$engine.class.php");
	$ed_class = "\\esa_datasource\\$engine";
	return new $ed_class;
}


function esa_item_map() {
	echo "<div id='esa_items_overview_map'>Map loading</div>";
}

add_action('wp_ajax_esa_get_overview_map', function() {
	
	global $esa_settings;
	global $wpdb;
	
	$post_types = "'" . implode("', '", $esa_settings['post_types']) . "'";
	
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
});

?>