/**
 * esa_mediamenu.js
 *
 */

jQuery(document).ready(function() {
	
	jQuery('#esa_ds_search_button').on('click', function(){
		console.log(jQuery('#esa_ds_search_button').data('apiurl'));
		
		jQuery.ajax({
			method: "GET",
			url: jQuery(this).data('apiurl')
		}).done(function(r) {
			console.log(r)
		}).fail(function(e) {
			console.log("error: ", e);
		});
		
		
	});
	
});