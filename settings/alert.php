<?php

class ProudAlertPage extends ProudSettingsPage
{
    /**
     * Start up
     */
    public function __construct()
    {

      /**
       * Allows other plugins to add to our settings without embedding settings
       * and increasing plugin dependencies. You must add to this so that our
       * options will save as expected.
       *
       * @since 2022.11.03
       * @author Curtis
       *
       * @param   array             Array of current options
       */
      $alert_options = apply_filters( 'pc_admin_alert_options',
        array(
          'alert_active' => '',
          'alert_message' => '',
          'alert_severity' => '',
        )
      );

      parent::__construct(
        'alertbar', // Key
        [ // Submenu settings
          'parent_slug' => 'proudsettings',
          'page_title' => 'Alert bar',
          'menu_title' => 'Alert bar',
          'capability' => 'edit_proud_options',
        ],
        '', // Option
        $alert_options
      );
    }

    /**
     * Sets fields
     */
    public function set_fields( ) {

      $alert_fields_array = [
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

      /**
       * Adds fields to the form. This WILL NOT save the fields see pc_admin_alert_options for
       * the key you need to add to have your displayed fields save.
       *
       * @since 2022.11.03
       * @author Curtis
       *
       * @param     array       $alert_fields_array                    Array of existing fields that we can modify
       */
      $this->fields = apply_filters( 'pc_admin_alert_settings', $alert_fields_array );

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