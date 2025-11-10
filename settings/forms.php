<?php
/**
 * Provides the displayable shortcodes for Policies
 *
 * @package ProudSettings/ProudGFSettings
 * @author  ProudCity <curtis@proudcity.com>
 * @license https://opensource.org/licenses/gpl-license.php GNU Public License
 * @see     https://proudcity.com
 */
class ProudGFSettings
{

    private static $_instance;

    /**
     * Spins up the instance of the plugin so that we don't get many instances running at once
     *
     * @since  1.0
     * @author Curtis <curtis@proudcity.com>
     *
     * @uses $instance->init()                      The main get it running function
     *
     * @return null
     */
    public static function instance()
    {

        if (! self::$_instance) {
            self::$_instance = new ProudGFSettings();
            self::$_instance->init();
        }

    } // instance

    /**
     * Spins up all the actions/filters in the plugin to really get the engine running
     *
     * @since  1.0
     * @author Curtis <curtis@proudcity.com>
     *
     * @return null
     */
    public function init()
    {
        add_action('admin_menu', [__CLASS__, 'registerSubMenu']);
    } // init

    public static function registerSubMenu()
    {
        add_submenu_page(
            'proudsettings', // parent_slug
            'Form Settings', // page_title
            'Form Settings', // menu_title
            'edit_posts', // capability
            'form-settings', // menu_slug
            [__CLASS__, 'formSettings'], // callback
        );

    }

    public static function formSettings()
    { ?>
        <div class="wrap"><h1>Form Settings</h1></div>

    <?php
    }

}

ProudGFSettings::instance();

