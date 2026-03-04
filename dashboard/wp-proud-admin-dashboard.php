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
      'textdomain'     => 'wp-proud-dashboard',
      'plugin_path'    => __FILE__,
    ) );

    $this->hook( 'wp_dashboard_setup', 'remove_dashboard_meta', 100 );

    // wp-admin.php callback to save proud_
    $this->hook( 'wp_ajax_wp-proud-checklist', 'checklist_save' );
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
    remove_meta_box( 'rg_forms_dashboard', 'dashboard', 'normal' ); //gravityforms
    remove_meta_box( 'auth0_dashboard_widget_age', 'dashboard', 'normal' );
    remove_meta_box( 'auth0_dashboard_widget_gender', 'dashboard', 'normal' );
    remove_meta_box( 'auth0_dashboard_widget_income', 'dashboard', 'normal' );
    remove_meta_box( 'auth0_dashboard_widget_signups', 'dashboard', 'normal' );
    remove_meta_box( 'auth0_dashboard_widget_Location', 'dashboard', 'normal' );
    remove_meta_box( 'auth0_dashboard_widget_idp', 'dashboard', 'normal' );

    // We're using add_meta_box() instead of wp_add_dashboard_widget() so we can set positioning
    // http://wordpress.stackexchange.com/questions/69729/dashboard-widget-custom-positioning
    if (is_plugin_active('wp-proud-dashboard/wp-proud-dashboard.php')) {
        add_meta_box( 'dashboard_proud_welcome', 'Loading...', array($this, 'checklist'), 'dashboard', 'normal', 'high' );
    }
    else {
        add_meta_box( 'dashboard_proud_video', 'Player', array($this, 'video'), 'dashboard', 'side', 'high' );
        add_meta_box( 'dashboard_proud_help', 'Get help', array($this, 'help'), 'dashboard', 'side', 'high' );
        add_meta_box( 'dashboard_proud_news', 'Recent news', array($this, 'news'), 'dashboard', 'side', 'high' );
    }

  }



  function checklist() {
    // Add proud search settings
    // @todo: make this work
    /*global $proudcore;
    $proudcore->addJsSettings([
      'proud_checklist' => [
        'global' => [
          'url'     => admin_url( 'admin-ajax.php' ),
          'params' => array(
            'action'   => 'wp-proud-checklist',
            '_wpnonce' => wp_create_nonce( 'wp-proud-checklist' ),
          ),
        ]
      ]
    ]);*/
    //$path = plugins_url('js/',__FILE__);
    //wp_enqueue_script( 'wp-dashboard-checklist', $path . 'checklist.js', [ 'typewatch' ], false, true );

    $steps = $this->checklist_steps();
    $completed = get_option('proud_checklist', array());
    $count_completed = count($completed);
    $count = count($steps);
    include ( plugin_dir_path(__FILE__) . 'checklist.php' );
  }



  function help() {
    include_once( plugin_dir_path(__FILE__) . 'help.php' );
  }

  function news() {
    include_once( plugin_dir_path(__FILE__) . 'news.php' );
  }

  function video() {
    include_once( plugin_dir_path(__FILE__) . 'video.php' );
  }

  function checklist_steps() {
    return array(
      'editor' => array(
        'title' => 'Learn about The Editor',
        'icon' => 'fa-list-alt',
        'link' => null,
        'video' => 'v2Hm-XkZ0WY',
      ),
      'media' => array(
        'title' => 'Add imagery',
        'icon' => 'fa-picture-o',
        'link' => '/wp-admin/upload.php',
        'video' => null,
      ),
      'appearance' => array(
        'title' => 'Configure appearance',
        'icon' => 'fa-paint-brush',
        'link' => 'wp-admin/customize.php?return=/wp-admin',
        'video' => null,
      ),
      'integrations' => array(
        'title' => 'Select integrations',
        'icon' => 'fa-share-square',
        'link' => '/wp-admin/admin.php?page=integrations',
        'video' => null,
      ),
      'social' => array(
        'title' => 'Set up social feed',
        'icon' => 'fa-comments',
        'link' => '/wp-admin/admin.php?page=social',
        'video' => null,
      ),
      'home' => array(
        'title' => 'Edit homepage',
        'icon' => 'fa-th-large',
        'link' => '/wp-admin/post.php?post=139&action=edit', // @todo: use get_option('page_on_front')?
        'video' => null,
      ),
      'answers' => array(
        'title' => 'Add answers',
        'icon' => 'fa-list-alt',
        'link' => '/wp-admin/edit.php?post_type=question', // @todo: use get_option('page_on_front')?
        'video' => 'rGPs8nPEA4E',
      ),
      'payments' => array(
        'title' => 'Set up payments',
        'icon' => 'fa-credit-card',
        'link' => '/wp-admin/edit.php?post_type=payment', // @todo: use get_option('page_on_front')?
        'video' => 'GfggmaEypdg',
      ),
      'agencies' => array(
        'title' => 'Create agencies',
        'icon' => 'fa-university',
        'link' => '/wp-admin/edit.php?post_type=agency', // @todo: use get_option('page_on_front')?
        'video' => 'gWDzE7O5uro',
      ),
      'forms' => array(
        'title' => 'Add forms',
        'icon' => 'fa-check-square-o',
        'link' => '/wp-admin/admin.php?page=wpcf7', // @todo: use get_option('page_on_front')?
        //'video' => '_sCEkbaX6eE',
      ),
    );
  }

  public function checklist_save() {
    if(!empty($_GET['completed']) && wp_verify_nonce($_GET['_wpnonce'], $this->textdomain)) {
      update_option('proud_checklist', $_GET['completed']);
      wp_send_json($_GET['completed']);
    }
    wp_die();
  }



}

new ProudAdminDashboard;
