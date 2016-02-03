<?php
class ProudAdminSocialSettingsPage
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
      $this->key = 'social';
      $this->fields = null;
    }

    // create custom plugin settings menu
    

    public function create_menu() {

      // Integrations page (top level)
      //add_menu_page('Integrations', 'Proud Settings', 'manage_options', __FILE__, array($this, 'settings_page') , plugins_url('/images/icon.png', __FILE__) );
      add_submenu_page( 
          'proudsettings',
          'Social feeds',
          'Social feeds',
          'edit_proud_options',
          $this->key,
          array($this, 'settings_page')
      );

      //call register settings function
      add_action( 'admin_init', array($this, 'register_settings') );
      add_action( 'update_option_social_feeds', array($this, 'update_social_feeds'), 10, 2 );

    }


    public function register_settings() {
      register_setting( $this->key, 'social_feeds' );
      register_setting( $this->key, 'social_map' );
    }

    private function build_fields(  ) {
      $this->fields = [
        'social_feeds_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Social feeds') . '</h3>',
        ],
        'social_feeds' => [
          '#type' => 'textarea',
          '#title' => __pcHelp('Social network accounts'),
          '#description' => __pcHelp('These accounts will be included on the social wall. Enter each account, one per line in the form <br/><code>[service]:[account]</code>'),
          '#name' => 'social_feeds',
          '#value' => get_option('social_feeds')
        ],

        'social_map_title' => [
          '#type' => 'html',
          '#html' => '<h3>' . __pcHelp('Social map') . '</h3>',
        ],
        'social_map' => [
          '#type' => 'textarea',
          '#title' => __pcHelp('Services map layers'),
          '#description' => __pcHelp('These feeds will be included on the services map. Enter each account/feed, one per line in the form <br/><code>[service]:[account/feed url]</code>'),
          '#name' => 'social_map',
          '#value' => get_option('social_map')
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

    public function update_social_feeds( $old_value, $value ) {
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