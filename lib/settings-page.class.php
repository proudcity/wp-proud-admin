<?php
/**
 * @author ProudCity
 */

abstract class ProudSettingsPage {

    public $options; // Holds the values to be used in the fields callbacks
    public $option; // Optional option string to use array as basis
    private $key; // for forms, nonce, ect
    public $hook; // The menu callback
    public $submenu; // Submenu options for admin menu
    public $fields; // Form fields
    public $form; // FormHelper 

    /**
     * Start up
     * @param string $key
     * @param array $submenu
     *    with keys: parent_slug, page_title, menu_title, capability
     * @param string $option (use single option array as basis)
     * @param array $options (uses multiple options as basis for forms)
     */
    public function __construct( $key, $submenu, $option = '', $options = [], $weight = 10 ) {
      $this->key = $key;
      $this->submenu = $submenu;
      // Single mode or multiple?
      if( $option ) {
        $this->option = $option;
        $this->options = [];
      }
      else {
        $this->option = '';
        $this->options = $options;
      }
      add_action( 'admin_menu', array( $this, 'create_menu' ), $weight );
    }

    /**
     * set_fields called on form creation
     */
    abstract protected function set_fields( );

    /** 
     * Rebuilds form values from options
     */
    public function build_options( ) {
      // We're in single option mode
      if( $this->option ) {
        $this->options = get_option( $this->option );
      }
      // Array of options
      else {
        foreach ( $this->options as $option => $default ) {
          $this->options[$option] = get_option( $option, $default ? $default : false );
        }
      }
    }

    /**
     * Gets field ids for fields
     * @param array $fields, subset of fields to fetch
     * in form {fieldname}_id => 'theId'
     */
    public function get_field_ids( $fields = [] ) {
      if( empty( $fields ) ) {
        $fields = array_keys( $this->fields );
      }
      $ids = [];
      foreach ( $fields as $fieldname ) {
        $ids[ $fieldname . '_id' ] = $this->form->get_field_id( $fieldname );
      }
      return $ids;
    }

    /**
     * Gets field names for fields
     * @param array $fields, subset of fields to fetch
     * in form {fieldname}_name => 'theName'
     */
    public function get_field_names( $fields = [] ) {
      if( empty( $fields ) ) {
        $fields = array_keys( $this->fields );
      }
      $names = [];
      foreach ( $fields as $fieldname ) {
        $names[ $fieldname . '_name' ] = $this->form->get_field_name( $fieldname );
      }
      return $names;
    }

    /** 
     * Creates menu 
     */
    public function create_menu() {
      // Extract submenu vars
      extract($this->submenu);
      // Menu page entry
      if( !$parent_slug ) {
        $hook = add_menu_page(
          $page_title, 
          $menu_title, 
          $capability,
          $this->key,
          array( $this, 'settings_page' ),
          $icon_url
        );
      }
      // Submenu page entry
      else {
        $hook = add_submenu_page( 
            $parent_slug, 
            $page_title, 
            $menu_title, 
            $capability,
            $this->key,
            array( $this, 'settings_page' )
        );
      }
      $this->hook = $hook;
      // Add action to load our form
      add_action( 'load-' . $hook, array( $this, 'load_form' ) );
    }

    /**
     * Calls form actions and builds fields
     */
    public function load_form() {
      $this->set_fields( );
      $this->build_options( );
      $this->form = new \Proud\Core\FormHelper( $this->key, $this->fields, 1, 'form' );
    }

    /**
     * Deal with nonce, print page content
     */
    public function settings_page() {
      // Do we have post?
      if(isset($_POST['_wpnonce'])) {
        if( wp_verify_nonce( $_POST['_wpnonce'], $this->key ) ) {
          $this->save($_POST);
        }
      }
      // Print content
      $this->settings_content();
    }

    /**
     * Prints form
     * @param array $args: override default settins
     */
    public function print_form( $args = [] ) {
      $this->build_options( );
      $args = array_merge( array(
        'button_text' => __pcHelp('Save'),
        'method' => 'post',
        'action' => '',
        'instance' => $this->options,
        'fields' => $this->fields
      ), $args );
      // Print form content
      $this->form->printForm( $args );
    }

    /**
     * build_options called on form creation
     */
    abstract protected function settings_content( );

    /**
     * Deals with submit values
     */
    public function escape_post_value( &$value, $field ) {
      // If checkbox, and no value, set to 0
      if( 'checkbox' === $field['#type'] && !isset( $value ) ) {
        $value = 0;
      }
      // WYSIWYG
      else if( 'editor' === $field['#type'] ) {
        $value = wp_kses_post( $value );
      }
      // Text areas (can contain html)
      else if( 'textarea' === $field['#type'] ) {
        if( !empty( $field['#save_method'] ) ) {
          if( $field['#save_method'] === 'stripslashes' ) {
            $value = stripslashes($value);
            return;
          }
        }
        $value = $value;
      }
      // Array values (checkboxes)
      else if( 'checkboxes' === $field['#type'] && is_array( $value ) ) {
        foreach ( $value as $key => &$v ) {
          $v = esc_attr($v);
        }
      }
      // Groups of fields
      else if( 'group' === $field['#type'] && is_array( $value ) ) {
        foreach ( $value as $key => &$value_group ) {
          foreach ($value_group as $field_key => &$field_value) {
            if( isset( $field['#sub_items_template'][$field_key] ) ) {
              // Recurse with field content
              $this->escape_post_value( $field_value, $field['#sub_items_template'][$field_key] );
            }
            else {
              unset( $value_group[$field_key] );
            }
          }
        }
      }
      else {
        $value = esc_attr( $value );
      }
    }

    /** 
     * Saves form values
     */
    public function save( &$raw_values ) {
      $values = $this->form->getFormValues( $raw_values );
      foreach ( array_keys( $this->options ) as $key ) {
        // Escape
        $this->escape_post_value( $values[$key], $this->fields[$key] );
        // Multiple option mode ?
        if ( !$this->option && isset( $values[$key] ) ) {
          update_option( $key, $values[$key] );
        }
      }
      // Single option mode
      if( $this->option ) {
        update_option( $this->option, $values );
      }
      $raw_values = $values;
    }
}