<?php
class ProudGeneralSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
      add_action( 'admin_menu', array($this, 'create_menu') );
      $this->key = 'proudsettings';
      $this->fields = null;
    }

    // create custom plugin settings menu
    

    public function create_menu() {

      add_menu_page('Settings', 'Settings', 'manage_options', $this->key, array($this, 'settings_page') , plugins_url('/images/icon.png', __FILE__) );
      /*add_submenu_page( 
          'integrations',
          'General Settings',
          'General Settings',
          'manage_options',
          'alertbar',
          array($this, 'settings_page')
      );*/

      //call register settings function
      add_action( 'admin_init', array($this, 'register_settings') );
    }


    public function register_settings() {
      register_setting( $this->key, 'city' );
      register_setting( $this->key, 'state' );
      register_setting( $this->key, 'lat' );
      register_setting( $this->key, 'lng' );

      register_setting( $this->key, 'external_link_window' );
      register_setting( $this->key, 'agency_label' );
    }

    private function build_fields(  ) {
      $this->fields = [
        
        'search_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Location') . '</h3>',
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
          '#title' => __pcHelp('City'),
          '#description' => __('Example: "California" or "South Carolina"'),
          '#name' => 'city',
          '#value' => get_option('city')
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
      $this->build_fields();
      $form = new \Proud\Core\FormHelper( $this->key, $this->fields );
      ?>
      <div class="wrap">
        <h2>Integrations</h2>   
        <form method="post" action="options.php">
          <?php settings_fields( $this->key ); ?>
          <?php do_settings_sections( $this->key ); ?>
            <?php $form->printFields(  ); ?>
          <?php submit_button(); ?>
        </form>
      </div>
      <?php
    }
}

if( is_admin() )
    $proud_general_settings_page = new ProudGeneralSettingsPage();