/*
ICS Calendar front end AJAX
https://icscalendar.com
*/


jQuery(window).on('load', function() {

	if (jQuery('.r34ics-ajax-container').length > 0) {
		jQuery('.r34ics-ajax-container').each(function() {
			var r34ics_elem = jQuery(this);
			jQuery(this).addClass('loading');
			jQuery.ajax({
				url: r34ics_ajax_obj.ajaxurl,
				data: {
					'action': 'r34ics_ajax',
					'r34ics_nonce': r34ics_ajax_obj.r34ics_nonce,
					'subaction': 'display_calendar',
					'args': jQuery(this).data('args'),
				},
				dataType: 'text',
				type: 'POST',
				success:function(data) {
					r34ics_elem.replaceWith(data);
					r34ics_init();
					// @todo Move to hook
					if (typeof r34icspro_init === 'function') { r34icspro_init(); }
					r34ics_show_hide_headers();
				},
				error: function(errorThrown){
					console.log(errorThrown);
				},
			});
		});
	}

});