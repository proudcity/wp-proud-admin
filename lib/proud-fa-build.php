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
			self::build_basic_fa();
		}

		$data = array(
			'success' => (bool) $success,
			'message' => wp_kses_post( $message ),
		);

		wp_send_json_success( $data );

	} // build_fa

	/**
	 * Build the FontAwesome icon strings for the free version
	 * 
	 * @since 2022.04.13
	 */
	private static function build_basic_fa(){

		/* full query
		$fa_query = 'query {
			release(version:"6.0.0") {
				icons {
				id
				label
				membership {
					free
				}
				}
			}
		}';
		*/

		/* demo shorter working query */
		$fa_query = 'query {
			search(version:"6.0.0", query:"square", first:15) {
				id
				label
				membership {
				  free
				}
			}
		  }';

		$icon_json = \FortAwesome\fa()->query( $fa_query );

		self::process_icon_json( $icon_json );

	} // build_basic_fa

	/**
	 * Processes the JSON and returns the icon classes we need
	 * 
	 * @since 2022.04.13
	 */
	private static function process_icon_json( $json ){

		$processed_icons = '';

		$decoded_json = json_decode( $json );
		$results = $decoded_json->data->search;

		foreach( $results as $r ){
			// skip if not in free
			if ( empty( $r->membership->free ) ) { continue; }

			$class = 'fa-' . $r->id;
			$style = $r->membership->free;
			update_option( 'sfn_icons', $class );
		}


		return $processed_icons;

	} // process_icon_json


}

Proud_FA_Build::instance();