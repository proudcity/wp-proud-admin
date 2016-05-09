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
      $this->key = 'integrations';
      $this->fields = null;

      add_filter( 'option_page_capability_'.$this->key, array($this, 'option_page_capability') );
      add_action( 'admin_menu', array($this, 'create_menu') );
    }

    // create custom plugin settings menu
    

    public function create_menu() {

      // Integrations page (top level)
      add_submenu_page( 
          'proudsettings',
          'Integrations',
          'Integrations',
          'edit_proud_options',
          $this->key,
          array($this, 'settings_page')
      );

      //call register settings function
      //add_action( 'admin_init', array($this, 'register_settings') );
      $this->options = [
        'google_analytics_key',

        'search_service',
        'search_google_key',

        'payment_service',
        'payment_stripe_type',
        'payment_stripe_key',
        'payment_stripe_secret',

        '311_service',
        '311_link_create',
        '311_link_status',

        'mapbox_token',
        'mapbox_map',
        'google_places_key',

        'embed_code',
        'validation_metatags',
      ];
    }

    private function build_fields(  ) {
      $this->fields = [
        'analytics_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Analytics') . '</h3>',
        ],
        'google_analytics_key' => [
          '#type' => 'text',
          '#title' => __pcHelp('Google Analytics Tracking ID'),
          '#description' => __pcHelp(
            'Copy the Tracking ID code that appears under Admin > Tracking info.'
          ),
          '#name' => 'google_analytics_key',
          '#value' => get_option('google_analytics_key'),
        ],

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
          '#html' => '<h3>' . __pcHelp('Map') . '</h3>',
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
        'google_places_key' => [
          '#type' => 'text',
          '#title' => __pcHelp('Google places key'),
          '#value' => get_option('google_places_key'),
          '#name' => 'google_places_key',
          '#description' => __pcHelp(
            'This is used only for custom locations.'
          ),
        ],       

        'embed_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Embed code') . '</h3>',
        ],
        'embed_code' => [
          '#type' => 'textarea',
          '#title' => __pcHelp('Additional tracking code'),
          '#description' => __pcHelp(
            'This will be included on every page.'
          ),
          '#name' => 'embed_code',
          '#value' => get_option('embed_code', true),
        ],
        'metatags' => [
          '#type' => 'textarea',
          '#title' => __pcHelp('Metatags'),
          '#description' => __pcHelp(
            'These are helpful for validating domain ownership'
          ),
          '#name' => 'validation_metatags',
          '#value' => get_option('validation_metatags', true),
        ],
      ];
    }

    public function settings_page() {
      // Do we have post?
      if(isset($_POST['_wpnonce'])) {
        if( wp_verify_nonce( $_POST['_wpnonce'], $this->key ) ) {
          $this->save($_POST);
        }
      }

      $this->build_fields();
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
        if (isset($values[$key])) {
          // @todo: we should have this go through something like wp_kses_data() for embed code
          $value = ($key == 'validation_metatags' || $key == 'embed_code') ? $values[$key] : esc_attr( $values[$key] );
          update_option($key, $value );
        }
      }
    }
}

if( is_admin() )
    $proud_integrations_settings_page = new ProudIntegrationsSettingsPage();