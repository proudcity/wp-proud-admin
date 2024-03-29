<?php
class ProudIntegrationsSettingsPage extends ProudSettingsPage
{
    /**
     * Start up
     *
     *
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
      $integration_options = apply_filters( 'pc_admin_integration_options',
        array(
            'search_service' => 'wordpress',
            //'search_google_key' => '',
            //'mapbox_token' => '',
            //'mapbox_map' => '',
            'google_api_key' => '',
            'embed_code' => '',
            'validation_metatags' => '',
        )
      );

      parent::__construct(
        'integrations', // Key
        [ // Submenu settings
          'parent_slug' => 'proudsettings',
          'page_title' => 'Integrations',
          'menu_title' => 'Integrations',
          'capability' => 'edit_proud_options',
        ],
        '', // Option
        $integration_options
      );
    }

    /**
     * Sets fields
     */
    public function set_fields( ) {
      $integrations_fields_array = [
        'search_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Search') . '</h3>',
        ],
        'search_service' => [
          '#type' => 'radios',
          '#title' => __pcHelp('Full search service'),
          '#description' => __('The type of search to fallback on when users don\'t find what they\'re looking for in the autosuggest search and make a full site search.', 'proud-settings'),
          '#options' => array(
            'wordpress' => __pcHelp( 'Standard site search' ),
            //'google' => __pcHelp( 'Customized Google search' ),
            'solr' => __pcHelp( 'Apache Solr search', '//proudcity.com/contact', null, array('link_text' => 'Contact us to learn more &raquo;') ),
          ),
        ],
        /*'search_google_key' => [
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
        ],*/

        /*'payments_title' => [
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
        ],*/

        'google_api_key' => [
          '#type' => 'text',
          '#title' => __pcHelp('Google api key'),
          '#description' => __pcHelp(
            'This is used for custom locations and the Vote app.'
          ),
        ],

        'fa_generate' => [
          '#type' => 'html',
          '#html' =>
            '<h3>' . __pcHelp('Font Awesome') . '</h3>' .
            '<a class="btn btn-default" id="fa-generate" href="#">Regenerate Font Awesome List</a>' .
            '<p class="message"></p>'
        ],

        'social_title' => [
          '#type' => 'html',
          '#html' =>
            '<h3>' . __pcHelp('Social feeds') . '</h3>' .
            '<a class="btn btn-default" href="/wp-admin/admin.php?page=social">Set up social feeds &raquo</a>'
        ],

        /*'mapbox' => [
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
        ],*/


        'embed_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Embed code') . '</h3>',
        ],
        'embed_code' => [
          '#type' => 'textarea',
          '#save_method' => 'stripslashes',
          '#title' => __pcHelp('Additional tracking code'),
          '#description' => __pcHelp(
            'This will be included on every page.'
          ),
        ],
		'validation_metatags' => [
			'#type' => 'textarea',
			'#save_method' => 'stripslashes',
			'#title' => __pcHelp('Metatags'),
			'#description' => __pcHelp(
			'These are helpful for validating domain ownership'
			),
		],
      ];

      /**
       * Adds fields to the form. This WILL NOT save the fields see pc_admin_integration_options for
       * the key you need to add to have your displayed fields save.
       *
       * @since 2022.11.03
       * @author Curtis
       *
       * @param     array       $integrations_fields_array                    Array of existing fields that we can modify
       */
      $this->fields = apply_filters( 'pc_admin_integration_settings', $integrations_fields_array );
    }

    /**
     * Print page content
     */
    public function settings_content() {
      $this->print_form( );
    }
}

if( is_admin() )
    $proud_integrations_settings_page = new ProudIntegrationsSettingsPage();
