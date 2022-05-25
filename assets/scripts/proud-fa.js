jQuery(document).ready(function($) {

    /**
     * @todo style the spinner so it lines up with the button as expected
     * @todo remove the console log statements
     */

    var faButton = $('#fa-generate');
    var group = $(faButton).parent('.form-group')

    $(button).after('<span class="spinner"></span>')

    var spinner = $(group).find('.spinner');
    var message = $(group).find('.message');

    var data = {
        'action': 'proud_build_fa',
        'security': ProudFaBuild.proud_fabuild_ajax_nonce
    };

    $(document).on('click', faButton, function(e){
        e.preventDefault();

        // giving some user feedback that stuff is happening
        $(spinner).css('visibility', 'visible');

        $.post( ProudFaBuild.ajaxurl, data, function( response ) {

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