<?php
/**
 * Makes customizations to various post types
 */
class Proud_CPT_Customizations{

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
			self::$instance = new Proud_CPT_Customizations();
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
		add_filter('hidden_meta_boxes', array( $this 'hide_meta_box_attributes' ), 10, 2);
	} // init

	/**
	 * Hides the page attributes box for new users by default
	 *
	 * @since 2023.05.04
	 * @author Curtis
	 *
	 * @return 	$hidden 		array 				Array of metaboxes that are hidden by default
	 */
	public static function hide_meta_box_attributes( $hidden, $screen) {

		$hidden[] = 'pageparentdiv';
		return $hidden;

	}


}

Proud_CPT_Customizations::instance();
