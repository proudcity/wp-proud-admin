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
    }

    private function build_fields(  ) {
      $this->fields = [
        
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