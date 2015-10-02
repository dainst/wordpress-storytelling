/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | esa_item Javascript
 * @link 		http://www.eagle-network.eu/stories/
 * @author 		Philipp Franck
 *
 * 
 * Some Javascript functionality of the esa_items
 * 
 *
 */

jQuery(document).on('mouseenter', '.esa_item', function() {
	if(
		(jQuery(this).find('.esa_item_inner').css('height').replace(/[^-\d\.]/g, '') > 200) ||
		(jQuery(this).find('.esa_item_map').length)
	) {
		jQuery(this).find('.esa_item_resizebar').fadeIn('slow');	
	}
	
});


jQuery(document).on('mouseleave', '.esa_item', function() {
	jQuery(this).find('.esa_item_resizebar').fadeOut('slow');
});

jQuery(document).on('click', '.esa_item_resizebar', function() {
	jQuery(this).parents('.esa_item').toggleClass('esa_item_collapsed');
	
	if (mapDiv = jQuery(this).parents('.esa_item').find('.esa_item_map')[0]) {
		var mapId = jQuery(mapDiv).attr('id');
		//console.log(mapId);
		esa_maps[mapId].invalidateSize();
	}
	
});

var esa_maps = {}; // an array containing all maps from esa objects

// load leaflet if needed
jQuery(document).ready(function(){
	if (jQuery('.esa_item_map').length) {
		jQuery.getScript("http://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.js")
		.done(function( script, textStatus ) {
			//console.log( textStatus );
			// draw maps
			jQuery('.esa_item_map').each(function(k, mapDiv) {
				console.log(mapDiv);
				
				var mapId = jQuery(mapDiv).attr('id');
				var lat   = parseFloat(jQuery(mapDiv).data('latitude'));
				var long  = parseFloat(jQuery(mapDiv).data('longitude'));
				
				var shape  = jQuery(mapDiv).data('shape');

				
				//console.log(mapId);
				esa_maps[mapId] = L.map(mapId).setView([lat, long], 13);
	
				L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
				    attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
				}).addTo(esa_maps[mapId]);
	
				
				
				if (typeof shape !== "undefined") {
					console.log(shape);
					var poly = L.multiPolygon(shape).addTo(esa_maps[mapId]);
					esa_maps[mapId].fitBounds(poly.getBounds());
				} else {
					L.marker([lat, long]).addTo(esa_maps[mapId]);
				}
				
			})
		})
		.fail(function( jqxhr, settings, exception ) {
			console.log(exception)
		});
	}
})
