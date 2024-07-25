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
	 *
	 * @since 2022.04.21
	 * @author Curtis
	 *
	 * @uses 	check_ajax_referer 							Makes sure it's a secure ajax query with a nonce
	 * @uses 	self::build_basic_fa() 						Build our icon list
	 * @uses 	wp_kses_post() 								makes our content safe for display
	 * @uses 	wp_send_json_success() 						Returns response to ajax request
	 * @return 	$data 		array() 						Message from the ajax request
     */
	public function build_fa(){
		check_ajax_referer( 'proud_fabuild_ajax_nonce', 'security' );

		$success = false;
		$message = 'The rebuild did NOT even run. Talk to your site administrator.';

		$built = self::build_basic_fa();

		if ( true === $built ){
			$success = true;
			$message = 'Font Awesome list is rebuilt.';
		} else {
			$success = false;
			$message = 'Font Awesome NOT rebuilt. Talk to the site Administrator if you are having issues.';
		}

		$data = array(
			'success' => (bool) $success,
			'message' => wp_kses_post( $message ),
		);

		wp_send_json_success( $data );

	} // build_fa

	/**
	 * Makes our GraphQL query to Font Awesome via the WordPress Plugin for Font Awesome
	 *
	 * @since 2022.04.13
	 * @author Curtis
	 *
	 * @uses 	\FortAwesome\fa() 						Built in WP plugin call to the Font Awesome graphql API
	 * @uses 	self::process_icon_json() 				Processes API response and saves icons
	 */
	private static function build_basic_fa(){

		/* full query */
		$fa_query = 'query {
			release(version:"6.6.0") {
				icons {
				id
				label
				membership {
					free
					pro
				}
				}
			}
		}';

		$icon_json = \FortAwesome\fa()->query( $fa_query );

		return self::process_icon_json( $icon_json );

	} // build_basic_fa

	/**
	 * Processes the JSON and returns the icon classes we need
	 *
	 * @since 2022.04.13
	 * @author Curtis
	 *
	 * @param 	$json 			string 			required 			The json query from Font Awesome
	 * @uses 	set_transient() 									Sets our transient value
	 * @uses 	update_option() 									Saves our option to the database
	 * @return 	bool 			$success 							True if all options saved as expected
	 */
	private static function process_icon_json( $json ){

		$basic_icons = array();
		$pro_icons = array();

		$decoded_json = json_decode( $json );
		$results = $decoded_json->data->release->icons;

		foreach( $results as $r ){
			// skip if not in free
			if ( empty( $r->membership->free ) ) {
				// need to process pro icons here
				$icon_class = 'fa-' . $r->id;
				$style = $r->membership->pro;

				foreach( $style as $s ){
					$style_class = 'fa-' . $s;

					$pro_icons[] = $style_class . ' ' . $icon_class;
				}
			} // if empty free (so pro)

			$icon_class = 'fa-' . $r->id;
			$style = $r->membership->free;

			foreach( $style as $s ){
				$style_class = 'fa-' . $s;

				$basic_icons[] = $style_class . ' ' . $icon_class;
			} // foreach $style

		} // foreach $results

		delete_transient( 'fa_pro_icons_trans' );
		delete_transient( 'fa_basic_icons_trans' );
		delete_option( 'fa_pro_icons' );
		delete_option( 'fa_basic_icons' );

		$pro_trans = set_transient( 'fa_pro_icons_trans', $pro_icons, 2629746 );
		$pro_opt = update_option( 'fa_pro_icons', $pro_icons );

		$basic_trans = set_transient( 'fa_basic_icons_trans', $basic_icons, 2629746 );
		$basic_opt = update_option( 'fa_basic_icons', $basic_icons );

		$success = ( $pro_trans && $pro_opt && $basic_trans && $basic_opt ) ? true : false;

		return (bool) $success;

	} // process_icon_json


}

Proud_FA_Build::instance();
