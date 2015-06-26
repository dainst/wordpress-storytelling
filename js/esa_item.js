jQuery(document).on('mouseenter', '.esa_item', function() {
	if(jQuery(this).find('.esa_item_inner').css('height').replace(/[^-\d\.]/g, '') > 200) {
		jQuery(this).find('.esa_item_resizebar').fadeIn('slow');	
	}
	
});

jQuery(document).on('mouseleave', '.esa_item', function() {
	jQuery(this).find('.esa_item_resizebar').fadeOut('slow');
});

jQuery(document).on('click', '.esa_item_resizebar', function() {
	jQuery(this).parents('.esa_item').toggleClass('esa_item_collapsed');
})