<?php
class ProudAdminSocialSettingsPage extends ProudSettingsPage
{
    /**
     * Start up
     */
    public function __construct()
    {
      parent::__construct(
        'social', // Key
        [ // Submenu settings
          'parent_slug' => 'proudsettings',
          'page_title' => 'Social feeds',
          'menu_title' => 'Social feeds',
          'capability' => 'edit_proud_options',
        ],
        '', // Option
        [   // Options
          'social_feeds' => '',
          'social_map' => '',
        ]
      );
    }


    /** 
     * Sets fields
     */
    public function set_fields( ) {
      $this->fields = [
        'social_feeds_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Social feeds') . '</h3>',
        ],
        'social_feeds' => [
          '#type' => 'textarea',
          '#title' => __pcHelp('Social network accounts'),
          '#description' => __pcHelp('These accounts will be included on the social wall. Enter each account, one per line in the form <br/><code>[service]:[account]</code>'),
        ],

        'social_map_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Social map') . '</h3>',
        ],
        'social_map' => [
          '#type' => 'textarea',
          '#title' => __pcHelp('Services map layers'),
          '#description' => __pcHelp('These feeds will be included on the services map. Enter each account/feed, one per line in the form <br/><code>[service]:[account/feed url]</code>'),
        ],
      ];

    }

    /**
     * Print page content
     */
    public function settings_content() {
      $this->print_form( );
    }

    /** 
     * Saves form values
     */
    public function save( &$values ) {
      parent::save($values);
      // @todo
      // $this->update_aggregator( $key, $value );
    }

    public function update_aggregator( $key, $value ) {
      $json = $this->json_string($value);
      $json = $this->json_wrapper($json, 'local');
      //print_R($json);die();
      $this->api('/user/update', $json);
    }


    /**
     * Returns a json array for a Drupal entity field.
     */
    public function json_field($field) {
      $agency = array();
      foreach($field[LANGUAGE_NONE] as $item) {
        $exploded = explode('/', $item['url']);
        $agency[$item['service']] = empty($agency[$item['service']]) ? array('feeds' => array()) : $agency[$item['service']];
        $agency[$item['service']]['feeds'][] = array(
          'type' => 'account',
          'query' => array_pop($exploded),
        );
      }
      return $agency;
    }

    /**
     * Returns a json array for a string.
     */
    public function json_string($string) {
      $agency = array();
      $string = trim($string);
      $arr = explode("\n", $string);
      $arr = array_filter($arr, 'trim'); // remove any extra \r characters left behind

      foreach ($arr as $line) {
        $line = explode(':', $line);
        $title = $line[1];
        $options = explode('|', $line[0]);
        $arg = $options[1];
        $service = is_array($options) ? $options[0] : $options;
        $data = array();
        switch ($service) {
          case 'foursquare': 
            $data = array(
              'type' => $title,
              'url' => 'query',
            );
            break;
          case 'socrata': 
          case 'gtfs':
            $data = array(
              'type' => $title,
              'url' => $arg,
            );
            break;
          case 'rss':
          case 'ical':
          case 'yelp':
            $data = array(
              'type' => $title,
              'location' => $arg,
            );
            break;
          default:
            $data = array(
              'type' => 'account',
              'query' => $title,
            );
            break;
        }
        $agency[$service] = empty($agency[$service]) ? array('feeds' => array()) : $agency[$service];
        $agency[$service]['feeds'][] = $data;

        // Open311 is a little different
        if ($service == 'open311') {
          foreach(explode($arg) as $subarg) {
            $agency[$service]['feeds'][] = array('status' => $arg);
          }
        }
      } // foreach

      return $agency;
    }

    /**
     * Wraps a list of feeds with the necessary JSON variables.
     */
    public function json_wrapper($agency, $title, $delete_mode = TRUE) {
      $agency['name'] = $this->sanitize_key($title);
      $agency['delete_mode'] = $delete_mode;

      return array(
        'name' => $this->username(),
        'agencies' => array($agency),
      );
    }

    /**
     * Implements hook_node_update().
     */
    public function api($endpoint, $data) {
      $data_string = json_encode($data);
      //$url = 'http://' . 'proudCity:UM0o2aBUbtrsunGm2lvtnSFkz@' . str_replace('http://', '', variable_get('$this->url', 'my.getproudcity.com:8080')) . $endpoint;
      $user = PROUD_AGGREGATOR_KEY .':'. PROUD_AGGREGATOR_SECRET;
      $url = PROUD_AGGREGATOR_URL . $endpoint;

      $ch = curl_init();
      $options = array(
          CURLOPT_URL            => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_HEADER         => true,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_ENCODING       => "",
          CURLOPT_AUTOREFERER    => true,
          CURLOPT_CONNECTTIMEOUT => 120,
          CURLOPT_TIMEOUT        => 120,
          CURLOPT_MAXREDIRS      => 10,
          CURLOPT_CUSTOMREQUEST  => "POST",
          CURLOPT_POSTFIELDS     => $data_string,
          CURLOPT_USERPWD        => $user,
          CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
          curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json'
          )),
      );
      curl_setopt_array($ch, $options);
      $response = curl_exec($ch);
    }


    /**
     * Helper. Returns the username string for the current city.
     */
    private function username() {
      return get_option('city') .',_'. get_option('state');
    }

    /**
     * Helper. Returns the username string for the current city.
     */
    private function sanitize_key($title) {
      return str_replace([' ', '-'], '_', strtolower($title));
    }

}

if( is_admin() )
    $proud_admin_social_settings_page = new ProudAdminSocialSettingsPage();