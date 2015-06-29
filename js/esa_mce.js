/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | tiny mce plugin to view esa_items in editor (instead of shortcodes)
 * @link 		http://www.europeana.eu/
 * @author 		Philipp Franck
 *
 * Status: BETA 
 * 
 * We could have used 'wpview' perhaps, but it still count as experimental, so we prefer a more classy apporach to build a whole own plugin.
 * 
 * 
 */
tinymce.PluginManager.add('esa_item', function( editor ) {

	//console.log('mce esa plugin speaking');
		
	function replaceEsaShortcodes( content ) {
		return content.replace( /\[esa([^\]]*)\]/g, function( match ) {
			//console.log('replaceEsaShortcodes speaking', match);
			return html( match );
		});
	}

	function html( data ) {
		var encodedShortCode = window.encodeURIComponent(data);
		// do ajax
		jQuery.ajax({
			url: ajaxurl,
			type: 'post',
			data: {
				action: 'esa_shortcode',
				esa_shortcode: data
			},
			success: function(result) {
				//console.log('ajax success');
				//console.log(result.trim());
				jQuery('#content_ifr').contents().find('span.esa_item_wrapper[data-mce-esa-item="' + encodedShortCode + '"]').html(result.trim()); //
			},
			error: function(e) {
				console.log('ajax error');
				console.log(e);
			}
		}); 
		
		// data-mce-resize="true"  <- unfortunatley only works on images.
		return '<!-- esa_item --><span class="esa_item_wrapper mceNonEditable" data-mce-esa-item="' + encodedShortCode + '" data-mce-placeholder="1" >.</span><!-- /esa_item -->';
	}

	function restoreEsaShortcodes( content ) {
		
		/**
		 * for some reason mce works always with strings and regexes, not with objects. maybe it's faster. but it sucks.
		 */
		function getAttr( str, name ) {
			name = new RegExp(name + '=\"([^\"]+)\"').exec(str);
			return name ? window.decodeURIComponent(name[1]) : '';
		}
		
		return content.replace(/<!-- esa_item -->(.*?)<!-- \/esa_item -->/ig, function(match, match_with_p) {
			var data = getAttr(match, 'data-mce-esa-item');

			if (data) {
				return '<p>' + data + '</p>';
			}
			
			return match;
		});
	}

	/**
	 * prevent images in esa_item to be selected on click -> todo: does not work yet!
	 */
	editor.on( 'mousedown', function( event ) {
		var dom = editor.dom,
			node = event.target;
		if ( jQuery(node).parents('span.esa_item_wrapper') ) {
			event.stopPropagation();
		}
	});
	
	
	// Display esa_item content instead of shortcode
	editor.on( 'ResolveName', function( event ) {
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

	editor.on( 'BeforeSetContent', function( event ) {
		//console.log('BeforeSetContent 2 speaking');
		event.content = replaceEsaShortcodes( event.content );
	});

	editor.on( 'PostProcess', function( event ) {
		//console.log('PostProcess speaking');
		if ( event.get ) {
			event.content = restoreEsaShortcodes( event.content );
		}
	});
});