<?php
class ProudIntegrationsSettingsPage
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
      $this->key = 'integrations';
      $this->fields = null;
    }

    // create custom plugin settings menu
    

    public function create_menu() {

      // Integrations page (top level)
      add_submenu_page( 
          'proudsettings',
          'Integrations',
          'Integrations',
          'manage_options',
          $this->key,
          array($this, 'settings_page')
      );

      //call register settings function
      add_action( 'admin_init', array($this, 'register_settings') );
    }


    public function register_settings() {
      register_setting( $this->key, 'search_service' );
      register_setting( $this->key, 'search_google_key' );

      register_setting( $this->key, 'payment_service' );
      register_setting( $this->key, 'payment_stripe_type' );
      register_setting( $this->key, 'payment_stripe_key' );
      register_setting( $this->key, 'payment_stripe_secret' );

      register_setting( $this->key, '311_service' );
      register_setting( $this->key, '311_link_create' );
      register_setting( $this->key, '311_link_status' );
    }

    private function build_fields(  ) {
      $this->fields = [
        'search_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Search') . '</h3>',
        ],
        'search_service' => [
          '#type' => 'radios',
          '#title' => __pcHelp('Full search service'),
          '#description' => __('The type of search to fallback on when users don\'t find what they\'re looking for in the autosuggest search and make a full site search.', 'proud-settings'),
          '#name' => 'search_service',
          '#options' => array(
            'wordpress' => __pcHelp( 'Standard site search' ),
            'google' => __pcHelp( 'Customized Google search' ),
            'solr' => __pcHelp( 'Apache Solr search', '//proudcity.com/contact', null, array('link_text' => 'Contact us to learn more &raquo;') ),
          ),
          '#value' => get_option('search_service', 'wordpress')
        ],
        'search_google_key' => [
          '#type' => 'text',
          '#title' => __pcHelp('Google search key'),
          '#description' => __pcHelp(
            '@todo'
          ),
          '#name' => 'search_google_key',
          '#value' => get_option('search_google_key'),
          '#states' => [
            'visible' => [
              'search_service' => [
                'operator' => '==',
                'value' => ['google'],
                'glue' => '||'
              ],
            ],
          ],
        ],

        'payments_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Payments') . '</h3>',
        ],
        'payment_service' => [
          '#type' => 'radios',
          '#title' => __pcHelp('Payment processor'),
          //'#description' => __pcHelp(''),
          '#name' => 'payment_service',
          '#options' => array(
            'stripe' => __pcHelp( 'Stripe' ),
            'link' => __pcHelp( 'Link out to other provider' ),
          ),
          '#value' => get_option('payment_service', 'link')
        ],
        'payment_stripe_type' => [
          '#type' => 'radios',
          '#title' => __pcHelp('Stripe account type'),
          //'#description' => __pcHelp(''),
          '#name' => 'payment_stripe_type',
          '#options' => array(
            'test' => __pcHelp( 'Test' ),
            'production' => __pcHelp( 'Production' ),
          ),
          '#value' => get_option('payment_stripe_type'),
          '#states' => [
            'visible' => [
              'payment_service' => [
                'operator' => '==',
                'value' => ['stripe'],
                'glue' => '||'
              ],
            ],
          ],
        ],
        'payment_stripe_key' => [
          '#type' => 'text',
          '#title' => __pcHelp('Stripe key'),
          //'#description' => __pcHelp(''),
          '#name' => 'payment_stripe_key',
          '#value' => get_option('payment_stripe_key'),
          '#states' => [
            'visible' => [
              'payment_service' => [
                'operator' => '==',
                'value' => ['stripe'],
                'glue' => '||'
              ],
            ],
          ],
        ],
        'payment_stripe_secret' => [
          '#type' => 'text',
          '#title' => __pcHelp('Stripe secret'),
          //'#description' => __pcHelp(''),
          '#name' => 'payment_stripe_secret',
          '#value' => get_option('payment_stripe_secret'),
          '#states' => [
            'visible' => [
              'payment_service' => [
                'operator' => '==',
                'value' => ['stripe'],
                'glue' => '||'
              ],
            ],
          ],
        ],


        '311_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('311 issue reporting') . '</h3>',
        ],
        '311_service' => [
          '#type' => 'radios',
          '#title' => __pcHelp('311 provider'),
          //'#description' => __pcHelp(''),
          '#name' => '311_service',
          '#options' => array(
            'seeclickfix' => __pcHelp( 'SeeClickFix' ),
            'link' => __pcHelp( 'Link out to other provider' ),
          ),
          '#value' => get_option('311_service', 'seeclickfix')
        ],
        '311_link_create' => [
          '#type' => 'text',
          '#title' => __pcHelp('Create issue URL'),
          '#value' => get_option('311_link_create'),
          '#name' => '311_link_create',
          '#states' => [
            'visible' => [
              '311_service' => [
                'operator' => '==',
                'value' => ['link'],
                'glue' => '||'
              ],
            ],
          ],
        ],
        '311_link_status' => [
          '#type' => 'text',
          '#title' => __pcHelp('Lookup issue URL'),
          '#value' => get_option('311_link_status'),
          '#name' => '311_link_status',
          '#states' => [
            'visible' => [
              '311_service' => [
                'operator' => '==',
                'value' => ['link'],
                'glue' => '||'
              ],
            ],
          ],
        ],

        'social_title' => [
          '#type' => 'html',
          '#html' => 
            '<h3>' . __pcHelp('Social feeds') . '</h3>' .
            '<a class="btn btn-default" href="/wp-admin/admin.php?page=wp-proud-admin%2Fsettings%social.php#">Set up social feeds &raquo</a>'
        ],

        'mapbox' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Custom map layer') . '</h3>',
        ],
        'mapbox_token' => [
          '#type' => 'text',
          '#title' => __pcHelp('Mapbox token'),
          '#value' => get_option('mapbox_token'),
          '#name' => 'mapbox_token',
          // @todo: desc
        ],
        'mapbox_map' => [
          '#type' => 'text',
          '#title' => __pcHelp('Mapbox map id'),
          '#value' => get_option('mapbox_map'),
          '#name' => 'mapbox_map',
          // @todo: desc
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
    $proud_integrations_settings_page = new ProudIntegrationsSettingsPage();