jQuery(document).ready(function($) {

    var clearButton = $('#pc_clear_cache');
    var group = $(clearButton).parent('.form-group')

    $(clearButton).after('<span class="spinner"></span>');

    var spinner = $(group).find('.spinner');
    var message = $(group).find('.message');

    $(document).on( 'click touchstart', '#pc_clear_cache', function(e){

    console.log('clicked');

        e.preventDefault();

        // giving some user feedback that stuff is happening
        $(spinner).css('visibility', 'visible');

        var data = {
            'action': 'proud_clear_cache',
            'security': ProudCaching.proud_caching_ajax_nonce
        };

        $.post( ProudCaching.ajaxurl, data, function( response ) {

			// hide spinner
            $(spinner).css('visibility', 'hidden');

			if ( true === response.data.success ){
                $(message).empty().append(response.data.message);
			} // yup

			if ( false === response.data.success ){
                $(message).empty().append(response.data.message);
			}

		}); // end ajax post
    });

});