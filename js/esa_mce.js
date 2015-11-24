/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | tiny mce plugin to view esa_items in editor (instead of shortcodes)
 * @link 		http://www.eagle-network.eu/stories/
 * @author 		Philipp Franck
 *
 * Status: BETA 
 * 
 * We could have used 'wpview' perhaps, but it still count as experimental, so we prefer a more classy apporach to build a whole own plugin.
 * 
 * 
 * 
 * 
 * 
 */
tinymce.PluginManager.add('esa_item', function(editor) {
	
	tinymce.esa = {
			featured_image: '',
			post_id: jQuery('#post_ID').val()
	}

	//console.log('mce esa plugin speaking');
		
	function replaceEsaShortcodes(content) {
		return content.replace(/\[esa([^\]]*)\]/g, function(match) {
			//console.log('replaceEsaShortcodes speaking', match);
			return html(match);
		});
	}

	function html(data) {
		
		var encodedShortCode = window.encodeURIComponent(data);
		// do ajax 
		
		jQuery.ajax({
			url: ajaxurl,
			type: 'post',
			data: {
				action: 'esa_shortcode',
				esa_shortcode: data,
				featured_image: tinymce.esa.featured_image,
				post_id: tinymce.esa.post_id
			},
			success: function(result) {
				//console.log('ajax success');
				
				result = JSON.parse(result);
				//console.log(result);
				if (typeof result.featured_image !== 'undefined') {
					tinymce.esa.featured_image = result.featured_image;
				}
								
				var esa_item_wrapper = jQuery('#content_ifr').contents().find('div.esa_item_wrapper[data-mce-esa-item="' + encodedShortCode + '"]');
				esa_item_wrapper.html(result.esa_item.trim());
				
				var bg_url = jQuery(esa_item_wrapper.find('.esa_item_main_image')[0]).css('background-image');
				var bg_url = /^url\((['"]?)(.*)\1\)$/.exec(bg_url);
				bg_url = bg_url ? bg_url[2] : "";
				
				var is_featured = (bg_url == tinymce.esa.featured_image) ? 'is_featured' : '';
				
				var overlay = jQuery('<div class="esa_item_overlay">')
				var tools = jQuery('<div class="esa_item_tools">');
				tools.append('<a href="#" class="esa_item_tools_delete" title="Delete">&nbsp;</a>');
				//tools.append('<a href="#" class="esa_item_tools_edit" title="Edit">&nbsp;</a>');
				
				if (bg_url) {
					tools.append('<a href="#" class="esa_item_tools_featured ' + is_featured + '" title="Set as Featured Image">&nbsp;</a>');
				}
				

				overlay.append(tools);
				esa_item_wrapper.append(overlay);

			},
			error: function(e) {
				console.log('ajax error');
				console.log(e);
			}
		}); 
		
		// data-mce-resize="true"  <- unfortunatley only works on images.
		return '<!-- esa_item --><div class="esa_item_wrapper mceNonEditable" data-mce-esa-item="' + encodedShortCode + '" data-mce-placeholder="1" >.</div><!-- /esa_item --> ';
	}

	function restoreEsaShortcodes(content) {
				
		/**
		 * for some reason mce works always with strings and regexes, not with objects. maybe it's faster. but it sucks.
		 */
				
		function getAttr(str, name) {
			//console.log(name);
			name = new RegExp(name + '=\"([^\"]+)\"').exec(str);
			return name ? window.decodeURIComponent(name[1]) : '';
		}
		
		return content.replace(/<!-- esa_item -->(.*?)<!-- \/esa_item -->/ig, function(match, match_with_p) {
			var data = getAttr(match, 'data-mce-esa-item');
			if (data) {
				return '' + data + '';
			}
			
			return match;
		});
	}

	function esa_thumbnail_update(url, item) {
		jQuery.ajax({
			url: ajaxurl,
			type: 'post',
			data: {
				action: 'esa_set_featured_image',
				image_url: url,
				post: tinymce.esa.post_id
			},
			success: function(result) {
				console.log('ajax success', result);
				
				var is_featured = jQuery(item).parents(document).find('.is_featured').removeClass('is_featured');
				
				if (result == 'ERROR') {
					console.log('error php side');
					return;
				}
				
				jQuery(item).toggleClass('is_featured', result != '');
				jQuery('#esa_thumpnail_content_1').toggle(result != '');
				jQuery('#esa_thumpnail_content_2').toggle(result == '');
				jQuery('#esa_thumpnail_admin_picture').attr("src", result);
				
			},
			error: function(e) {
				console.log('ajax error');
				console.log(e);
			}
		});
	}
	
	
	
	/**
	 * prevent images in esa_item to be selected on click -> todo: does not work yet!
	 */
	editor.on('mousedown', function(event) {
		
		//console.log("s", event.which, event);
		var dom = editor.dom,
			node = event.target;
		if (jQuery(node).parents('div.esa_item_wrapper').length) {
			event.stopImmediatePropagation();
			event.preventDefault();
			//jQuery(node).toggleClass('esa_item_overlay_selected');
			
			// tools
			if (event.which == 1) {
				
				var wrapper = jQuery(node).parents('div.esa_item_wrapper');
				//console.log(wrapper);
				
				// tool: delete
				if (jQuery(node).hasClass('esa_item_tools_delete')) {
					console.log('delete', wrapper);
					if (confirm('Really Delete?')) {
						esa_thumbnail_update('', node);
						jQuery(wrapper).remove();
					}
					
				// tool: featured item
				} else if (jQuery(node).hasClass('esa_item_tools_featured')) {
					
					if (jQuery(node).hasClass('is_featured')) {
						
					    esa_thumbnail_update('', node);
					    
					} else {

						var mainImage = jQuery(wrapper).find('.esa_item_main_image');
						//console.log('featured',mainImage);
					    var bg_url = jQuery(mainImage).css('background-image');
					    bg_url = /^url\((['"]?)(.*)\1\)$/.exec(bg_url);
					    bg_url = bg_url ? bg_url[2] : ""; 
					    console.log(bg_url);
						
					    esa_thumbnail_update(bg_url, node);
					}   
					
				// tool: edit item
				} else  if (jQuery(node).hasClass('esa_item_tools_edit')) {
					var window = wp.media({
			            title: 'Insert a media',
			            library: {type: 'image'},
			            multiple: false,
			            button: {text: 'Insert'}
			        });
				}
			}
		}
	});
	
	
	editor.on('dblclick', function(event) {
		var dom = editor.dom,
			node = event.target;
		if (jQuery(node).parents('div.esa_item_wrapper').length) {
			event.stopImmediatePropagation();
			event.preventDefault();
		}
	});
	
	// Display esa_item content instead of shortcode
	editor.on('ResolveName', function(event) {
		//console.log('ResolveName speaking');
		var dom = editor.dom,
			node = event.target;
		//console.log(node);
		if ( node.nodeName === 'DIV' && dom.getAttrib( node, 'data-mce-esa-item' ) ) {
			if ( dom.hasClass( node, 'esa_item' ) ) {
				event.name = 'esa_item';
			}
		}
	});

	editor.on('BeforeSetContent', function(event) {
		//console.log('BeforeSetContent 2 speaking');
		event.content = replaceEsaShortcodes( event.content );
	});

	editor.on('PostProcess', function(event) {
		//console.log('PostProcess speaking');
		if ( event.get ) {
			event.content = restoreEsaShortcodes( event.content );
		}
	});
	
	
	/**
	
	This was to make the esa_item functionality in the editor. But it doesn't work
	
	1. 
	http://stackoverflow.com/questions/3033206/inject-javascript-into-iframe-generated-by-tinymce-yui
	http://stackoverflow.com/questions/25924605/load-jquery-file-in-tinymce-iframe
	
	2.
	
	editor.on('init', function(args) {
		if (typeof esaJsPath === "undefined") {
			console.log('esaJsPath is undefined');
			return;
		}
		console.log('yolo');
		var esaDocument = args.target.getDoc();//jQuery('iframe#content_ifr')[0].contentWindow.document;
		//jQuery(jQuery('iframe#content_ifr')[0].contentDocument.body).append('<script type="text/javascript"> </script>');
		console.log(esaJqPath);
		editor.designMode = 'off';
		jQuery(esaDocument.head).append('<script type="text/javascript" src="' + esaJqPath + '"></script>');
		jQuery(esaDocument.head).append('<script type="text/javascript" src="' + esaJsPath + 'esa_item.js"></script>');
		jQuery(esaDocument.head).append('<script type="text/javascript">console.log(jQuery("p"))</script>');
		jQuery(esaDocument.head).append('<script type="text/javascript">console.log(jQuery(".esa_item"))</script>');
		editor.designMode = 'on';
	});
	
	3.
	jspath.js:
	var esaJsPath = "<?php echo $_GET['plugins_url'] . '/eagle-storytelling/js/'; ?>";
	var esaJqPath = "<?php echo $_GET['jq_url'] ?>";

	4.
	in add_action('init', function() { add_filter("mce_external_plugins", function($plugin_array) {
		$wp_scripts = wp_scripts();
		$jqurl = get_home_url() . $wp_scripts->registered['jquery-core']->src;
		plugin_array['jspath'] = plugins_url() . '/eagle-storytelling/js/jspath.php?plugins_url=' . plugins_url() . '&jq_url=' . $jqurl;

	
	*/
});