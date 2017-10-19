<?php
class ProudGeneralSettingsPage extends ProudSettingsPage
{
    /**
     * Start up
     */
    public function __construct()
    {
      parent::__construct(
        'proudsettings', // Key
        [ // Submenu settings
          'parent_slug' => false, // regular menu entry
          'page_title' => 'Settings',
          'menu_title' => 'Settings',
          'capability' => 'edit_proud_options',
          'icon_url' => plugins_url('/images/icon.png', __FILE__) 
        ],
        '', // Option
        [   // Options
          'city' => '',
          'state' => '',
          'lat' => '',
          'lng' => '',
		      'bounds' => '',
          'proud_navbar_dropdown' => '',
          'proud_navbar_transparent' => '',
          'external_link_window' => '',
          'proud_document_show_date' => '1',
          'agency_label' => ['agencies'],
          'payments_label' => 'Payment',
          'staff_position_label' => 'Position',
        ]
      );

      // Load admin scripts from libraries
      add_action('admin_enqueue_scripts', [ $this, 'load_scripts' ] );
    }

    /**
     * Sets fields
     */
    public function set_fields( ) {

      // Set fields
      $this->fields = [
        'search_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Location') . '</h3>',
        ],
        'city_input' => [
          '#type' => 'text',
          '#title' => __pcHelp('Autopopulate'),
          '#value' => ''
        ],
        'city' => [
          '#type' => 'text',
          '#title' => __pcHelp('City'),
          '#description' => __('Example: "Oakland" or "San Francisco"'),
        ],
        'state' => [
          '#type' => 'text',
          '#title' => __pcHelp('State'),
          '#description' => __('Example: "California" or "South Carolina"'),
        ],
        'lat' => [
          '#type' => 'text',
          '#title' => __pcHelp('Latitude'),
          '#description' => __('Example: "37.8044"'),
        ],
        'lng' => [
          '#type' => 'text',
          '#title' => __pcHelp('Longitude'),
          '#description' => __('Example: "-122.2708"'),
        ],

        'advanced' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Advanced') . '</h3>',
        ],
        'external_link_window' => [
          '#type' => 'checkbox',
          '#title' => __pcHelp('External links'),
          '#return_value' => '1',
          '#label_above' => false,
          '#replace_title' => __pcHelp( 'Open external links in a new tab' ),
          '#default_value' => '1',
        ],
        'proud_navbar_dropdown' => [
          '#type' => 'checkbox',
          '#title' => __pcHelp('Enable main navigation dropdown'),
          '#return_value' => '1',
          '#label_above' => false,
          '#replace_title' => __pcHelp( 'Enable the main navigation bar to use a dropdown' ),
        ],
        'proud_navbar_transparent' => [
          '#type' => 'checkbox',
          '#title' => __pcHelp('Make the main navigation bar transparent'),
          '#return_value' => '1',
          '#label_above' => false,
          '#replace_title' => __pcHelp( 'Make the main navigation bar transparent' ),
        ],
        'proud_document_show_date' => [
          '#type' => 'checkbox',
          //'#title' => __pcHelp('Display published date on documents'),
          '#return_value' => '1',
          '#label_above' => false,
          '#replace_title' => __pcHelp( 'Display published date on documents' ),
          '#default_value' => '1',
        ],
        'agency_label' => [
          '#type' => 'select',
          '#title' => __pcHelp('Agency Label'),
          '#return_value' => '1',
          '#label_above' => true,
          '#options' => array(
            'agencies' => __pcHelp('Agency'),
            'departments' => __pcHelp('Department'),
            'branches' => __pcHelp('Branch'),
          ),
        ],
        'payments_label' => [
          '#type' => 'select',
          '#title' => __pcHelp('Payments Label'),
          '#return_value' => '1',
          '#label_above' => true,
          '#options' => array(
            'Payment' => __pcHelp('Payment'),
            'Donation' => __pcHelp('Donation'),
          ),
        ],
        'staff_position_label' => [
          '#type' => 'text',
          '#title' => __pcHelp('Staff/Contact "Position" label'),
          '#value' => ''
        ],
        'bounds' => [
          '#type' => 'textarea',
          '#title' => __pcHelp('Bounds'),
          '#description' => __('Used to improve location autocomplete widget. JSON format.'),
          '#name' => 'bounds',
          '#value' => get_option('bounds')
        ],
 
      ];
    }

    /**
     * Calls form actions and builds fields
     */
    public function load_scripts( $hook ) {
      if( $hook === $this->hook ) {
        // Attach scripts
        $path = plugins_url('assets/',__FILE__);
        wp_enqueue_script( 'google-places-api', '//maps.googleapis.com/maps/api/js?key='. get_option( 'google_api_key', '' ) .'&libraries=places' );
        wp_register_script( 'google-places-field', $path . 'google-places.js' );
        // Grab field IDs
        $options = $this->get_field_ids( [
          'city_input',
          'city',
          'state',
          'lat',
          'lng',
        ] );
        wp_localize_script( 'google-places-field', 'places_fields', $options );
        wp_enqueue_script( 'google-places-field' ); 
      }
    }      

    /**
     * Print page content
     */
    public function settings_content() {
      $this->print_form( );
    }
}

if( is_admin() )
    $proud_general_settings_page = new ProudGeneralSettingsPage();