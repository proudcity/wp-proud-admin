<?php

class Proud_FA_Build{

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
			self::$instance = new Proud_FA_Build();
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
        add_action( 'wp_ajax_proud_build_fa', array( $this, 'build_fa' ) );
	} // init

    /**
     * Builds Font Awesome strings for the database
     */
	public function build_fa(){
		check_ajax_referer( 'proud_fabuild_ajax_nonce', 'security' );

		$success = false;
		$message = 'FA woowoo';

        if ( true === \FortAwesome\fa()->pro() ){
            $message = 'generating pro';
			// need to do a pro query here
		} else {
            $message = 'not pro loser';
			// non pro query
		}

		$data = array(
			'success' => (bool) $success,
			'message' => wp_kses_post( $message ),
		);

		wp_send_json_success( $data );

	} // get_sku


}

Proud_FA_Build::instance();