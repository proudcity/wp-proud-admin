<?php
/**
 * Created by PhpStorm.
 * User: nick
 * Date: 28/09/15
 * Time: 10:00
 */
//add menu screen to customize logo

add_action( 'admin_menu', 'wp_flat_admin_config_menu' );

function wp_flat_admin_config_menu() {
    add_options_page( 'WP Flat Admin Options', 'WP Flat Admin', 'manage_options', 'wpflatadmin', 'wp_flat_admin_config_options' );
}

/** Step 3. */
function wp_flat_admin_config_options() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    include_once("config_options.php");

}