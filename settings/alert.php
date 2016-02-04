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
          'edit_proud_options',
          $this->key,
          array($this, 'settings_page')
      );

      $this->options = [
        'alert_active',
        'alert_message',
        'alert_severity',
      ];
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

      // Do we have post?
      if(isset($_POST['_wpnonce'])) {
        if( wp_verify_nonce( $_POST['_wpnonce'], $this->key ) ) {
          $this->save($_POST);
          $this->build_fields();
        }
      }
      
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
      foreach ($this->options as $key) {
        if (isset($values[$key]) || $this->fields[$key]['#type'] == 'checkbox') {
          $value = $key === 'alert_message' ? wp_kses_post( $values[$key] ) : esc_attr( $values[$key] );
          update_option( $key, $value );
        }
      }
    }
}

if( is_admin() )
    $proud_alert_page = new ProudAlertPage();