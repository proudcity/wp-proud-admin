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

use Proud\Theme\Assets;

//add css
function flat_admin_theme_style() {
  // Bootstrap + proud-library styles from theme
  wp_enqueue_style('proud-vendor/css', Assets\asset_path('styles/proud-vendor.css'), false, null);

  // Local
  $path = plugins_url('dist/',__FILE__);
  wp_enqueue_style('proud-admin/css', $path . 'styles/proud-admin.css', false, null);
  wp_enqueue_script('proud-admin/js', $path . 'scripts/proud-admin.js', ['proud','jquery'], null, true);
  // // Bootstrap
  // wp_enqueue_script('proud/js', Assets\asset_path('scripts/main.js'), ['jquery'], null, true);
}
add_action('admin_enqueue_scripts', 'flat_admin_theme_style');
add_action('login_enqueue_scripts', 'flat_admin_theme_style');

//login screen
include_once('login/login.php');
include_once('login/add_menu.php');
