<?php
/*
Plugin Name:        Proud Admin
Plugin URI:         http://getproudcity.com
Description:        ProudCity WordPress Admin theme and customizations
Version:           	2217.2023.07.27.1043
Author:             ProudCity
Author URI:         http://getproudcity.com

License:            Affero GPL v3
*/

namespace Proud\Admin;

require_once file_exists ( get_template_directory() . '/lib/assets.php' ) ? get_template_directory() . '/lib/assets.php' : plugin_dir_path(__FILE__) . '../../themes/wp-proud-theme/lib/assets.php';

use Proud\Theme\Assets;

// Load Extendible
// -----------------------
if ( ! class_exists( 'ProudPlugin' ) ) {
  require_once( plugin_dir_path(__FILE__) . '../wp-proud-core/proud-plugin.class.php' );
}
// Abstract settings class
require_once( plugin_dir_path(__FILE__) . 'lib/settings-page.class.php' );
// Meta box class
require_once( plugin_dir_path(__FILE__) . 'lib/meta-box.class.php' );
// Helper classes
require_once( plugin_dir_path(__FILE__) . 'wp-proud-admin-helpers.php' );
// Dashboard
require_once( plugin_dir_path(__FILE__) . 'dashboard/wp-proud-admin-dashboard.php' );
// FontAwesome Builder
require_once( plugin_dir_path(__FILE__) . 'lib/proud-fa-build.php' );
// User Profile Customizations
require_once( plugin_dir_path(__FILE__) . 'lib/proud-customize-wp-profile.php' );
// Post Type Customizations
require_once( plugin_dir_path(__FILE__) . 'lib/proud-cpt-customizations.php' );
// Forcing Activation of Default Theme
require_once( plugin_dir_path(__FILE__) . 'lib/proud-force-theme.php' );

class ProudAdmin extends \ProudPlugin {

  /**
   * Constructor
   */
  public function __construct() {

    parent::__construct( array(
      'textdomain'     => 'wp-proud-admin',
      'plugin_path'    => __FILE__,
    ) );

    $this->hook( 'init', 'register_setting_pages' );
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
    add_filter( 'wp_head', array($this, 'add_metatag_code') );
    add_filter( 'srm_restrict_to_capability', array($this, 'redirect_capability') );

    // -- Hacks
    // Hide admin fields
    $this->hook('init', 'remove_post_admin_fields');
    // Hide theme selection options
    add_filter( 'wp_prepare_themes_for_js', array($this, 'filter_theme_options') );

    $this->hook( 'tiny_mce_before_init', 'tiny_mce_alter' );
    $this->hook( 'admin_print_footer_scripts', 'wp_561_window_unload_error_final_fix' );

    //$this->hook( 'postbox_classes_post_wpseo_meta', 'minify_metabox' );  // This is done in js
  }

  /**
   * Register admin settings pages
   */
  public function register_setting_pages() {
    if( is_admin() ) {
      require_once( plugin_dir_path(__FILE__) . 'settings/settings.php' );
      require_once( plugin_dir_path(__FILE__) . 'settings/integrations.php' );
      require_once( plugin_dir_path(__FILE__) . 'settings/social.php' );
      require_once( plugin_dir_path(__FILE__) . 'settings/alert.php' );
      require_once( plugin_dir_path(__FILE__) . 'settings/caching.php' );
    }
  }

  /**
   *  Alters tinymce instances for backend
   */
  public function tiny_mce_alter( $settings ) {
    // Make sure tables get a class
    $settings['table_default_attributes'] = json_encode( array(
      'class' => 'table'
    ) );
    // Setup event on editor loads
    if( empty( $settings['setup'] ) ) {
      $settings['setup'] = 'function (ed) { ed.on("init", function(args) { jQuery("body").trigger({ type: "tinyEditorInit", ed: ed, edArgs: args }); }); }';
    }
    return $settings;
  }


  /*public function minify_metabox( $classes ) {
    array_push( $classes, 'closed' );
    return $classes;
  }*/

  // Add permissions to Editor role
  function add_caps( $allcaps, $cap = null, $args = [] ) {
    $editor_caps = array(
      'activate_plugins',  //@todo: need to look at ramfications (left sidebar) before enabling
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
      'manage_job_listings',
      'gravityforms_create_form',
      'gravityforms_delete_entries',
      'gravityforms_delete_forms',
      'gravityforms_edit_entries',
      'gravityforms_edit_entry_notes',
      'gravityforms_edit_forms',
      'gravityforms_export_entries',
      'gravityforms_preview_forms',
      'gravityforms_view_entries',
      'gravityforms_view_entry_notes',
      'gravityforms_mailchimp',
      'gravityforms_stripe',
      'gravityforms_zapier',
      'gravityforms_constantcontact',
      'gravityflow_activity',
      'gravityflow_create_steps',
      'gravityflow_inbox',
      'gravityflow_reports',
      'gravityflow_settings',
      'gravityflow_status',
      'gravityflow_statis_view_all',
      'gravityflow_submit',
      'gravityflow_workflow_detail_admin_actions',
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

	$plugin_data = get_plugin_data( __FILE__ );
	$version = $plugin_data['Version'];
	$screen = get_current_screen();

/*
	echo '<pre>';
	print_r( $screen );
	echo '</pre>';
*/
		/**
		 * Excluding gravity forms pages here because our vendor CSS loads
		 * and messes up the styling of the page
		 */
	if (    isset( $screen->base ) && 'toplevel_page_gf_edit_forms' != $screen->base
			&& 'forms_page_gf_entries' != $screen->base
			&& 'forms_page_gf_settings' != $screen->base
			&& 'forms_page_gf_export' != $screen->base
	){
		// Bootstrap + proud-library styles from theme
		wp_enqueue_style('proud-vendor/css', Assets\asset_path('styles/proud-vendor.css'), false, esc_attr( $version ) );
	}

    // Local
    $path = plugins_url('dist/',__FILE__);
    wp_enqueue_style('proud-admin/css', $path . 'styles/proud-admin.css', false, esc_attr( $version ) );
    wp_enqueue_script('proud-admin/js', $path . 'scripts/proud-admin.js', ['proud','jquery'], esc_attr( $version ), true );
    // // Bootstrap
    // wp_enqueue_script('proud/js', Assets\asset_path('scripts/main.js'), ['jquery'], null, true);

    // temp CSS file until we rebuild the overall admin theme build process
    wp_enqueue_style('proud-admin/css-temp', $path . 'styles/proud-admin-temp.css', false, esc_attr( $version ) );

    if ( function_exists( 'get_current_screen' ) ){
      $screen = get_current_screen();
      if ( is_object( $screen ) && 'settings_page_integrations' == $screen->id ){
        wp_enqueue_script( 'proud-admin/fa',  plugins_url( '/wp-proud-admin/assets/scripts/proud-fa.js'), ['proud', 'jquery'], esc_attr( $version ), true );
        wp_localize_script( 'proud-admin/fa', 'ProudFaBuild', array(
          'ajaxurl'           => admin_url( 'admin-ajax.php' ),
          'proud_fabuild_ajax_nonce' => wp_create_nonce( 'proud_fabuild_ajax_nonce' ),
        ) );
      }

      if ( is_object( $screen ) && 'settings_page_caching' == $screen->id ){
        wp_enqueue_script( 'proud-admin/cache',  plugins_url( '/wp-proud-admin/assets/scripts/proud-caching.js'), ['proud', 'jquery'], esc_attr( $version ), true );
        wp_localize_script( 'proud-admin/cache', 'ProudCaching', array(
          'ajaxurl'           => admin_url( 'admin-ajax.php' ),
          'proud_caching_ajax_nonce' => wp_create_nonce( 'proud_caching_ajax_nonce' ),
        ) );
      }

    } // function_exists

    // Fonts
    wp_enqueue_style('external-fonts', '//fonts.googleapis.com/css?family=Lato:400,700,300');
  }

  // Remove extra fields on the admin pages
  public function remove_post_admin_fields() {
    remove_post_type_support( 'question', 'author' );
    remove_post_type_support( 'question', 'comments' );
    remove_post_type_support( 'question', 'custom-fields' );
  }

  // Filtering theme options
  public function filter_theme_options($prepared_themes) {
    foreach ( $prepared_themes as $name => $theme ) {
      if( false === strpos($name, 'wp-proud') ) {
        unset($prepared_themes[$name]);
      }
    }
    return $prepared_themes;
  }


  // Change the cap for redirect post type creation from manage_options
  function redirect_capability($cap) {
    return 'edit_proud_options';
  }


  // Add Dashboard link
  function wp_admin_bar_dashboard($admin_bar) {
  //echo "<pre>";
  //print_r($admin_bar);
  //echo "<pre>";
   $admin_bar->add_menu( array(
        'id'    => 'pc-dashboard',
        'parent' => 'root-default',
        'title' => 'Dashboard',
        'href'  => get_site_url() . '/wp-admin/admin.php?page=proud_dashboard',
        'weight' => 10,
        'meta'  => array(
            'title' => __('Visit your Dashboard'),
        ),
    ) );

    $admin_bar->add_menu( array(
        'id'    => 'pc-help',
        'parent' => 'root-default',
        'title' => 'Help',
        'href'  => 'https://help.proudcity.com/',
        'weight' => 10000,
        'meta'  => array(
            'title' => __('Get Help'),
        ),
    ) );
    $admin_bar->add_menu( array(
        'id'    => 'pc-help-search',
        'parent' => 'pc-help',
        'title' => 'Search Help',
        'href'  => 'https://help.proudcity.com/',
    ) );
    $admin_bar->add_menu( array(
        'id'    => 'pc-help-ticket',
        'parent' => 'pc-help',
        'title' => 'Create a ticket',
        'href'  => 'https://help.proudcity.com/submit',
    ) );
    $admin_bar->add_menu( array(
        'id'    => 'pc-help-request-feature',
        'parent' => 'pc-help',
        'title' => 'Request a feature',
        'href'  => 'https://help.proudcity.com/submit?type=Feature+request',
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
    echo "<a href='https://proudcity.com' target='_blank'>ProudCity</a> is proudly powered by <a href='http://wordpress.com' target='_blank'>WordPress</a> and Open Source software. <a href='$url/wp-admin/credits.php'>Credits</a> &middot; <a href='$url/wp-admin/freedoms.php'>Freedoms</a>.";
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

  // Add tracking code (footer)
  function add_tracking_code () {
    print get_option('embed_code', true);
  }

  // Add metatags (head)
  function add_metatag_code () {
    $ga = get_option('google_analytics_key', true);
    $metatags = get_option('validation_metatags', true);
    require_once( plugin_dir_path(__FILE__) . 'inc/tracking-code.php' );
  }

  /**
   * Fix for Platform: Upgrading WordPress core and "Are you sure you want to leave" alert #1822
   * https://github.com/proudcity/wp-proudcity/issues/1822
   *
   * From: https://core.trac.wordpress.org/ticket/52440
   *
   * @return void
   */
  function wp_561_window_unload_error_final_fix(){
    ?>
    <script>
        jQuery(document).ready(function($){

            // Check screen
            if(typeof window.wp.autosave === 'undefined')
                return;

            // Data Hack
            var initialCompareData = {
                post_title: $( '#title' ).val() || '',
                content: $( '#content' ).val() || '',
                excerpt: $( '#excerpt' ).val() || ''
            };

            var initialCompareString = window.wp.autosave.getCompareString(initialCompareData);

            // Fixed postChanged()
            window.wp.autosave.server.postChanged = function(){

                var changed = false;

                // If there are TinyMCE instances, loop through them.
                if ( window.tinymce ) {
                    window.tinymce.each( [ 'content', 'excerpt' ], function( field ) {
                        var editor = window.tinymce.get( field );

                        if ( ( editor && editor.isDirty() ) || ( $( '#' + field ).val() || '' ) !== initialCompareData[ field ] ) {
                            changed = true;
                            return false;
                        }

                    } );

                    if ( ( $( '#title' ).val() || '' ) !== initialCompareData.post_title ) {
                        changed = true;
                    }

                    return changed;
                }

                return window.wp.autosave.getCompareString() !== initialCompareString;

            }
        });
    </script>
    <?php
}

}

new ProudAdmin;

//login screen
include_once('login/login.php');
include_once('login/add_menu.php');
