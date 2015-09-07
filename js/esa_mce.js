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
 * TODO
 * 
 * * make them editable (align, size etc)
 * * prevent dblclick
 * * when only one is prevent no p error
 * * make them deletable (remove comments on delete...)
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
//[esa source="europeana" id="/2020707/11B9BDAA1657DE3C2085185DF144E846F35A7898"]
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
				jQuery('#content_ifr').contents().find('div.esa_item_wrapper[data-mce-esa-item="' + encodedShortCode + '"]').html(result.trim() + '<div class="esa_item_overlay">&nbsp;</div>'); //
			},
			error: function(e) {
				console.log('ajax error');
				console.log(e);
			}
		}); 
		
		// data-mce-resize="true"  <- unfortunatley only works on images.
		return '<!-- esa_item --><div class="esa_item_wrapper mceNonEditable" data-mce-esa-item="' + encodedShortCode + '" data-mce-placeholder="1" >.</div><!-- /esa_item -->';
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

	/**
	 * prevent images in esa_item to be selected on click -> todo: does not work yet!
	 */
	editor.on('mousedown', function(event) {
		console.log("hallo", event);
		var dom = editor.dom,
			node = event.target;
		if (jQuery(node).parents('div.esa_item_wrapper').length) {
			console.log('teaparty');
			event.stopImmediatePropagation();
			event.preventDefault()
		}
	});
	/*
	editor.on( 'dblclick', function( event ) {
		console.log("dallo", event);
		var dom = editor.dom,
			node = event.target;
		if ( jQuery(node).parents('div.esa_item_wrapper') ) {
			console.log('deaparty');
			event.stopImmediatePropagation();
			event.preventDefault()
		}
	});
	*/
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