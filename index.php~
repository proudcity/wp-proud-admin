<?php
/**
* Plugin Name: WP Flat Admin Theme
* Plugin URI: https://github.com/nickhargreaves/WP_FlatAdmin
* Description: This plugin turns a Wordpress Admin theme into a nice looking dashboard based on the Flat UI kit by http://designmodo.com/flat
* Version: 1.0.1
* Author: Nick Hargreaves
* Author URI: http://nickhargreaves.com
* License: GPL2
*/

//add css
function flat_admin_theme_style() {
    wp_enqueue_style('flat-admin-theme', plugins_url('Flat-UI/dist/css/flat-ui.css', __FILE__));
    wp_enqueue_style('flat-admin-theme2', plugins_url('assets/css/custom.css', __FILE__));
    //wp_enqueue_style('flat-admin-theme3', plugins_url('assets/css/bootstrap.min.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'flat_admin_theme_style');
add_action('login_enqueue_scripts', 'flat_admin_theme_style');



//login screen
include_once('login/login.php');
include_once('login/add_menu.php');
