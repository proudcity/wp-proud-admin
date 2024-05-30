<?php
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
			$success = true;
            $message = 'Domain cache was cleared. ';
        }

        if ( function_exists( 'rocket_clean_minify') ){
			rocket_clean_minify();
			$success = true;
            $message .= 'Scripts and styles were cleared.';
        }


		$data = array(
			'success' => (bool) $success,
			'message' => wp_kses_post( $message ),
		);

		wp_send_json_success( $data );
	} // clear_cache

	/**
	 * Renders the cache clearing page
	 */
	public static function render_page(){

		$html = '';
		$html .= '<div class="form-group">';
			$html .= '<h2>Clear Cache</h2>';

			$html .= '<a class="btn btn-default" id="pc_clear_cache" href="#">Clear Cache</a>';
			$html .= '<p class="message"></p>';
		$html .= '</div><!-- /.form-group -->';

		echo $html;
	}


}

Proud_Caching::instance();
