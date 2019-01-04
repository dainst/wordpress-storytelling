/**
 * @package 	enhanced-storytelling
 * @subpackage	Search in Datasources | esa_item mediamenu Javascript
 * @link 		https://github.com/dainst/wordpress-storytelling
 * @author 		Philipp Franck
 *
 * 
 * Some Javascript functionality of the "Add Media" dialogue. Quite important.
 * 
 *
 */
var esa_ds = {
		
	selected: false,

	insert: function() {
		
		//console.log(esa_ds.selected);
		
		if (!esa_ds.selected) {
			return;
		}
			
		

		var options = {}; 
		var opts = '';
		jQuery('#esa_item_settings .esa_item_setting').find('input, select').each(function(k,v) {
			options[jQuery(v).attr('name')] = jQuery(v).val();
			if (jQuery(v).val()) {
				opts += ' ' + jQuery(v).attr('name') + '="' + jQuery(v).val() + '"' 
				
			}
		});
		console.log(options);
		console.log(opts);
		
		var html = '[esa source="' + jQuery(esa_ds.selected).data("source") + '" id="' + jQuery(esa_ds.selected).data("id") + '"' + opts + ']';
		
		
		var win = window.dialogArguments || opener || parent || top;
		win.send_to_editor(html);
		return false;
	},
	
	select: function() {
		//console.log(this);
		jQuery('.esa_item').removeClass('selected');
		if (this !== esa_ds.selected) {
			jQuery(this).addClass('selected');
			jQuery('#esa_item_preview').html(jQuery(this).html());
			jQuery('#go_button').prop('disabled', false);
			jQuery('#esa_item_preview').addClass('esa_item_collapsed');
			esa_ds.selected = this;			
		} else {
			esa_ds.selected = false;
			jQuery('#esa_item_preview').html('');
			jQuery('#go_button').prop('disabled', true);
		}

	},
	
	reset_form: function() {
		jQuery('#esa_item_settings').find('form')[0].reset();
	}

	
};

jQuery(document).ready(function() {
	jQuery('body').on('click', '.esa_item_list .esa_item',  esa_ds.select);
	
	jQuery('body').on('click', '.toggle', function(event) {
		jQuery('#' + jQuery(event.target).data('toggle')).toggle();
	})
	
});