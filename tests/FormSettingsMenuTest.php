<?php

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ProudGFSettings::registerAdminPages() and friends in
 * settings/forms.php (#2939).
 *
 * Background: Form Settings and the general ProudCity Settings page both
 * registered their submenu on admin_menu at the default priority 10, and
 * wp-proud-admin.php requires settings/forms.php before settings/settings.php.
 * At equal priority, callback order is registration order, so
 * ProudGFSettings::registerAdminPages() ran before ProudGeneralSettingsPage's
 * add_menu_page('proudsettings', ...) existed. Two symptoms followed:
 *
 *   1. add_submenu_page()'s "insert a self-link for the parent" loop found no
 *      'proudsettings' entry in $menu yet, so the Settings self-link was never
 *      created and Form Settings became $submenu['proudsettings'][0] -- the
 *      top-level Settings link in menu-header.php points at the first submenu
 *      item, so Settings landed on Form Settings.
 *   2. get_plugin_page_hookname() read $admin_page_hooks['proudsettings'],
 *      which add_menu_page() had not set yet, so Form Settings registered as
 *      admin_page_form-settings instead of settings_page_form-settings --
 *      a mismatch that made user_can_access_admin_page() 403 the page at
 *      request time even for an administrator.
 *
 * The fix ships two changes together (Curtis, 2026-09-22):
 *
 *   - registerAdminPages() moves to admin_menu priority 11, so it always runs
 *     after ProudGeneralSettingsPage has created 'proudsettings'.
 *   - The submenu capability, the renderSettingsPage() guard, and the
 *     options.php save path all move from manage_options to edit_proud_options
 *     to match every sibling ProudCity settings page. A new
 *     optionPageCapability() filter on option_page_capability_proud_gf_settings
 *     covers the options.php save path, which register_setting() otherwise
 *     gates on manage_options regardless of the submenu capability.
 *
 * IMPORTANT: unit tests cannot assert core's actual $submenu['proudsettings']
 * ordering -- that ordering is produced inside WordPress core's
 * add_submenu_page(), which is stubbed out entirely here. The priority
 * assertions below (case 1 in particular) are a proxy for the real fix; the
 * ordering and hookname were confirmed separately with a WP-CLI script against
 * the loaded Newton County database, for both an administrator and an editor.
 * See the issue notes for that script and its output.
 *
 * Harness note: hook-registration assertions here use Functions\when('add_action')
 * / Functions\when('add_filter') to capture the raw call arguments, not Brain
 * Monkey's Actions\expectAdded() / Filters\expectAdded() / has_action() helpers.
 * tests/stubs.php defines its own add_action()/add_filter() (guarded by
 * function_exists) at bootstrap time, before Brain Monkey's real hook-tracking
 * versions in inc/wp-hook-functions.php are ever required -- that file is only
 * pulled in lazily by Monkey\setUp(), which first runs inside a test's own
 * setUp(), by which point the stub definitions have already won the
 * function_exists race. So Brain Monkey's own add_action() (the one that
 * populates the storage has_action()/expectAdded() read from) never becomes
 * the active definition; only Patchwork-based interception (Functions\when(),
 * Functions\expect()) reaches these calls.
 */
class FormSettingsMenuTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\stubTranslationFunctions();

        // Approximations of real behaviour, not identity stubs: with identity
        // stubs sanitize_text_field(wp_unslash($x)) === $x always, so a test
        // built on these stubs cannot distinguish the slashed-NUL desync
        // (#2939) from a clean value -- it would pass against the vulnerable
        // code just as happily as the fixed code.
        Functions\when('wp_unslash')->alias(function ($value) {
            if (is_array($value)) {
                return array_map(fn($v) => is_string($v) ? stripslashes($v) : $v, $value);
            }
            return is_string($value) ? stripslashes($value) : $value;
        });
        Functions\when('sanitize_text_field')->alias(function ($value) {
            if (! is_string($value)) {
                return '';
            }
            $value = trim($value);
            return preg_replace('/[\r\n\t ]+/', ' ', $value);
        });

        $_REQUEST = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_REQUEST = [];
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD']);
        Monkey\tearDown();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // 1. admin_menu registration order
    // -----------------------------------------------------------------------

    /**
     * Core regression guard. Fails today because registerAdminPages() is
     * added at the default priority 10, not 11.
     */
    public function testRegistersAdminMenuAtPriorityEleven(): void
    {
        Functions\when('is_admin')->justReturn(true);

        $actionCalls = [];
        Functions\when('add_action')->alias(function (...$args) use (&$actionCalls) {
            $actionCalls[] = $args;
            return true;
        });

        (new ProudGFSettings())->init();

        $adminMenuCalls = array_values(array_filter($actionCalls, fn($call) => $call[0] === 'admin_menu'));

        $this->assertCount(1, $adminMenuCalls, 'admin_menu must be registered exactly once.');
        $this->assertSame(
            11,
            $adminMenuCalls[0][2] ?? null,
            'registerAdminPages() must be hooked at priority 11, after the proudsettings parent menu exists.'
        );
    }

    /**
     * Guards against the 11 being applied to the wrong add_action(). Must
     * pass both before and after the fix.
     */
    public function testAdminInitPriorityUnchanged(): void
    {
        Functions\when('is_admin')->justReturn(true);

        $actionCalls = [];
        Functions\when('add_action')->alias(function (...$args) use (&$actionCalls) {
            $actionCalls[] = $args;
            return true;
        });

        (new ProudGFSettings())->init();

        $adminInitCalls = array_values(array_filter($actionCalls, fn($call) => $call[0] === 'admin_init'));

        $this->assertCount(1, $adminInitCalls);
        $this->assertArrayNotHasKey(
            2,
            $adminInitCalls[0],
            'admin_init must not carry an explicit priority -- the 11 belongs only on admin_menu.'
        );
    }

    /**
     * Protects the #2937 arrangement where this file loads on every request.
     * Must pass both before and after the fix.
     */
    public function testFrontEndRequestRegistersNoAdminMenu(): void
    {
        Functions\when('is_admin')->justReturn(false);

        $actionCalls = [];
        Functions\when('add_action')->alias(function (...$args) use (&$actionCalls) {
            $actionCalls[] = $args;
            return true;
        });
        $filterCalls = [];
        Functions\when('add_filter')->alias(function (...$args) use (&$filterCalls) {
            $filterCalls[] = $args;
            return true;
        });

        (new ProudGFSettings())->init();

        $this->assertEmpty($actionCalls, 'A front-end request must not register admin_menu or any other admin action.');

        $notificationCalls = array_values(array_filter($filterCalls, fn($call) => $call[0] === 'gform_notification'));
        $this->assertCount(1, $notificationCalls, 'The #2937 notification filter must still register on the front end.');
    }

    /**
     * Dead-code removal guard: hookGravityformsDefaults() never ran in
     * practice (init() only ever runs on the init hook, by which point
     * plugins_loaded has already fired) and targeted a filter,
     * gform_form_settings_defaults, that does not exist anywhere in
     * Gravity Forms. init() must not register plugins_loaded at all.
     */
    public function testInitDoesNotRegisterPluginsLoadedInAdmin(): void
    {
        Functions\when('is_admin')->justReturn(true);

        $actionCalls = [];
        Functions\when('add_action')->alias(function (...$args) use (&$actionCalls) {
            $actionCalls[] = $args;
            return true;
        });

        (new ProudGFSettings())->init();

        $pluginsLoadedCalls = array_values(array_filter($actionCalls, fn($call) => $call[0] === 'plugins_loaded'));

        $this->assertEmpty($pluginsLoadedCalls, 'plugins_loaded must not be registered by init().');
    }

    /**
     * Regression guard for the plugins_loaded/hookGravityformsDefaults
     * removal: everything else init() registers in admin context must be
     * untouched.
     */
    public function testInitStillRegistersAllRemainingAdminHooks(): void
    {
        Functions\when('is_admin')->justReturn(true);

        $actionCalls = [];
        Functions\when('add_action')->alias(function (...$args) use (&$actionCalls) {
            $actionCalls[] = $args;
            return true;
        });
        $filterCalls = [];
        Functions\when('add_filter')->alias(function (...$args) use (&$filterCalls) {
            $filterCalls[] = $args;
            return true;
        });

        (new ProudGFSettings())->init();

        $adminMenuCalls = array_values(array_filter($actionCalls, fn($call) => $call[0] === 'admin_menu'));
        $this->assertCount(1, $adminMenuCalls, 'admin_menu must still be registered exactly once.');
        $this->assertSame(11, $adminMenuCalls[0][2] ?? null, 'registerAdminPages() must still be hooked at priority 11.');

        $adminInitCalls = array_values(array_filter($actionCalls, fn($call) => $call[0] === 'admin_init'));
        $this->assertCount(1, $adminInitCalls, 'admin_init must still be registered exactly once.');

        $saveFormCalls = array_values(array_filter($actionCalls, fn($call) => $call[0] === 'gform_after_save_form'));
        $this->assertCount(1, $saveFormCalls, 'gform_after_save_form must still be registered exactly once.');
        $this->assertSame(10, $saveFormCalls[0][2] ?? null, 'gform_after_save_form must still be registered at priority 10.');
        $this->assertSame(2, $saveFormCalls[0][3] ?? null, 'gform_after_save_form must still be registered with 2 accepted args.');

        $capabilityFilterCalls = array_values(array_filter($filterCalls, fn($call) => $call[0] === 'option_page_capability_proud_gf_settings'));
        $this->assertCount(1, $capabilityFilterCalls, 'The option_page_capability_proud_gf_settings filter must still be registered.');
    }

    /**
     * Guards against the dead hookGravityformsDefaults() method being
     * reintroduced.
     */
    public function testHookGravityformsDefaultsMethodNoLongerExists(): void
    {
        $this->assertFalse(
            method_exists(ProudGFSettings::class, 'hookGravityformsDefaults'),
            'hookGravityformsDefaults() was dead code -- it must not be reintroduced.'
        );
    }

    // -----------------------------------------------------------------------
    // 2. Submenu capability
    // -----------------------------------------------------------------------

    /**
     * Fails today: add_submenu_page() is still called with 'manage_options'.
     */
    public function testSubmenuRegistersUnderProudsettingsWithEditProudOptions(): void
    {
        $calls = [];
        Functions\when('add_submenu_page')->alias(function (...$args) use (&$calls) {
            $calls[] = $args;
            return false;
        });

        (new ProudGFSettings())->registerAdminPages();

        $this->assertCount(1, $calls, 'add_submenu_page() must be called exactly once.');
        [$parent, , , $capability, $slug, $callback] = $calls[0];

        $this->assertSame('proudsettings', $parent);
        $this->assertSame('form-settings', $slug);
        $this->assertSame('edit_proud_options', $capability);
        $this->assertNotSame('manage_options', $capability);
        $this->assertIsArray($callback);
    }

    /**
     * The bug this whole issue is made of is these two capabilities drifting
     * apart. Passes both before and after the fix -- it only checks internal
     * consistency, not the specific value.
     */
    public function testSubmenuCapabilityMatchesRenderGuard(): void
    {
        $submenuCapability = null;
        Functions\when('add_submenu_page')->alias(function ($parent, $pageTitle, $menuTitle, $capability) use (&$submenuCapability) {
            $submenuCapability = $capability;
            return false;
        });
        (new ProudGFSettings())->registerAdminPages();

        $renderCapability = null;
        Functions\when('current_user_can')->alias(function ($capability) use (&$renderCapability) {
            $renderCapability = $capability;
            return true;
        });
        Functions\when('settings_fields')->justReturn(null);
        Functions\when('do_settings_sections')->justReturn(null);
        Functions\when('submit_button')->justReturn(null);

        ob_start();
        (new ProudGFSettings())->renderSettingsPage();
        ob_end_clean();

        $this->assertNotNull($submenuCapability);
        $this->assertSame($submenuCapability, $renderCapability);
    }

    /**
     * Fails today: the guard checks 'manage_options', so current_user_can()
     * never sees 'edit_proud_options'.
     */
    public function testRenderSettingsPageDiesWithoutCapability(): void
    {
        $seenCapability = null;
        Functions\when('current_user_can')->alias(function ($capability) use (&$seenCapability) {
            $seenCapability = $capability;
            return false;
        });
        Functions\when('wp_die')->alias(function () {
            throw new \RuntimeException('wp_die called');
        });

        try {
            (new ProudGFSettings())->renderSettingsPage();
            $this->fail('Expected wp_die() to be called.');
        } catch (\RuntimeException $e) {
            $this->assertSame('wp_die called', $e->getMessage());
        }

        $this->assertSame('edit_proud_options', $seenCapability);
    }

    public function testRenderSettingsPageRendersFormWhenCapable(): void
    {
        Functions\when('current_user_can')->justReturn(true);

        $settingsFieldsGroup = null;
        Functions\when('settings_fields')->alias(function ($group) use (&$settingsFieldsGroup) {
            $settingsFieldsGroup = $group;
        });
        $sectionsPage = null;
        Functions\when('do_settings_sections')->alias(function ($page) use (&$sectionsPage) {
            $sectionsPage = $page;
        });
        Functions\when('submit_button')->justReturn(null);

        ob_start();
        (new ProudGFSettings())->renderSettingsPage();
        $output = ob_get_clean();

        $this->assertStringContainsString('action="options.php"', $output);
        $this->assertSame('proud_gf_settings', $settingsFieldsGroup);
        $this->assertSame('form-settings', $sectionsPage);
    }

    // -----------------------------------------------------------------------
    // 3. options.php save path
    // -----------------------------------------------------------------------

    /**
     * Fails today: nothing hooks option_page_capability_proud_gf_settings, so
     * options.php falls back to its own manage_options default and an editor
     * gets a 403 on save even though the page renders.
     */
    public function testRegistersOptionPageCapabilityFilterForItsOwnGroup(): void
    {
        Functions\when('is_admin')->justReturn(true);

        $filterCalls = [];
        Functions\when('add_filter')->alias(function (...$args) use (&$filterCalls) {
            $filterCalls[] = $args;
            return true;
        });

        (new ProudGFSettings())->init();

        // The literal hook name is load-bearing -- core interpolates $option_page
        // into "option_page_capability_{$option_page}", so assert the string,
        // not the constant concatenation.
        $matching = array_values(array_filter($filterCalls, fn($call) => $call[0] === 'option_page_capability_proud_gf_settings'));
        $this->assertCount(1, $matching);
    }

    /**
     * option_page_capability_* gates the whole of wp-admin/options.php, not
     * only the 'update' branch -- a bare GET with ?option_page=proud_gf_settings
     * and no action falls through to the undocumented "All Settings" screen,
     * which dumps every row of wp_options. Only a genuine, nonce-verified
     * save POST may relax the capability.
     */
    public function testOptionPageCapabilityReturnsEditProudOptionsOnValidPostUpdate(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['action'] = 'update';
        $_POST['_wpnonce'] = 'valid-nonce';
        Functions\when('wp_verify_nonce')->justReturn(true);

        $instance = new ProudGFSettings();

        $this->assertSame('edit_proud_options', $instance->optionPageCapability('manage_options'));
        $this->assertSame(
            'edit_proud_options',
            $instance->optionPageCapability('some_bogus_capability'),
            'On a verified save request the callback must return a fixed capability, never pass through what it was given.'
        );
    }

    /**
     * Fails today: the method returns self::CAPABILITY unconditionally, so a
     * POST with no action at all (or a non-'update' action) would still be
     * granted edit_proud_options instead of the incoming capability.
     */
    #[DataProvider('nonUpdateActions')]
    public function testOptionPageCapabilityReturnsIncomingCapabilityForNonUpdatePostAction($action): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        if (null !== $action) {
            $_POST['action'] = $action;
        }
        $_POST['_wpnonce'] = 'valid-nonce';
        Functions\when('wp_verify_nonce')->justReturn(true);

        $instance = new ProudGFSettings();

        $this->assertSame('manage_options', $instance->optionPageCapability('manage_options'));
    }

    public static function nonUpdateActions(): array
    {
        return [
            'action absent' => [null],
            'empty string' => [''],
            'unrelated action' => ['foo'],
        ];
    }

    /**
     * Fails today: there is no nonce check at all, so a POST with
     * action=update but no _wpnonce would already be granted the capability.
     */
    public function testOptionPageCapabilityReturnsIncomingCapabilityWhenNonceMissing(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['action'] = 'update';
        unset($_POST['_wpnonce']);

        $instance = new ProudGFSettings();

        $this->assertSame('manage_options', $instance->optionPageCapability('manage_options'));
    }

    /**
     * Fails today: there is no nonce check at all, so an invalid nonce would
     * not stop the capability from being granted.
     */
    public function testOptionPageCapabilityReturnsIncomingCapabilityWhenNonceInvalid(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['action'] = 'update';
        $_POST['_wpnonce'] = 'bad-nonce';
        Functions\when('wp_verify_nonce')->justReturn(false);

        $instance = new ProudGFSettings();

        $this->assertSame('manage_options', $instance->optionPageCapability('manage_options'));
    }

    /**
     * Regression test for the reported bypass: a bare authenticated GET with
     * ?action=update (and even a nonce that would verify) must never be
     * granted edit_proud_options. Only a POST that core itself would treat
     * as the save request may relax the capability.
     */
    public function testOptionPageCapabilityIgnoresGetRequestsEvenWithValidNonce(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_REQUEST['action'] = 'update';
        $_POST['_wpnonce'] = 'valid-nonce';
        Functions\when('wp_verify_nonce')->justReturn(true);

        $instance = new ProudGFSettings();

        $this->assertSame(
            'manage_options',
            $instance->optionPageCapability('manage_options'),
            'A GET request must never be granted edit_proud_options, regardless of $_REQUEST[\'action\'] or nonce validity.'
        );
    }

    /**
     * Regression test for the #2939 desync itself: wp_magic_quotes() encodes
     * a raw NUL byte as the two literal characters "\" and "0" via
     * addslashes(), so a %00 payload arrives here as the 8-character string
     * "update\0" (backslash, zero). Core's own $action check
     * (sanitize_text_field() with no wp_unslash() first) sees that slashed
     * string and is false. This callback must never see it as 'update'
     * either -- there is no wp_unslash()/sanitize_text_field() round-trip
     * left to normalize it back.
     */
    public function testOptionPageCapabilityRejectsSlashedNulDesyncOnGet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_REQUEST['action'] = "update\\0";
        Functions\when('wp_verify_nonce')->justReturn(true);

        $instance = new ProudGFSettings();

        $this->assertSame('manage_options', $instance->optionPageCapability('manage_options'));
    }

    /**
     * Same desync payload, but delivered as the POST action instead of
     * $_REQUEST, with an otherwise-valid nonce. 'update' !== "update\0", so
     * this must also fall through to the incoming capability.
     */
    public function testOptionPageCapabilityRejectsSlashedNulDesyncOnPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['action'] = "update\\0";
        $_POST['_wpnonce'] = 'valid-nonce';
        Functions\when('wp_verify_nonce')->justReturn(true);

        $instance = new ProudGFSettings();

        $this->assertSame(
            'manage_options',
            $instance->optionPageCapability('manage_options'),
            "'update' !== \"update\\0\" -- the trailing slashed NUL must not be normalised away."
        );
    }

    /**
     * The nonce action must be exactly self::SETTINGS_GROUP . '-options', the
     * same string settings_fields() emits via wp_nonce_field() and
     * check_admin_referer() verifies at wp-admin/options.php:246. Asserted
     * both via the constant and as a literal, since core interpolates it.
     */
    public function testOptionPageCapabilityVerifiesNonceAgainstSettingsGroupAction(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['action'] = 'update';
        $_POST['_wpnonce'] = 'some-nonce-value';

        $seenNonce = null;
        $seenAction = null;
        Functions\when('wp_verify_nonce')->alias(function ($nonce, $action) use (&$seenNonce, &$seenAction) {
            $seenNonce = $nonce;
            $seenAction = $action;
            return true;
        });

        $instance = new ProudGFSettings();
        $instance->optionPageCapability('manage_options');

        $this->assertSame('some-nonce-value', $seenNonce);
        $this->assertSame(ProudGFSettings::SETTINGS_GROUP . '-options', $seenAction);
        $this->assertSame('proud_gf_settings-options', $seenAction);
    }

    // -----------------------------------------------------------------------
    // 4. Existing allowlisting -- must survive the capability change untouched
    // -----------------------------------------------------------------------

    #[DataProvider('labelPlacementValues')]
    public function testSanitizeLabelPlacementRejectsValuesOutsideAllowlist($input, string $expected): void
    {
        $instance = new ProudGFSettings();

        $this->assertSame($expected, $instance->sanitize_label_placement($input));
    }

    public static function labelPlacementValues(): array
    {
        return [
            'top_label passes through'    => ['top_label', 'top_label'],
            'left_label passes through'   => ['left_label', 'left_label'],
            'right_label passes through'  => ['right_label', 'right_label'],
            'hidden_label passes through' => ['hidden_label', 'hidden_label'],
            'empty string rejected'       => ['', 'top_label'],
            'bogus value rejected'        => ['bogus', 'top_label'],
            'script tag rejected'         => ['<script>', 'top_label'],
            'array rejected'              => [['top_label'], 'top_label'],
            'null rejected'               => [null, 'top_label'],
        ];
    }

    public function testForceDefaultLabelPlacementIgnoresExistingForms(): void
    {
        Functions\when('get_option')->justReturn('right_label');
        GFAPI::reset();

        $form = ['id' => 1, 'labelPlacement' => 'left_label'];
        (new ProudGFSettings())->forceDefaultLabelPlacement($form, false);

        $this->assertNull(GFAPI::$lastUpdatedForm, 'GFAPI::update_form() must not be called for an existing form.');
    }

    public function testForceDefaultLabelPlacementWritesAllowlistedValueEvenWithCorruptedOption(): void
    {
        Functions\when('get_option')->justReturn('<script>alert(1)</script>');
        GFAPI::reset();

        $form = ['id' => 2];
        (new ProudGFSettings())->forceDefaultLabelPlacement($form, true);

        $this->assertNotNull(GFAPI::$lastUpdatedForm);
        $this->assertSame('top_label', GFAPI::$lastUpdatedForm['labelPlacement']);
    }
}

// Local stub, kept out of tests/stubs.php: settings/forms.php calls
// GFAPI::update_form() unqualified from the global namespace.
if (! class_exists('GFAPI')) {
    class GFAPI
    {
        public static $lastUpdatedForm;

        public static function update_form($form)
        {
            self::$lastUpdatedForm = $form;
            return $form['id'] ?? 0;
        }

        public static function reset(): void
        {
            self::$lastUpdatedForm = null;
        }
    }
}
