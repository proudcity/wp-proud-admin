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

namespace Proud\Admin;

require_once get_template_directory() . '/lib/assets.php';

use Proud\Theme\Assets;

// Load Extendible
// -----------------------
if ( ! class_exists( 'ProudPlugin' ) ) {
  require_once( plugin_dir_path(__FILE__) . '../wp-proud-core/proud-plugin.class.php' );
}

class ProudAdmin extends \ProudPlugin {

  /**
   * Constructor
   */
  public function __construct() {

    parent::__construct( array(
      'textdomain'     => 'wp-proud-admin',
      'plugin_path'    => __FILE__,
    ) );

    $this->hook( 'admin_enqueue_scripts', 'proud_admin_theme_style' );
    $this->hook( 'login_enqueue_scripts', 'proud_admin_theme_style' );
    $this->hook( 'admin_bar_menu', 'wp_admin_bar_dashboard', 20 );
    $this->hook( 'admin_bar_menu', 'wp_admin_bar_account', 11 );
    $this->hook( 'wp_dashboard_setup', 'remove_dashboard_meta' );
    $this->hook( 'admin_footer_text', 'dashboard_footer' );

  }

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

  // Add Dashboard link
  function wp_admin_bar_dashboard($admin_bar) {
  //echo "<pre>";
  //print_r($admin_bar);
  //echo "<pre>";
   $admin_bar->add_menu( array(
        'id'    => 'my-item',
        'parent' => 'root-default',
        'title' => 'Dashboard',
        'href'  => get_site_url() . '/wp-admin',
        'weight' => 10,
        'meta'  => array(
            'title' => __('My Item'),
        ),
    ) );
  }

  // Change the "Howdy" text
  function wp_admin_bar_account( $wp_admin_bar ) {
    $user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    $profile_url = get_edit_profile_url( $user_id );

    if ( 0 != $user_id ) {
      /* Add the "My Account" menu */
      $avatar = get_avatar( $user_id, 28 );
      $howdy = $current_user->user_email;//sprintf( __('Welcome, %1$s'), $current_user->display_name );
      $class = empty( $avatar ) ? '' : 'with-avatar';

      $wp_admin_bar->add_menu( array(
        'id' => 'my-account',
        'parent' => 'top-secondary',
        'title' => $howdy . $avatar,
        'href' => $profile_url,
        'meta' => array(
        'class' => $class,
        ),
      ) );

    }
  }

  function remove_dashboard_meta() {
    //$user = wp_get_current_user();
    //if ( ! $user->has_cap( 'manage_options' ) ) {
        remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');//since 3.8
    //}
  }

  function dashboard_footer () {
    $url = get_site_url();
    echo "<a href='http://proudcity.com' target='_blank'>ProudCity</a> is proudly powered by <a href='http://wordpress.com' target='_blank'>WordPress</a> and Open Source software. <a href='$url/wp-admin/credits.php'>Credits</a> &middot; <a href='$url/wp-admin/freedoms.php'>Freedoms</a>.";
  }

}

new ProudAdmin;

//login screen
include_once('login/login.php');
include_once('login/add_menu.php');



// @todo: move
function add_theme_caps() {
    // gets the author role
    $role = get_role( 'author' );

    // This only works, because it accesses the class instance.
    // would allow the author to edit others' posts for current theme only
    $role->add_cap( 'edit_others_posts' ); 
}
add_action( 'admin_init', 'add_theme_caps');