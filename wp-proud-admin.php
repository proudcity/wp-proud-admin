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

require_once( plugin_dir_path(__FILE__) . 'wp-proud-admin-helpers.php' );


require_once( plugin_dir_path(__FILE__) . 'dashboard/wp-proud-admin-dashboard.php' );

require_once( plugin_dir_path(__FILE__) . 'settings/settings.php' );
require_once( plugin_dir_path(__FILE__) . 'settings/integrations.php' );
require_once( plugin_dir_path(__FILE__) . 'settings/social.php' );
require_once( plugin_dir_path(__FILE__) . 'settings/alert.php' );


class ProudAdmin extends \ProudPlugin {

  /**
   * Constructor
   */
  public function __construct() {

    parent::__construct( array(
      'textdomain'     => 'wp-proud-admin',
      'plugin_path'    => __FILE__,
    ) );

    // @todo: add this in register_activation_hook, implement register_deactivation_hook
    // http://wordpress.stackexchange.com/questions/35165/how-do-i-create-a-custom-role-capability
    $this->hook( 'admin_init', 'add_caps' ); 
    $this->hook( 'admin_enqueue_scripts', 'proud_admin_theme_style' );
    $this->hook( 'login_enqueue_scripts', 'proud_admin_theme_style' );
    $this->hook( 'admin_bar_menu', 'wp_admin_bar_dashboard', 20 );
    $this->hook( 'admin_bar_menu', 'wp_admin_bar_account', 11 );
    $this->hook( 'admin_footer_text', 'custom_footer' );
    $this->hook( 'admin_body_class', 'add_admin_body_classes' );

    // Add Google Analytics/other embed code
    add_filter( 'wp_footer', array($this, 'add_tracking_code') );

    // -- Hacks
    // Hide admin fields
    $this->hook('init', 'remove_post_admin_fields');

    //$this->hook( 'postbox_classes_post_wpseo_meta', 'minify_metabox' );  // This is done in js
  }


  /*public function minify_metabox( $classes ) {
    array_push( $classes, 'closed' );
    return $classes;
  }*/

  // Add permissions to Editor role
  function add_caps( $allcaps, $cap = null, $args = [] ) {
    $editor_caps = array(                  
      'switch_themes',
      'edit_files',
      'edit_theme_options',
      'edit_job_listing',
      'read_job_listing',
      'delete_job_listing',
      'edit_job_listings',
      'edit_others_job_listings',
      'publish_job_listings',
      'read_private_job_listings',
      'delete_job_listings',
      'delete_private_job_listings',
      'delete_published_job_listings',
      'delete_others_job_listings',
      'edit_private_job_listings',
      'edit_published_job_listings',
      'manage_job_listing_terms',
      'edit_job_listing_terms',
      'delete_job_listing_terms',
      'assign_job_listing_terms',
      'edit_proud_options',
    );
    $role = get_role( 'editor' );
    foreach ($editor_caps as $item) {
      $role->add_cap( $item ); 
    }

    $administrator_caps = array(                  
      'edit_proud_options',
    );
    $role = get_role( 'administrator' );
    foreach ($administrator_caps as $item) {
      $role->add_cap( $item ); 
    }
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

    // Fonts
    wp_enqueue_style('external-fonts', '//fonts.googleapis.com/css?family=Lato:400,700,300');
  }

  // Remove extra fields on the admin pages
  public function remove_post_admin_fields() {
    remove_post_type_support( 'question', 'author' );
    remove_post_type_support( 'question', 'comments' );
    remove_post_type_support( 'question', 'custom-fields' );
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

  // Customize footer message
  function custom_footer () {
    $url = get_site_url();
    echo "<a href='http://proudcity.com' target='_blank'>ProudCity</a> is proudly powered by <a href='http://wordpress.com' target='_blank'>WordPress</a> and Open Source software. <a href='$url/wp-admin/credits.php'>Credits</a> &middot; <a href='$url/wp-admin/freedoms.php'>Freedoms</a>.";
    require_once( plugin_dir_path(__FILE__) . 'inc/chat.php' );
  }

  // Add classes to distinguish between admin, normal users.
  public function add_admin_body_classes($classes) {
    if( current_user_can( 'manage_options' ) ) {
      return "$classes role-admin";
    }
    else {
      return "$classes role-non-admin";
    }
  }

  // Add tracking code
  function add_tracking_code () {
    $ga = get_option('google_analytics_key', true);
    $embed = get_option('embed_code', true);
    require_once( plugin_dir_path(__FILE__) . 'inc/tracking-code.php' );
  }

}

new ProudAdmin;

//login screen
include_once('login/login.php');
include_once('login/add_menu.php');
