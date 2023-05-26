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
		$has_parent = self::theme_has_parent( $active_theme );

		// does active theme have parent
			// is parent theme present?

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

	private static function theme_has_parent( $active_theme ){

		$has_parent = false;

		$parent = $active_theme->parent();

		// does this double as a check that the theme exists
		if ( $parent->exists() ){

			echo '<pre> parent';
			print_r( $parent );
			echo '</pre>';

		}

		return (bool) $has_parent;

	}

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
