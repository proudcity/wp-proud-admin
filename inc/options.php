<?php
class ProudAdminSettingsPage
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
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_menu_page(
            'ProudCity Settings', 
            'ProudCity Settings', 
            'manage_options', 
            'proud-setting-admin', 
            array( $this, 'create_admin_page' )
        );

    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'my_option_name' );
        ?>
        <div class="wrap">
            <h2>My Settings</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'my_option_group' );   
                do_settings_sections( 'proud-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'my_option_group', // Option group
            'my_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'mailchimp', // ID
            'Mailchimp', // Title
            array( $this, 'print_section_info' ), // Callback
            'proud-setting-admin' // Page
        );  

        add_settings_field(
            'mailchimp_api_key', // ID
            'Mailchimp api key', // Title 
            array( $this, 'mailchimp_api_key_callback' ), // Callback
            'proud-setting-admin', // Page
            'mailchimp' // Section           
        );

        add_settings_section(
            'search', // ID
            'Search', // Title
            array( $this, 'print_section_info' ), // Callback
            'proud-setting-admin' // Page
        ); 

        add_settings_field(
            'search_type', 
            'Search type', 
            array( $this, 'search_type_callback' ), 
            'proud-setting-admin', 
            'search'
        );      
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['id_number'] ) )
            $new_input['id_number'] = absint( $input['id_number'] );

        if( isset( $input['title'] ) )
            $new_input['title'] = sanitize_text_field( $input['title'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        //print 'Enter your settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function mailchimp_api_key_callback()
    {
        printf(
            '<input type="text" id="id_number" name="my_option_name[id_number]" value="%s" />',
            isset( $this->options['mailchimp_api_key'] ) ? esc_attr( $this->options['mailchimp_api_key']) : ''
        );
        echo '<p class="help">The API key for connecting with your MailChimp account. <a target="_blank" href="https://admin.mailchimp.com/account/api">Get your API key here.</a></p>';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function search_type_callback()
    {
        $html = '<label><input type="radio" name=" value="default" '. $type == 'default' ? 'selected="selected"' : '' .' /> Standard site search</label>';
        $html = '<label><input type="radio" value="google" value="" /> Embedded Google search</label>';
        $html = '<label><input type="radio" value="solr" value="" /> Apache Solr</label>';
        print $html;
    }
}

if( is_admin() )
    $proud_admin_settings_page = new ProudAdminSettingsPage();