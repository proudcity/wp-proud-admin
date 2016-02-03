<?php
class ProudAlertPage
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
      $this->key = 'alertbar';
      $this->fields = null;
    }

    // create custom plugin settings menu
    

    public function create_menu() {

      add_submenu_page( 
          'proudsettings',
          'Alert bar',
          'Alert bar',
          'manage_options',
          $this->key,
          array($this, 'settings_page')
      );

      //call register settings function
      add_action( 'admin_init', array($this, 'register_settings') );
    }


    public function register_settings() {
      register_setting( $this->key, 'alert_active' );
      register_setting( $this->key, 'alert_message' );
      register_setting( $this->key, 'alert_severity' );
    }

    private function build_fields(  ) {
      $this->fields = [
        'alert_active' => [
          '#type' => 'checkbox',
          '#title' => __pcHelp('Active'),
          '#name' => 'alert_active',
          '#return_value' => '1',
          '#label_above' => true,
          '#replace_title' => __pcHelp( 'Show alert bar' ),
          '#value' => get_option('alert_active'),
        ],
        'alert_severity' => [
          '#type' => 'radios',
          '#title' => __pcHelp('Severity'),
          //'#description' => __pcHelp(''),
          '#name' => 'alert_severity',
          '#options' => array(
            'danger' => __pcHelp( 'Danger (red)' ),
            'warning' => __pcHelp( 'Warning (yellow)' ),
            'info' => __pcHelp( 'Info (highlight color)' ),
          ),
          '#value' => get_option('alert_severity'),
        ],
        'alert_message' => [
          '#type' => 'editor',
          '#title' => __pcHelp('Message'),
          '#description' => __pcHelp('HTML code and tokens are allowed. Should be no more than one or two sentences with a link.'),
          '#name' => 'alert_message',
          '#value' => get_option('alert_message')
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
    $proud_alert_page = new ProudAlertPage();