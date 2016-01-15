<?php

namespace Proud\Admin\Dashboard;

// Load Extendible
// -----------------------
if ( ! class_exists( 'ProudPlugin' ) ) {
  require_once( plugin_dir_path(__FILE__) . '../wp-proud-core/proud-plugin.class.php' );
}

class ProudAdminDashboard extends \ProudPlugin {

  /**
   * Constructor
   */
  public function __construct() {

    parent::__construct( array(
      'textdomain'     => 'wp-proud-admin-dashboard',
      'plugin_path'    => __FILE__,
    ) );

    $this->hook( 'wp_dashboard_setup', 'remove_dashboard_meta', 100 );
  }



  // Customize dashboard
  function remove_dashboard_meta() {
    remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');//since 3.8
    remove_meta_box( 'wpseo-dashboard-overview', 'dashboard', 'normal' );

    wp_add_dashboard_widget('dashboard_proud_welcome', 'Make your city proud', array($this, 'welcome') );
    wp_add_dashboard_widget('dashboard_proud_help', 'Get help', array($this, 'help') );
    wp_add_dashboard_widget('dashboard_proud_news', 'Recent news', array($this, 'news') );
  }



  function welcome() {
    require_once( plugin_dir_path(__FILE__) . 'welcome.php' );
  }

  function help() {
    include_once( plugin_dir_path(__FILE__) . 'help.php' );
  }

  function news() {
    include_once( plugin_dir_path(__FILE__) . 'news.php' );
  }


}

new ProudAdminDashboard;
