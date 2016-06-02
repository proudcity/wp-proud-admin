<?php
class ProudGeneralSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $fields;

    /**
     * Start up
     */
    public function __construct()
    {
      add_action( 'admin_menu', array($this, 'create_menu') );
      $this->key = 'proudsettings';
    }

    // create custom plugin settings menu
    

    public function create_menu() {

      add_menu_page(
        'Settings', 
        'Settings', 
        'edit_proud_options', 
        $this->key, array($this, 'settings_page'), 
        plugins_url('/images/icon.png', __FILE__) 
      );
      /*add_submenu_page( 
          'integrations',
          'General Settings',
          'General Settings',
          'manage_options',
          'alertbar',
          array($this, 'settings_page')
      );*/

      $this->options = [
        'city',
        'state',
        'lat',
        'lng',
        'proud_navbar_dropdown',
        'external_link_window',
        'agency_label',
      ];
    }


    private function build_fields( $attach = true ) {
      // Attach scripts?
      if( $attach ) {
        $path = plugins_url('assets/',__FILE__);
        wp_enqueue_script( 'google-places-api', '//maps.googleapis.com/maps/api/js?key=AIzaSyBBF8futzrzbs--ZOtqQ3qd_PFnVFQYKo4&libraries=places' );
        wp_enqueue_script( 'google-places-field', $path . 'google-places.js' );
      }
      
      $this->fields = [
        'search_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Location') . '</h3>',
        ],
        'city_input' => [
          '#type' => 'text',
          '#title' => __pcHelp('Autopopulate'),
          '#name' => 'city_input',
          '#value' => ''
        ],
        'city' => [
          '#type' => 'text',
          '#title' => __pcHelp('City'),
          '#description' => __('Example: "Oakland" or "San Francisco"'),
          '#name' => 'city',
          '#value' => get_option('city')
        ],
        'state' => [
          '#type' => 'text',
          '#title' => __pcHelp('State'),
          '#description' => __('Example: "California" or "South Carolina"'),
          '#name' => 'state',
          '#value' => get_option('state')
        ],
        'lat' => [
          '#type' => 'text',
          '#title' => __pcHelp('Latitude'),
          '#description' => __('Example: "37.8044"'),
          '#name' => 'lat',
          '#value' => get_option('lat')
        ],
        'lng' => [
          '#type' => 'text',
          '#title' => __pcHelp('Longitude'),
          '#description' => __('Example: "-122.2708"'),
          '#name' => 'lng',
          '#value' => get_option('lng')
        ],

        'advanced' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Advanced') . '</h3>',
        ],
        'external_link_window' => [
          '#type' => 'checkbox',
          '#name' => 'external_link_window',
          '#title' => __pcHelp('External links'),
          '#return_value' => '1',
          '#label_above' => false,
          '#replace_title' => __pcHelp( 'Open external links in a new tab' ),
          '#value' => get_option('external_link_window', '1'),
        ],
        'proud_navbar_dropdown' => [
          '#type' => 'checkbox',
          '#name' => 'proud_navbar_dropdown',
          '#title' => __pcHelp('Enable main navigation dropdown'),
          '#return_value' => '1',
          '#label_above' => false,
          '#replace_title' => __pcHelp( 'Enable the main navigation bar to use a dropdown' ),
          '#value' => get_option('proud_navbar_dropdown', '0'),
        ],
        'agency_label' => [
          '#type' => 'select',
          '#name' => 'agency_label',
          '#title' => __pcHelp('External links'),
          '#return_value' => '1',
          '#label_above' => true,
          '#options' => array(
            'agencies' => __pcHelp('Agency'),
            'departments' => __pcHelp('Department'),
            'branches' => __pcHelp('Branch'),
          ),
          '#value' => get_option('agency_label', 'agencies'),
        ],
      ];

    }

    public function settings_page() {
      // Do we have post?
      if(isset($_POST['_wpnonce'])) {
        if( wp_verify_nonce( $_POST['_wpnonce'], $this->key ) ) {
          $this->save($_POST);
        }
      }

      $this->build_fields();
      $form = new \Proud\Core\FormHelper( $this->key, $this->fields );
      $form->printForm ([
        'button_text' => __pcHelp('Save'),
        'method' => 'post',
        'action' => '',
        'name' => $this->key,
        'id' => $this->key,
      ]);

    }

    public function save($values) {
      $this->build_fields( false );
      foreach ($this->options as $key) {
        // If checkbox, and no value, set to 0
        if($this->fields[$key]['#type'] === 'checkbox' && !isset( $values[$key] ) ) {
          $values[$key] = 0;
        }
        if ( isset( $values[$key] ) ) {
          update_option($key, esc_attr($values[$key]) );
        }
      }
    }
}

if( is_admin() )
    $proud_general_settings_page = new ProudGeneralSettingsPage();