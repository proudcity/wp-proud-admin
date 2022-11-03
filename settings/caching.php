<?php
class ProudCachingSettingsPage extends ProudSettingsPage
{
    /**
     * Start up
     */
    public function __construct()
    {

      /**
       * Allows other plugins to add to our settings without embedding settings
       * and increasing plugin dependencies. You must add to this so that our
       * options will save as expected.
       *
       * @since 2022.11.03
       * @author Curtis
       *
       * @param   array             Array of current options
       */
      $caching_options = apply_filters( 'pc_admin_caching_options',
        array(
          // 'option_name' => '', // yeah you do not need anything in the second item
        )
      );


      parent::__construct(
        'caching', // Key
        [ // Submenu settings
          'parent_slug' => 'proudsettings',
          'page_title' => 'Caching',
          'menu_title' => 'Caching',
          'capability' => 'edit_proud_options',
        ],
        '', // Option
        $caching_options
      );

    }

    /**
     * Sets fields
     */
    public function set_fields( ) {
      $caching_fields_array = [
        'clear_cache' => [
          '#type' => 'html',
          '#html' =>
            '<h3>' . __pcHelp('Clear Cache') . '</h3>' .
            '<a class="btn btn-default" id="pc_clear_cache" href="#">Clear Cache</a>' .
            '<p class="message"></p>'
        ],

      ];

      /**
       * Adds fields to the form. This WILL NOT save the fields see pc_admin_caching_options for
       * the key you need to add to have your displayed fields save.
       *
       * @since 2022.11.03
       * @author Curtis
       *
       * @param     array       $caching_fields_array                    Array of existing fields that we can modify
       */
      $this->fields = apply_filters( 'pc_admin_caching_settings', $caching_fields_array );

    }

    /**
     * Print page content
     */
    public function settings_content() {
      $this->print_form( );
    }
}

if( is_admin() ){
    $proud_caching_settings_page = new ProudCachingSettingsPage();
}


class Proud_Caching{

	private static $instance;

	/**
	 * Spins up the instance of the plugin so that we don't get many instances running at once
	 *
	 * @since 1.0
	 * @author SFNdesign, Curtis McHale
	 *
	 * @uses $instance->init()                      The main get it running function
	 */
	public static function instance(){

		if ( ! self::$instance ){
			self::$instance = new Proud_Caching();
			self::$instance->init();
		}

	} // instance

	/**
	 * Spins up all the actions/filters in the plugin to really get the engine running
	 *
	 * @since 1.0
	 * @author SFNdesign, Curtis McHale
	 */
	public function init(){
        add_action( 'wp_ajax_proud_clear_cache', array( $this, 'clear_cache' ) );
	} // init


	public function clear_cache(){
		check_ajax_referer( 'proud_caching_ajax_nonce', 'security' );

        $message = 'Cache did NOT clear. Please contact a site administrator.';

        if ( function_exists( 'rocket_clean_domain') ){
            rocket_clean_domain();
            $message = 'Domain cache was cleared. ';
        }

        if ( function_exists( 'rocket_clean_minify') ){
            rocket_clean_minify();
            $message .= 'Scripts and styles were cleared.';
        }

		$success = false;
		//$message = 'Cache was cleared. It may take a few minutes for changes to be seen.';

		$data = array(
			'success' => (bool) $success,
			'message' => wp_kses_post( $message ),
		);

		wp_send_json_success( $data );
	} // clear_cache


}

Proud_Caching::instance();