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

	/**
	 * Main function that checks for our various theme conditions and alerts
	 * Slack if conditions are not met
	 *
	 * @since 2023.05.29
	 * @access public
	 * @author Curtis
	 *
	 * @uses	self::check_default_theme()											Returns the theme slug for what should be the default theme
	 * @uses	wp_get_theme()														Returns object with theme information
	 * @uses	self::theme_has_parent()											Returns false if there is no parent
	 * @uses	self::is_specified_theme_active()									Confirms that the specified theme is active
	 * @uses	self::try_to_activate_theme()										Tries to activate the expected default theme
	 * @uses	self::send_slack_message()											Sends message to our dev slack channel with given information
	 */
	public static function force_theme_activation(){

		$is_active = false;
		$has_parent = false;
		$default_theme = self::check_default_theme();
		$active_theme = wp_get_theme();

		// is the theme we expect to be active actually active?
		$is_active = self::is_specified_theme_active( $default_theme, $active_theme );
		// everything is good do nothing more
		if ( true === $is_active ) return;

		// is theme present so it can be activated
		$is_present = self::is_theme_present( $default_theme );

		// @todo I really think this might be redundant because when you check the site after an issue
		//		if there is no parent theme around the site will look bad and you'll know that something is wrong
		//		so you'll talk to a developer instead of figuring everything is okay
		// checks if the currently active theme has a parent theme
		//$has_parent = self::theme_has_parent( $active_theme );

		// try to activate the theme now
		if ( false === $is_active && true === $is_present ){

			// try to activate theme
			self::try_to_activate_theme( $default_theme, $active_theme );

		} else {

			// everything is borked get a dev
			$m = 'There is an issue with the default theme on ' .site_url(). ' that appears to be unrecoverable. The theme does not appear to be present thus it cannot be activated. You need to get a dev to fix this';
			self::send_slack_message( 'proud_big_bad_theme_error', $m );
			error_log ( 'proud_big_bad_theme_error' . $m );


		} // false === $is_active

		// no further messages needed because a deve should be checking at this point


	} // force_theme_activation

	/**
	 * Checks if our default theme is even present on the site
	 *
	 * @since 2023.05.30
	 * @author Curtis
	 * @access private
	 *
	 * @param	string			$default_theme			required					The slug for the theme we expect to be set on the site
	 * @uses	wp_get_themes()														Returns an object containing all themes
	 * @uses	self::send_slack_message()											Sends an alert to dev slack
	 * @return	bool			$is_present											True if the theme is installed false if not
	 */
	private static function is_theme_present( $default_theme ){

		$themes = wp_get_themes();
		$is_present = false;

		foreach( $themes as $theme ){

			if ( $default_theme === $theme->get_stylesheet() ){
				$is_present = true;
				break;
			}

		}

		if ( false === $is_present ){

			$m = 'The default theme for ' .site_url(). ' does not appear to be present on the site. Reboot the pod and confirm that the theme is active. If that does not work get a developer.';
			self::send_slack_message( 'proud_default_theme_not_present', $m );
			error_log( 'proud_default_theme_not_present ' . $m );

		}

		return (bool) $is_present;

	} // is_theme_present

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
		// current theme doesn't have parent so exit now
		if ( false === $parent ) return false;

		// does this double as a check that the theme exists
		if ( ! $parent->exists() ){

			$m = 'Parent theme does not exist for ' . site_url() . '.This means you should start by rebooting the pod and then check that the site looks as expected. If that does not work a developer needs to be involved.';

			$has_parent = false;
			self::send_slack_message( 'no_parent_theme', $m );
			error_log( 'no_parent_theme ' . $m );

		} else {
			$has_parent = true;
		}


		return (bool) $has_parent;

	} // theme_has_parent

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
	 * Tries to activate the expected theme.
	 *
	 * @since 2023.05.29
	 * @access private
	 * @author Curtis
	 *
	 * @param	string		$theme_slug					required			Slug for the expected theme
	 * @param	object		$active_theme				required			Current active theme object
	 * @uses	switch_theme()												Switches WP theme given theme slug
	 * @uses	self::is_specified_theme_active()							True if the expected theme is active
	 * @return	bool		$is_active										True if we activated the theme as expected
	 */
	private static function try_to_activate_theme( $default_theme, $active_theme ){

		switch_theme( $default_theme );

		// did the activation work?
		$is_active = self::is_specified_theme_active( $default_theme, $active_theme );

		if ( false === $is_active ){

			$m = 'We tried to activate the default theme on ' .site_url(). ' but it did not work. Time to get a developer involved';
			self::send_slack_message( 'proud_theme_not_activate', $m );
			error_log( 'proud_theme_not_activate ' . $m );

		} else {
				$m = 'The default theme was reactivated on ' .site_url().'. You should still check the site to make sure it is working as expected.';

				// logging so we can test without sending to slack
				error_log( 'expected_theme_activated ' . $m );

				self::send_slack_message( 'expected_theme_activated', $m );
		}

		return (bool) $is_active;

	} // try_to_activate_theme

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
		} else {

			$m = 'Expected theme is not active on ' .site_url(). '. Reboot the pod and make sure the proper theme is active at '.site_url().'/wp-admin/themes.php If that does not work a developer will need to be involved.';
			self::send_slack_message( 'expected_theme_not_active', $m );

			// logging so we can test locally
			error_log( 'expected_theme_not_active '. $m );
			$is_active = false;

		}

		return (bool) $is_active;

	} // is_specified_theme_active

	/**
	 * Checks to see what our default theme is.
	 *
	 * First this checks for a kubernetes based environment variable. Then if not present it checks for
	 * a constant set it wp-config.php. If neither of these is present then it assumes that we are
	 * using the standard default theme for our sites.
	 *
	 * @since 2023.05.25
	 * @author Curtis
	 * @access private
	 *
	 * @return 	string 		$default_theme 					String of the default theme
	 */
	private static function check_default_theme(){

		$default_theme = null;

		$default_theme = getenv( 'PROUD_DEFAULT_THEME' );

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
