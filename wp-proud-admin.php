<?php
/*
Plugin Name:        Proud Admin
Plugin URI:         http://getproudcity.com
Description:        ProudCity distribution
Version:            1.0.0
Author:             ProudCity
Author URI:         http://getproudcity.com

License:            MIT License
License URI:        http://opensource.org/licenses/MIT
*/

require_once get_template_directory() . '/lib/assets.php';

use Proud\Theme\Assets;

//add css
function proud_admin_theme_style() {
  // Bootstrap + proud-library styles from theme
  wp_enqueue_style('proud-vendor/css', Assets\asset_path('styles/proud-vendor.css'), false, null);

  // Local
  $path = plugins_url('dist/',__FILE__);
  wp_enqueue_style('proud-admin/css', $path . 'styles/proud-admin.css', false, null);
  wp_enqueue_script('proud-admin/js', $path . 'scripts/proud-admin.js', ['proud','jquery'], null, true);
  // // Bootstrap
  // wp_enqueue_script('proud/js', Assets\asset_path('scripts/main.js'), ['jquery'], null, true);
}
add_action('admin_enqueue_scripts', 'proud_admin_theme_style');
add_action('login_enqueue_scripts', 'proud_admin_theme_style');

//login screen
include_once('login/login.php');
include_once('login/add_menu.php');
