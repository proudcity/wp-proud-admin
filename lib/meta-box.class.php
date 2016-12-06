<?php
/**
 * @author ProudCity
 */

abstract class ProudMetaBox {

    public $options = []; // Holds the values to be used in the fields callbacks, MUST be overridden
    private $key; // for forms, nonce, ect
    public $post; // Content for meta fields
    public $fields; // Form fields
    public $form; // FormHelper
    public $id; // metabox id 
    public $title; // metabox title
    public $screen; // metabox screen
    public $position; // metabox position
    public $priority; // metabox priority

    /**
     * Start up
     * @param string $key
     * @param string $title // metabox title
     * @param string $screen // metabox screen
     * @param string  $position // metabox position
     * @param string $priority // metabox priority
     */
    public function __construct( $key, $title, $screen = null, $position = 'advanced', $priority = 'default' ) {
      $this->key = $key;
      $this->title = $title;
      $this->screen = $screen;
      $this->position = $position;
      $this->priority = $priority;

      // Add save option
      add_action( 'save_post', array( $this, 'save_meta' ), 10, 3 );
      add_action( 'admin_init', array( $this, 'register_box' ) );
    }

    /**
     * Register box hook
     */
    public function register_box() {
      add_meta_box( 
        $this->key . '_meta_box',
        $this->title,
        array($this, 'settings_content'),
        $this->screen, 
        $this->position,
        $this->priority
      );
      // Set fields, no display
      $this->set_fields( false );
      $this->form = new \Proud\Core\FormHelper( $this->key, $this->fields, 1, 'form' );
    }

    /**
     * Called on form creation
     * @param $displaying : false if just building form, true if about to display
     * Use displaying:true to do any difficult loading that should only occur when
     * the form actually will display
     */
    abstract protected function set_fields( $displaying );

    /** 
     * Rebuilds form values from options
     */
    public function build_options( $id = null ) {
      if( !$id ) {
        // New post
        if( !is_object( $this->post ) ) {
          return;
        }
        $id = $this->post->ID;
      }
      $meta = get_post_meta( $id );
      foreach ( $this->options as $option => $default ) {
        if( isset( $meta[$option][0] ) ) {
          $this->options[$option] = $meta[$option][0];
        }
      }
    }

    /**
     * Returns build options
     */
    public function get_options( $id = null ) {
      $this->build_options( $id );
      return $this->options;
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
     * Prints form fields
     * @param bool $print_form: should the function print form immediately or return content?
     */
    public function print_form( $post ) {
      // Set post
      $this->post = $post;
      // Set fields, displaying
      $this->set_fields( true );
      $this->build_options( );
      // Print fields
      $this->form->printFields( $this->options, $this->fields, 1, 'form' );
    } 

    /**
     * Prints form
     */
    public function settings_content( $post ) {
      $this->print_form( $post );
    }

    /** 
     * Validate and return values
     */
    public function validate_values( $post ) {
      // We have screen requirements not met 
      if( empty( $this->form ) || ( !empty( $screen ) && $post->post_type !== $screen ) ) {
        return false;
      }
      // Return values
      return $this->form->getFormValues( $_POST );
    }

    /** 
     * Runs through options, saves
     */
    public function save_all( $values, $post_id ) {
      foreach ( array_keys( $this->options ) as $key ) {
        if ( isset( $values[$key] ) ) {
          update_post_meta( $post_id, $key, $values[$key] );
        }
      }
    }

    /** 
     * Saves form values
     */
    public function save_meta( $post_id, $post, $update ) {
      // Grab form values from Request
      $values = $this->validate_values( $post );
      if( !empty( $values ) ) {
        $this->save_all( $values, $post_id );
      }
    }
}

// Abstract class for term MetaBox
abstract class ProudTermMetaBox extends ProudMetaBox {

    /**
     * Start up
     * @param string $key
     * @param string $title // metabox title
     */
    public function __construct( $key, $title ) {
      $this->key = $key;
      $this->title = $title;

      // Load form / fields
      add_action( 'admin_init', array( $this, 'register_form' ) );

      add_action( $this->key . '_add_form_fields', array($this, 'settings_content'), 10, 2 );
      add_action( $this->key . '_edit_form_fields', array($this, 'settings_content'), 10, 2 );

      add_action( 'edited_' . $this->key, array($this, 'save_term_meta'), 10, 2 );  
      add_action( 'create_' . $this->key, array($this, 'save_term_meta'), 10, 2 );
    }

    /** 
     * Builds field forms
     */
    public function register_form() {
      // Set fields, no display
      $this->set_fields( false );
      $this->form = new \Proud\Core\FormHelper( $this->key, $this->fields, 1, 'form' );
    }

    /** 
     * Rebuilds form values from options
     */
    public function build_options( $id = null ) {
      if( !$id ) {
        // New post
        if( !is_object( $this->post ) ) {
          return;
        }
        $id = $this->post->term_id;
      }

      $meta = get_term_meta( $id );

      $this->options = count($this->options) ? $this->options : array_keys($this->fields);
      foreach ( $this->options as $option => $default ) {
        if( isset( $meta[$option][0] ) ) {
          $this->options[$option] = $meta[$option][0];
        }
      }
    }

    /**
     * Returns build options
     */
    public function get_options( $id = null ) {
      $this->build_options( $id );
      return $this->options;
    }


    /** 
     * Runs through options, saves
     */
    public function save_all( $values, $term_id ) {
      foreach ( array_keys( $this->options ) as $key ) {
        if ( isset( $values ) ) {
          update_term_meta( $term_id, $key, $values[$key] );
        }
      }
    }

    /**
     * Prints form
     */
    public function settings_content( $post ) {
      $this->print_form( $post );
    }

    /** 
     * Saves form values
     */
    public function save_term_meta( $term_id, $taxonomy ) {
      // Grab form values from Request
      $values = $this->form->getFormValues( $_POST );
      if( !empty( $values ) ) {
        $this->save_all( $values, $term_id );
      }
    }
}