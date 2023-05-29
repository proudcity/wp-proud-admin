<?php

class Proud_Force_Theme{

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
			self::$instance = new Proud_Force_Theme();
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

		add_action( 'setup_theme', array( $this, 'force_theme_activation' ) );

	} // init

	public static function force_theme_activation(){

		$is_active = false;
		$has_parent = false;
		$default_theme = self::check_default_theme();
		$active_theme = wp_get_theme();

		// checks if the currently active theme has a parent theme
		$has_parent = self::theme_has_parent( $active_theme );


		$is_active = self::is_specified_theme_active( $default_theme, $active_theme );

		// is specified theme active?
			// 	alert slack if not active

		// does specified theme exist in our themes folder
			// if not alert slack
		// try to activate theme
			// alert slack if we could activate theme so don't worry
			// alert slack if we could NOT activate theme so do worry because that means it's not present
				// this should really just result in a pod restart so provide that information

	} // force_theme_activation

	/**
	 * Checks if a theme has a parent theme and lets us know if that parent theme is not active or not present
	 *
	 * @since 2023.05.29
	 * @author Curtis
	 * @access private
	 *
	 * @param	object		$active_theme		required					Active theme object
	 * @uses	site_url()													Returns site url
	 * @uses	self::send_slack_message()									Sends a message to our dev slack channel
	 * @return	bool		$has_parent										True if all the parent theme checks are good, false if not
	 */
	private static function theme_has_parent( $active_theme ){

		$has_parent = false;

		$parent = $active_theme->parent();

		// does this double as a check that the theme exists
		if ( ! $parent->exists() ){

			$m = 'Parent theme does not exist for ' . site_url() . '.This means you should start by rebooting the pod and then check that the site looks as expected. If that does not work a developer needs to be involved.';

			self::send_slack_message( 'no_parent_theme', $m );

		} else {
			$has_parent = true;
		}

		return (bool) $has_parent;

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
	 * @uses	get_option()												returns option from WP database
	 * @uses	get_transient()												Returns transient value
	 * @uses	set_transient()												Sets transient
	 */
	private static function send_slack_message( $message_name, $message_content ){

		$slack_key = get_option( 'proud_slack_key' );

		$notified = get_transient( $message_name );

		if ( false === $notified ){

			$curl = curl_init( $slack_key );

			$message = array( 'payload' => json_encode( array( 'text' => $message_content ) ) );

			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $message );

			$result = curl_exec( $curl );
			curl_close( $curl );

			// setting our transient for 1 hour it keeps bugging us if the plugins are off
			set_transient( $message_name, true, 3600 );

		} // notified

	} // send_slack_message

	/**
 	 * Checks to see if the active theme matches the default theme setting
	 *
	 * @since 2023.05.25
	 * @access private
	 * @author Curtis
	 *
	 * @param 	string 			$default_theme 			required 					The theme that should be active
	 * @param 	array|object 	$active_theme 			required 					All theme information
	 * @return 	bool 			$is_active 											TRUE if the default_theme matches active_theme
	 */
	private static function is_specified_theme_active( $default_theme, $active_theme ){

		$is_active = false;
		$active_theme_name = $active_theme->get_stylesheet();

		if ( esc_attr( $default_theme ) === esc_attr( $active_theme_name ) ){
			$is_active = true;
		}

		return (bool) $is_active;

	} // is_specified_theme_active

	/**
	 * Checks to see what our default theme is
	 *
	 * @since 2023.05.25
	 * @author Curtis
	 * @access private
	 *
	 * @return 	string 		$default_theme 					String of the default theme
	 */
	private static function check_default_theme(){

		$default_theme = null;

		$default_theme = getenv( 'Proud_Default_Theme' );

		if ( ! isset( $default_theme ) || empty( $default_theme ) ){

			if ( defined( 'PROUD_DEFAULT_THEME' ) ){
				$default_theme = PROUD_DEFAULT_THEME;
			} else {
				$default_theme = 'wp-proud-theme';
			}

		}

		return $default_theme;

	} // check_default_theme

}

Proud_Force_Theme::instance();
