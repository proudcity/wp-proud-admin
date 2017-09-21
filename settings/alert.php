<?php

class ProudAlertPage extends ProudSettingsPage
{
    /**
     * Start up
     */
    public function __construct()
    {
      parent::__construct(
        'alertbar', // Key
        [ // Submenu settings
          'parent_slug' => 'proudsettings',
          'page_title' => 'Alert bar',
          'menu_title' => 'Alert bar',
          'capability' => 'edit_proud_options',
        ],
        '', // Option
        [   // Options
          'alert_active' => '',
          'alert_message' => '',
          'alert_severity' => '',
        ]
      );
    }

    /** 
     * Sets fields
     */
    public function set_fields( ) {

      $this->fields = [
        'alert_active' => [
          '#type' => 'checkbox',
          '#title' => __pcHelp('Active'),
          '#return_value' => '1',
          '#label_above' => true,
          '#replace_title' => __pcHelp( 'Show alert bar' )
        ],
        'alert_severity' => [
          '#type' => 'radios',
          '#title' => __pcHelp('Severity'),
          //'#description' => __pcHelp(''),
          '#options' => array(
            //'danger' => __pcHelp( 'Danger (red)' ),
            //'warning' => __pcHelp( 'Warning (yellow)' ),
            //'info' => __pcHelp( 'Info (highlight color)' ),
            'info' => __pcHelp( 'Highlight color (info notice)' ),
            'red' => __pcHelp( 'Red' ),
            'orange' => __pcHelp( 'Orange' ),
            'yellow' => __pcHelp( 'Yellow' ),
            'green' => __pcHelp( 'Green' ),
            'black' => __pcHelp( 'Black' ),
            'gray' => __pcHelp( 'Gray' ),
          )
        ],
        'alert_message' => [
          '#type' => 'editor',
          '#title' => __pcHelp('Message'),
          '#description' => __pcHelp('HTML code and tokens are allowed. Should be no more than one or two sentences with a link.'),
        ]
      ];

    }
    
    /**
     * Print page content
     */
    public function settings_content() {
      $this->print_form( );
    }
}

if( is_admin() )
    $proud_alert_page = new ProudAlertPage();