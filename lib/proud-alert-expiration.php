<?php

/**
 * Alert bar expiration via wp-cron.
 *
 * Schedules an hourly cron event that checks whether the stored
 * alert_expiration date has passed and deactivates the alert bar
 * automatically when it has.
 *
 * Expiry semantics: the alert bar stays visible through the entire chosen
 * day (in the site timezone) and turns off after midnight. The stored value
 * must be a date string in Y-m-d format. Datetime strings are not accepted.
 *
 * This file is required unconditionally in wp-proud-admin.php so the cron
 * event is registered and fires on every request, not only admin requests.
 */

class Proud_Alert_Expiration {

	private static $instance;

	/**
	 * Spins up the single instance and registers hooks.
	 */
	public static function instance() {

		if ( ! self::$instance ) {
			self::$instance = new Proud_Alert_Expiration();
			self::$instance->init();
		}

	} // instance

	/**
	 * Registers the cron scheduling on init and hooks the callback.
	 */
	public function init() {

		add_action( 'init', array( $this, 'schedule' ) );
		add_action( 'proud_alert_expiration_check', array( 'Proud_Alert_Expiration', 'check' ) );
		// Priority 20: this file loads (and registers hooks) before ProudAdmin
		// enqueues proud-admin/js at priority 10, and wp_add_inline_script()
		// silently fails if the handle is not yet registered.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_date_picker' ), 20 );

	} // init

	/**
	 * Schedules the hourly cron event if it is not already scheduled.
	 */
	public function schedule() {

		if ( ! wp_next_scheduled( 'proud_alert_expiration_check' ) ) {
			wp_schedule_event( time(), 'hourly', 'proud_alert_expiration_check' );
		}

	} // schedule

	/**
	 * On the alert bar settings page, converts the alert_expiration text input
	 * to a native HTML5 date picker via inline JavaScript.
	 *
	 * The proud-form library hardcodes type="text" and cannot be modified, so
	 * this script flips the type attribute after the page renders. The input
	 * name rendered by FormHelper for key 'alertbar', field 'alert_expiration',
	 * number 1, base 'form' is: form-alertbar[1][alert_expiration].
	 *
	 * Checks $_GET['page'] rather than the screen id because the screen id is
	 * derived from the top-level menu *title* ("Settings" -> settings_page_alertbar)
	 * and would break if that title ever changes.
	 *
	 * Hook: admin_enqueue_scripts
	 */
	public function enqueue_date_picker() {

		if ( ! isset( $_GET['page'] ) || 'alertbar' !== $_GET['page'] ) {
			return;
		}

		wp_add_inline_script(
			'proud-admin/js',
			'document.addEventListener("DOMContentLoaded",function(){' .
				'document.querySelectorAll(\'[name="form-alertbar[1][alert_expiration]"]\').forEach(function(el){' .
					'el.type="date";' .
				'});' .
			'});'
		);

	} // enqueue_date_picker

	/**
	 * Cron callback: checks whether the stored expiration date has passed and
	 * deactivates the alert bar if so.
	 *
	 * Accepts only the Y-m-d date format. The comparison uses end-of-day
	 * semantics (23:59:59 in the site timezone) so the bar stays visible
	 * through the chosen day and turns off after midnight. Unparseable strings
	 * are treated as "no expiration" (no-op).
	 *
	 * @uses get_option()
	 * @uses update_option()
	 * @uses wp_timezone()
	 * @uses ProudSettingsPage::clear_cache()
	 */
	public static function check() {

		$raw = get_option( 'alert_expiration' );

		if ( empty( $raw ) || ! get_option( 'alert_active' ) ) {
			return;
		}

		$tz  = wp_timezone();
		$raw = trim( (string) $raw );

		// Accept only the date-only format. Datetime strings are unparseable.
		$dt = date_create_from_format( 'Y-m-d', $raw, $tz );

		// Unparseable — treat as no expiration.
		if ( ! $dt ) {
			return;
		}

		// End-of-day semantics: the bar stays visible through the entire chosen
		// day and turns off after midnight in the site timezone.
		$dt->setTime( 23, 59, 59 );

		if ( $dt->getTimestamp() < time() ) {
			update_option( 'alert_active', 0 );
			update_option( 'alert_expiration', '' );
			ProudSettingsPage::clear_cache();
		}

	} // check

} // Proud_Alert_Expiration

Proud_Alert_Expiration::instance();
