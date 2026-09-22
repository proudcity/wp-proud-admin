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
    const CAPABILITY = 'edit_proud_options';

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
        // This file loads on every request so the notification filter below is
        // registered for front-end form submissions. Everything that only ever
        // runs in the admin or the form editor stays behind is_admin() so a
        // front-end request registers nothing it cannot use -- in particular
        // forceDefaultLabelPlacement(), which calls GFAPI::update_form() and
        // relies entirely on its caller for authorization.
        if (is_admin()) {
            // Admin UI
            // Priority 11: the 'proudsettings' parent menu is registered by
            // ProudGeneralSettingsPage (settings/settings.php) on admin_menu
            // at the default priority 10, and wp-proud-admin.php requires
            // this file before settings/settings.php. At equal priority,
            // callback order is registration order, so this submenu would
            // register before its parent exists.
            add_action('admin_menu', [$this, 'registerAdminPages'], 11);
            add_action('admin_init', [$this, 'registerSettings']);

            add_action('gform_after_save_form', [$this, 'forceDefaultLabelPlacement'], 10, 2);

            add_filter('option_page_capability_' . self::SETTINGS_GROUP, [$this, 'optionPageCapability']);
        }

        // Keep notification From headers on our authenticated sending domain.
        add_filter('gform_notification', [__CLASS__, 'filterNotificationSender'], 10, 3);
    }

    /**
     * Domains we are able to DKIM-sign for through Mailgun.
     *
     * A From address outside these is not merely cosmetic: DMARC aligns against
     * the From header domain, so such a notification fails authentication no
     * matter which domain our SMTP credential authenticates.
     */
    const SENDER_DOMAINS = ['proudcity.com'];

    /**
     * The merge tag that resolves to the site admin address, which is always on
     * a domain we can sign for.
     */
    const ADMIN_EMAIL_TAG = '{admin_email}';

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
            self::CAPABILITY,                  // capability
            self::PAGE_SLUG,                   // menu slug
            [$this, 'renderSettingsPage']    // callback
        );
    }

    /**
     * Fixes the capability required to save this settings group via
     * options.php, which otherwise defaults to manage_options regardless of
     * the submenu capability above.
     *
     * option_page_capability_{$option_page} gates the whole of
     * wp-admin/options.php, not just the save: a bare authenticated GET to
     * options.php?option_page=proud_gf_settings, with no action and no
     * nonce, passes this same filter and falls through to the undocumented
     * "All Settings" screen, which dumps every non-serialized row of
     * wp_options -- including secrets such as the WP-Stateless GCP service
     * account key. So this must only relax the capability for a request we
     * have positive proof is our own nonce-verified save POST -- not merely
     * one that looks like it based on $_REQUEST['action'].
     *
     * DESYNC HAZARD (#2939), do not "fix" this back: an earlier version of
     * this method computed $action via
     * sanitize_text_field(wp_unslash($_REQUEST['action'])) and compared it
     * to 'update'. Core computes its own $action at
     * wp-admin/options.php:25 with sanitize_text_field($_REQUEST['action'])
     * directly -- no wp_unslash() first. wp_magic_quotes()
     * (wp-includes/load.php) rebuilds $_GET/$_POST/$_REQUEST with
     * addslashes(), which turns a raw NUL byte into the two literal
     * characters "\" and "0". A request for
     * ?action=update%00 therefore arrives as the slashed string "update\0"
     * (8 chars). Core's check sees "update\0" !== 'update' and is false, so
     * check_admin_referer() at wp-admin/options.php:246 never runs and the
     * request falls through to the "All Settings" dump. But wp_unslash() on
     * that same value restores a real NUL byte, and sanitize_text_field()'s
     * trim() strips NUL (it is in PHP's default trim() charlist), collapsing
     * it back to a clean 'update' -- granting edit_proud_options for a
     * request core itself refused to treat as a save. Six equivalent
     * payloads exist (%00update, update%00, %00%20update, update%00%20,
     * %20%00update, update%20%00), all NUL-based.
     *
     * The fix below never derives a value to compare against core's: it
     * requires REQUEST_METHOD === 'POST' and compares $_POST['action']
     * against 'update' RAW -- no wp_unslash(), no sanitize_text_field(), no
     * normalization of any kind -- plus a valid nonce for
     * self::SETTINGS_GROUP . '-options', the same nonce action
     * settings_fields() emits via wp_nonce_field() and
     * check_admin_referer() verifies at wp-admin/options.php:246. 'update'
     * contains no character addslashes() touches, so the slashed and raw
     * values of that literal are always byte-identical: there is no
     * sanitizer pair left to desync. Do not reintroduce
     * wp_unslash()/sanitize_text_field() on $_POST['action'] -- that
     * reintroduces the bypass.
     *
     * @return string
     */
    public function optionPageCapability($capability)
    {
        if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) {
            return $capability;
        }

        if (! isset($_POST['action']) || 'update' !== $_POST['action']) {
            return $capability;
        }

        if (! isset($_POST['_wpnonce'])
            || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), self::SETTINGS_GROUP . '-options')) {
            return $capability;
        }

        return self::CAPABILITY;
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
        if (! current_user_can(self::CAPABILITY)) {
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

    /**
     * Keeps notification From headers on a domain we can authenticate for.
     *
     * Two problems this solves, both seen in production (#2937):
     *
     *   1. Notifications configured to send *as* someone else -- a resident via
     *      {Email:6}, or a city address such as townclerk@wendellmass.us. DMARC
     *      aligns against the From header domain, so these fail authentication
     *      no matter which domain our SMTP credential authenticates. The
     *      original address is preserved as Reply-To when it is usable, so
     *      replying still reaches the intended person.
     *
     *   2. From names built from resident-name merge tags, producing headers
     *      like "Bruce Ferguson <notify@proudcity.com>". A personal display
     *      name over an unrelated domain is a phishing signal that Microsoft in
     *      particular weights heavily.
     *
     * Gravity Forms fires gform_notification before merge-tag replacement
     * (common.php:2064, replacement at ~2107), so values arrive raw. That is
     * what makes detection reliable: a From name containing a merge tag is
     * resident-derived by definition, and no name-shaped guessing is needed.
     * Static names an admin typed deliberately are left alone.
     *
     * @param array $notification The notification about to be sent.
     * @param array $form         The form object.
     * @param array $entry        The entry being notified about.
     *
     * @return array
     */
    public static function filterNotificationSender($notification, $form, $entry)
    {
        if (! is_array($notification)) {
            return $notification;
        }

        $notification += ['from' => '', 'fromName' => '', 'replyTo' => ''];

        $from = trim((string) $notification['from']);
        if (! self::isAuthenticatedSender($from)) {
            // Only promote the displaced address when it can actually receive
            // mail. A malformed tag such as {admin-email} would land in Reply-To
            // as a literal broken string.
            if ('' === trim((string) $notification['replyTo']) && self::isUsableReplyTo($from)) {
                $notification['replyTo'] = $from;
            }
            $notification['from'] = self::ADMIN_EMAIL_TAG;
        }

        $fromName = trim((string) $notification['fromName']);
        if ('' !== $fromName && self::containsMergeTag($fromName)) {
            $notification['fromName'] = self::organisationName($form);
        }

        /**
         * Filters the notification after sender normalisation.
         *
         * Escape hatch for a site that genuinely needs different handling.
         * Overriding the From address back off our sending domains will break
         * DMARC for that notification.
         */
        return apply_filters('proud_gf_notification_sender', $notification, $form, $entry);
    }

    /**
     * Whether a From value will produce a DMARC-aligned message.
     *
     * True for the admin-email merge tag, which always resolves to an address
     * on a domain we sign for, and for literal addresses on those domains or
     * any subdomain of them.
     */
    protected static function isAuthenticatedSender(string $from): bool
    {
        if ('' === $from) {
            return false;
        }

        // WordPress' is_email() validates the local part with a pattern lacking
        // the D modifier, so PCRE's $ matches before a trailing newline and
        // "abc\n@proudcity.com" is accepted. From is the one value Gravity
        // Forms does not pass through remove_extra_commas(), so reject control
        // characters here rather than relying on is_email() for header safety.
        if (preg_match('/[\x00-\x1F\x7F]/', $from)) {
            return false;
        }

        if (self::ADMIN_EMAIL_TAG === $from) {
            return true;
        }

        if (! is_email($from)) {
            return false;
        }

        $domain = strtolower(substr(strrchr($from, '@'), 1));

        foreach (self::SENDER_DOMAINS as $allowed) {
            $allowed = strtolower($allowed);
            if ($domain === $allowed || str_ends_with($domain, '.' . $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a displaced From value is worth keeping as Reply-To.
     *
     * Accepts real addresses, and field merge tags of the form {Label:ID} which
     * Gravity Forms resolves to the submitted value. Rejects anything else,
     * including malformed tags like {admin-email} that resolve to nothing.
     */
    protected static function isUsableReplyTo(string $from): bool
    {
        if ('' === $from) {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $from)) {
            return false;
        }

        if (is_email($from)) {
            return true;
        }

        // Allowlist the label rather than blocklisting, so header-hostile
        // characters (quotes, angle brackets, commas, @, semicolons) cannot
        // appear even though CR/LF are already excluded above. Spaces and
        // parentheses must be permitted: real field labels look like
        // "Work Email" and "Name (First)". \A and \z rather than ^ and $, which
        // would allow a trailing newline. The field ID is a number with at most
        // one decimal place, so "..." and "." are correctly rejected.
        return (bool) preg_match('/\A\{[A-Za-z0-9 ()\-_.\/&\']+:\d+(?:\.\d+)?\}\z/', $from);
    }

    /**
     * Whether a value contains a Gravity Forms merge tag.
     */
    protected static function containsMergeTag(string $value): bool
    {
        return false !== strpos($value, '{') && false !== strpos($value, '}');
    }

    /**
     * The name to show in place of a resident-derived From name.
     *
     * The site name identifies the sending organisation, which is the whole
     * point -- it matches the domain the message is signed with. Falls back to
     * the form title on the rare site with no blogname set.
     */
    protected static function organisationName($form): string
    {
        $name = trim((string) get_bloginfo('name'));

        if ('' === $name && is_array($form)) {
            $name = trim((string) ($form['title'] ?? ''));
        }

        return $name;
    }
}

ProudGFSettings::instance();
