/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | tiny mce plugin to view esa_items in editor (instead of shortcodes)
 * @link 		http://www.eagle-network.eu/stories/
 * @author 		Philipp Franck
 *
 * Status: BETA 
 * 
 * 
 * 
 * 
 */

(function(window, views, media, $) {
	window.wp.mce.views.register('esa', _.extend({}, {
		
		initialize: function() {
			var self = this;
			//console.log('initialize', self);
			
			//check if this.shortcode has the necessary attrs [esa id="1"] [esa id="Bildhauerei@de" source="wiki"]
			if (typeof self.shortcode.attrs.named.id === 'undefined' || typeof self.shortcode.attrs.named.source === 'undefined') {
				//console.log('unsufficient shortcode n stuff');
				self.ignore = true;
				self.removeMarkers();
				self.loader = false;
				return self.text;
			}
			
			$.ajax({
				url: ajaxurl,
				type: 'post',
				data: {
					action: 'esa_shortcode',
					shortcode: self.encodedText
				},
				success: function(response) {
					response = JSON.parse(response);
					//console.log('getContent.success', response);
			        self.render(response.esa_item);

			        self.getNodes(function(editor, node, contentNode) {
			        	$(node).find('.esa_item').esa_item();	
			        });
				},
				error: function(e) {
					//console.log('ajax error');
					//console.log(e);
				}
			}); 
			
		},

		edit: function(text, update) {
			console.log('shortcode edit overwritten');
			console.log(this, update);
			
			var source = this.shortcode.attrs.named.source;
			var id = this.shortcode.attrs.named.id;
			tb_show('Eagle Storytelling Application', 'media-upload.php?tab=esa&esa_source='+source+'&amp;esa_id='+id+'&amp;TB_iframe=true');
		}
	}))

})(window, window.wp.mce.views, window.wp.media, window.jQuery);


(function(window, views, media, $) {
	window.wp.mce.views.unregister('embedURL'); //this replaces embedURL
	var embed = window.wp.mce.views.get('embed'); // and extenses embed
	window.wp.mce.views.register('esa_url',  _.extend({}, embed.prototype, {

		match: function(content) {
			var re = /(^|<p>)(https?:\/\/[^\s"]+?)(<\/p>\s*|$)/gi,
				match = re.exec( content );
			//console.log('url match', content, match);
			if (match) {
				//console.log('url matched');
				return {
		            index: match.index + match[1].length,
		            content: match[2],
		            options: {
		            	url: true
		            }
				}
			}
		},

		initialize: function() {

			console.log('url:initialize', this);		
			
			var self = this;
			
			wp.ajax.post('esa_url_checker', {
				esa_url: self.encodedText,
				post_ID : media.view.settings.post.id
			})
			.done(function(response) {
				// it may succeed in two different ways:
				//console.log('getContent.success', response, self);

				// 1. url could be recognized by wordpres build-in url embed functions (like youtube)
				if (typeof response.esa_item === 'undefined') {
					//console.log('no esa match, but embed match',response);
					self.render(response);
					return;
				} 
				
				// 2. url could be recognized by esa (replace url shortcode and display item)
				console.log('esa match');
				self.getNodes(function(editor, node) {
					console.log('update');
					self.update(response.shortcode, editor, node, true);
				}); 

			})
				
			.fail( function( response ) {
				// it may fail due to two second reasons:
				console.log('url checker fail',response);
				
				// 1. there is an actual error
				if (response.type === 'esa_error') {
					self.setError(response.message || response.statusText, 'admin-media');
					return;
				}	
					
				// 2. the url is not recongnized by esa nor by wp embed and shall remain untouched
				self.getNodes(function(editor, node, contentNode) {
					$(node).replaceWith(self.text);
					//console.log(node);						
				});
				
				/*
				self.replaceMarkers();
				self.unbind();
				self.setContent(self.text);
				self.ignore = true;
				self.loader= false;
				self.removeMarkers();
				*/

			} );
			
			return;
		}
		
	
	}))

})(window, window.wp.mce.views, window.wp.media, window.jQuery);


tinymce.PluginManager.add('esa_item', function(editor) {
	editor.on('NodeChange', function(event) {
		node = jQuery(tinyMCE.activeEditor.selection.getNode()).parents('.wpview-body').find('.esa_item');
		//console.log('nodeChange', node);
		jQuery('#esa_thumbnail_set_esa').toggle(node.length > 0);
	});
	
	
});


jQuery(document).on('click', '#esa_featured_btn', function() {
	node = jQuery(tinyMCE.activeEditor.selection.getNode()).parents('.wpview-body').find('.esa_item');
	//console.log(node);
	if (!node.length) {
		return;
	}
	var mainImage = jQuery(node).find('.esa_item_main_image');

    var bg_url = jQuery(mainImage).css('background-image');
    bg_url = /^url\((['"]?)(.*)\1\)$/.exec(bg_url);
    bg_url = bg_url ? bg_url[2] : ""; 
    //console.log(bg_url);
	
    esa_thumbnail_update(bg_url);
});

jQuery(document).on('click', '#esa_unfeatured_btn', function() {
    esa_thumbnail_update('%none%');
});

function esa_thumbnail_update(url) {
	wp.ajax.post('esa_set_featured_image',
		{
			image_url: url,
			post: jQuery('#post_ID').val()
		})
		.done(function(result) {
			//console.log('ajax success', result);
			
			jQuery('#esa_thumbnail_chooser').toggleClass('hasEsathumbnail', result.image_url != '' && result.image_url != '%none%');
			jQuery('#esa_thumbnail_admin_picture').attr("src", result.image_url);
			
		})
		.fail(function(e) {
			console.log('ajax error');
			console.log(e);
		});
}