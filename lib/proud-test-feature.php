<?php

class Proud_Test_Feature{

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
			self::$instance = new Proud_Test_Feature();
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

		add_action( 'plugins_loaded', array( $this, 'test_feature' ) );

	} // init

	/**
	 * Checks for option value in DB and alerts slack if NOT present
	 *
	 * @since 2024.10.30
	 * @author Curtis
	 *
	 * @uses get_option()						Returns the value of the option in the database
	 * @uses self::send_slack_message()			Sends the message
	 */
	public static function test_feature(){

		$feature = get_option('rg_gforms_captcha_private_key');

		if ( empty( $feature ) ){
			//update_option( 'sfn_test_absent', 'thing is NOT present ' . time());
			self::send_slack_message( 'gf_captcha_setup_notification', 'CAPTCHA is NOT setup on ' . site_url() . '.', 2628000 );
		} else {
			//update_option( 'sfn_test_present', 'thing IS present ' . time());
			self::send_slack_message( 'gf_captcha_setup_notification', 'Testing CAPTCHA notice on ' . site_url() . '.', 2628000 );
		}
	}

	/**
	 * Sends a slack message given message name and message content
	 *
	 * @since 2023.05.29
	 * @access private
	 * @author Curtis
	 *
	 * @param	string			$message_name			required			The name of the message for the transient
	 * @param	string			$message_content		required			The content that will be send to Slack
	 * @param	int				$time					optional			The time for the transient to live
	 * @uses	get_option()												returns option from WP database
	 * @uses	get_transient()												Returns transient value
	 * @uses	set_transient()												Sets transient
	 */
	private static function send_slack_message( $message_name, $message_content, $time = 3600 ){

		if ( 'local' === wp_get_environment_type() ){
			$slack_key = PROUD_SLACK_KEY;
		} else {
			$slack_key = getenv( 'PROUD_SLACK_KEY' );
		}

		$notified = get_transient( $message_name );

		if ( false === $notified ){

			$curl = curl_init( $slack_key );

			$message = array( 'payload' => json_encode( array( 'text' => $message_content ) ) );

			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $message );

			$result = curl_exec( $curl );
			curl_close( $curl );

			// default of 3600 setting our transient for 1 hour it keeps bugging us if the plugins are off
			set_transient( $message_name, true, (int) $time );

		} // notified

	} // send_slack_message

}

Proud_Test_Feature::instance();
