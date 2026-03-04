<?php
/**
 * ProudCity GF Settings
 */
class ProudGFSettings
{

    private static $_instance;
    const OPTION = 'proud_gf_label_placement';
    const SETTINGS_GROUP = 'proud_gf_settings';
    const PAGE_SLUG = 'form-settings';

    public static function instance()
    {
        if (! self::$_instance) {
            self::$_instance = new self();
            self::$_instance->init();
        }
        return self::$_instance;
    }

    public function init()
    {
        // Admin UI
        add_action('admin_menu', [$this, 'registerAdminPages']);
        add_action('admin_init', [$this, 'registerSettings']);

        // Apply setting to future Gravity Forms via defaults filter
        add_action('plugins_loaded', [$this, 'hookGravityformsDefaults']);

        add_action('gform_after_save_form', [$this, 'forceDefaultLabelPlacement'], 10, 2);
    }

    /**
     * Adds our admin pages
     *
     * @return null
     */
    public function registerAdminPages()
    {
        add_submenu_page(
            'proudsettings',                   // parent slug (menu slug, not URL)
            __('Form Settings', 'proudcity'),  // page title
            __('Form Settings', 'proudcity'),  // menu title
            'manage_options',                  // capability
            self::PAGE_SLUG,                   // menu slug
            [$this, 'renderSettingsPage']    // callback
        );
    }

    /**
     * Registors our settings
     *
     * @return null
     */
    public function registerSettings()
    {
        // Register option with sanitization and default
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION,
            [
                'type'              => 'string',
                'sanitize_callback' => [$this, 'sanitize_label_placement'],
                'default'           => 'top_label',
            ]
        );

        add_settings_section(
            'proud_gf_section',
            __('Gravity Forms Defaults', 'proudcity'),
            function () {
                echo '<p>' . esc_html__('Choose the default label placement for newly created Gravity Forms.', 'proudcity') . '</p>';
            },
            self::PAGE_SLUG
        );

        add_settings_field(
            'proud_gf_label_placement_field',
            __('Default Label Placement', 'proudcity'),
            [$this, 'renderLabelPlacementField'],
            self::PAGE_SLUG,
            'proud_gf_section'
        );
    }

    /**
     * Sanitize the label
     *
     * @return null
     */
    public function sanitize_label_placement($value)
    {
        $allowed = array_keys($this->choices());
        return in_array($value, $allowed, true) ? $value : 'top_label';
    }

    private function choices(): array {
        return [
            'top_label'    => __('Top label', 'proudcity'),
            'left_label'   => __('Left label', 'proudcity'),
            'right_label'  => __('Right label', 'proudcity'),
            'hidden_label' => __('Hidden label', 'proudcity'),
        ];
    }

    /**
     * Rendors the label placement field
     *
     * @return null
     */
    public function renderLabelPlacementField()
    {
        $current = get_option(self::OPTION, 'top_label');
        echo '<select id="proud_gf_label_placement" name="' . esc_attr(self::OPTION) . '">';
        foreach ($this->choices() as $val => $label) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($val),
                selected($current, $val, false),
                esc_html($label)
            );
        }
        echo '</select>';
        echo '<p class="description">' .
             esc_html__('Affects newly created forms only. Existing forms keep their own setting.', 'proudcity') .
             '</p>';
    }

    /**
     * Renders the settings page
     *
     * @return null
     */
    public function renderSettingsPage()
    {
        if (! current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'proudcity'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Form Settings', 'proudcity') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::SETTINGS_GROUP);
        do_settings_sections(self::PAGE_SLUG);
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Hooks the GF defaults to change it
     *
     * @return $defaults
     */
    public function hookGravityformsDefaults()
    {

        // Only run the filter if Gravity Forms is present (defensive, but optional)
        if (! has_filter('gform_form_settings_defaults')) {
            // Even if GF isn’t loaded yet, adding the filter is safe.
        }


        add_filter('gform_form_settings_defaults', function ($defaults) {
            $placement = get_option(self::OPTION, 'top_label');

            // Validate again at runtime to be extra safe in case the option changed externally.
            $allowed = ['top_label', 'left_label', 'right_label', 'hidden_label'];
            if (! in_array($placement, $allowed, true)) {
                $placement = 'top_label';
            }

            $defaults['labelPlacement'] = $placement;
            return $defaults;
        });
    }

    /**
     * Read your saved option (from the Settings API ).
     */
    function proudGetDefaultLabelPlacement(): string {
        $placement = get_option('proud_gf_label_placement', 'top_label');
        $allowed   = ['top_label', 'left_label', 'right_label', 'hidden_label'];
        return in_array($placement, $allowed, true) ? $placement : 'top_label';
    }

    /**
     * When a form is first created, set its labelPlacement and save it
     * based on the default settings
     *
     * @return null
     */
    public function forceDefaultLabelPlacement($form, $is_new)
    {
        if (! $is_new) {
            return; // Only touch brand-new forms.
        }

        $placement = $this->proudGetDefaultLabelPlacement();

        // Set the form-level setting Gravity Forms uses.
        $form['labelPlacement'] = $placement;

        // Persist the change.
        GFAPI::update_form($form);
    }
}

ProudGFSettings::instance();
