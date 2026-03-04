<?php

class Proud_Custom_Profile{

	private static $instance;

	/**
	 * Spins up the instance of the plugin so that we don't get many instances running at once
	 *
	 * @since 1.0
	 * @author Curtis McHale
	 *
	 * @uses $instance->init()                      The main get it running function
	 */
	public static function instance(){

		if ( ! self::$instance ){
			self::$instance = new Proud_Custom_Profile();
			self::$instance->init();
		}

	} // instance

	/**
	 * Spins up all the actions/filters in the plugin to really get the engine running
	 *
	 * @since 1.0
	 * @author Curtis McHale
	 */
	public function init(){
        remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );
        add_filter( 'user_contactmethods', array( $this, 'remove_contact_methods' ), 99, 2 );

        // silly Javascript stuff to hide more profile stuff
        add_action( 'admin_head', array( $this, 'js_profile_cleaner' ) );

	} // init

    public static function js_profile_cleaner(){
        $screen = get_current_screen();

        if ( 'profile' === $screen->base ){ ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('h2:contains("Contact Info")').hide();
                    $('h2:contains("Personal Options")').hide();

                    $('tr.user-rich-editing-wrap').parents('.form-table').hide();
                });
            </script>
        <?php } // if profile === screen->base
    }

     /**
     * Removes a bunch of the available contact methods from the Contact Info area of the User Profile screen
     *
     * @since 2022.05.05
     * @author Curtis McHale
     *
     * @param   $methods        array           required            array of existing methods
     * @param   $user           object          optional            user object for the current user
     * @return  $methods        array                               the contact methods array as empty as I can make it
     */
    public static function remove_contact_methods( $methods, $user ){

        unset( $methods['facebook'] );
        unset( $methods['dbem_phone'] );
        unset( $methods['instagram'] );
        unset( $methods['linkedin'] );
        unset( $methods['myspace'] );
        unset( $methods['pinterest'] );
        unset( $methods['soundcloud'] );
        unset( $methods['tumblr'] );
        unset( $methods['twitter'] );
        unset( $methods['youtube'] );
        unset( $methods['wikipedia'] );

        return $methods;

    } // remove_contact_methods

}

Proud_Custom_Profile::instance();