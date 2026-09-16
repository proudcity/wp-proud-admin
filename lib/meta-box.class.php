<?php
/**
 * @author ProudCity
 */

abstract class ProudMetaBox {

    public $options = []; // Holds the values to be used in the fields callbacks, MUST be overridden
    // protected, not private: ProudTermMetaBox used to redeclare its own public
    // $key, which shadowed this one, so parent methods reading $this->key saw an
    // unset property. One property, visible to both.
    protected $key; // for forms, nonce, ect
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
      // The gate, not save_meta() directly: nine subclasses across wp-proud-agency,
      // wp-proud-meeting, wp-proud-location and wp-proud-topic override save_meta()
      // and none call parent::save_meta(), so guards living in the base method were
      // simply skipped for them. Hooking a final wrapper makes them unskippable.
      add_action( 'save_post', array( $this, 'save_meta_gate' ), 10, 3 );
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
      // Paired with nonce_is_valid() in save_meta()/save_term_meta().
      wp_nonce_field( $this->nonce_action(), $this->nonce_name() );
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
     * Nonce action for this metabox's form.
     */
    public function nonce_action() {
      return 'proud_metabox_save_' . strtolower( $this->key );
    }

    /**
     * Nonce field name for this metabox's form.
     */
    public function nonce_name() {
      return 'proud_metabox_nonce_' . strtolower( $this->key );
    }

    /**
     * Was this metabox's own form the thing that was submitted?
     *
     * Fields are namespaced form-{key}[{number}][...], so their absence means
     * this is a programmatic save (REST, WP-CLI, wp_update_post) or another
     * metabox's submission, and there is nothing here for us to write.
     */
    protected function form_was_submitted() {
      // FormHelper lowercases the key when building field names, so match that.
      return isset( $_POST[ 'form-' . strtolower( $this->key ) ] );
    }

    /**
     * Verifies this metabox's nonce.
     */
    protected function nonce_is_valid() {
      $name = $this->nonce_name();
      if ( empty( $_POST[ $name ] ) ) {
        return false;
      }
      return (bool) wp_verify_nonce(
        sanitize_text_field( wp_unslash( $_POST[ $name ] ) ),
        $this->nonce_action()
      );
    }

    /**
     * Validate and return values
     */
    public function validate_values( $post ) {
      if( empty( $this->form ) ) {
        return false;
      }
      // This used to read `!empty( $screen )` -- an undefined local variable,
      // not $this->screen -- so the post-type check never ran and any metabox
      // would write its fields onto any post type.
      if( !empty( $this->screen ) && $post->post_type !== $this->screen ) {
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
     * Authorisation gate for a post save.
     *
     * save_post fires for every post save on the site -- autosaves, revisions,
     * REST, WP-CLI -- and carries no authorisation of its own.
     *
     * @return bool Whether save_meta() may run.
     */
    protected function can_save( $post_id, $post ) {
      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return false;
      }
      if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return false;
      }
      if ( !$this->form_was_submitted() ) {
        return false;
      }
      if ( !$this->nonce_is_valid() ) {
        return false;
      }
      return (bool) current_user_can( 'edit_post', $post_id );
    }

    /**
     * What is actually hooked to save_post.
     *
     * final on purpose. Subclasses override save_meta() freely and none of them
     * call parent::save_meta(), so any guard placed there is one forgotten
     * parent:: call away from being skipped. Putting the guard in a wrapper
     * that cannot be overridden means a new subclass is safe by default.
     */
    public final function save_meta_gate( $post_id, $post, $update ) {
      if ( !$this->can_save( $post_id, $post ) ) {
        return;
      }
      $this->save_meta( $post_id, $post, $update );
    }

    /**
     * Saves form values.
     *
     * Reached only through save_meta_gate(), which has already established that
     * this is our own authorised form submission.
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
        // edited_{taxonomy} / create_{taxonomy} carry no authorisation of their
        // own, so the same guards as save_meta() apply here.
        if ( !$this->form_was_submitted() ) {
            return;
        }
        if ( !$this->nonce_is_valid() ) {
            return;
        }
        if ( !current_user_can( 'edit_term', $term_id ) ) {
            return;
        }
        if ( empty( $this->form ) ) {
            return;
        }
        // Grab form values from Request
        $values = $this->form->getFormValues( $_POST );
        if ( !empty( $values ) ) {
            $this->save_all( $values, $term_id );
        }
    } // save_term_meta
}
