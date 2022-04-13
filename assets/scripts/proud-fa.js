jQuery(document).ready(function($) {

    /**
     * @todo style the spinner so it lines up with the button as expected
     */

    var button = $('#fa-generate');
    var group = $(button).parent('.form-group')

    $(button).after('<span class="spinner"></span>')

    var spinner = $(group).find('.spinner');

    $(document).on('click', button, function(e){
        e.preventDefault();
        
        // giving some user feedback that stuff is happening
        $(spinner).css('visibility', 'visible');

        $.post( AllEmail.ajaxurl, data, function( response ) {

			// @todo hide spinner
            $(spinner).css('visibility', 'hidden');           

			if ( true === response.data.success ){
				console.log( 'success' );
			} // yup

			if ( false === response.data.success ){
				console.log( 'fail' );
			}

		}); // end ajax post

    });

});