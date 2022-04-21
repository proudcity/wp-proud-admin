jQuery(document).ready(function($) {

    /**
     * @todo style the spinner so it lines up with the button as expected
     * @todo remove the console log statements
     */

    var button = $('#fa-generate');
    var group = $(button).parent('.form-group')

    $(button).after('<span class="spinner"></span>')

    var spinner = $(group).find('.spinner');

    var data = {
        'action': 'proud_build_fa',
        'security': ProudFaBuild.proud_fabuild_ajax_nonce
    };

    $(document).on('click', button, function(e){
        e.preventDefault();
        
        // giving some user feedback that stuff is happening
        $(spinner).css('visibility', 'visible');

        $.post( ProudFaBuild.ajaxurl, data, function( response ) {

			// hide spinner
            $(spinner).css('visibility', 'hidden');      

            console.log(response);

			if ( true === response.data.success ){
				console.log( 'success' );
			} // yup

			if ( false === response.data.success ){
				console.log( 'fail' );
			}

		}); // end ajax post

    });

});