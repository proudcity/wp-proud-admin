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
}

new ProudAdmin;

//login screen
include_once('login/login.php');
include_once('login/add_menu.php');
