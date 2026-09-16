<?php

/**
 * PHPUnit bootstrap for wp-proud-admin.
 *
 * Load order:
 *   1. Composer autoload — loads Patchwork before any plugin files.
 *   2. stubs.php — minimal WP function stubs for load-time calls.
 *   3. Plugin files under test.
 *
 * Run from the plugin root:
 *   composer install
 *   vendor/bin/phpunit
 */

require_once __DIR__ . '/../vendor/antecedent/patchwork/Patchwork.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/stubs.php';

// proud-alert-expiration.php defines Proud_Alert_Expiration and registers
// hooks on init. add_action is stubbed so no side effects.
require_once __DIR__ . '/../lib/proud-alert-expiration.php';

// meta-box.class.php defines ProudMetaBox and ProudTermMetaBox. Both register
// hooks in their constructors; add_action is stubbed so there are no side
// effects. MetaBoxSaveTest covers the save path (#2933 security review).
require_once __DIR__ . '/../lib/meta-box.class.php';

// The real Proud\Core\FormHelper from wp-proud-core. ProudMetaBox delegates
// field naming and $_POST extraction to it, so getFormValues() is part of what
// MetaBoxSaveTest exercises -- a stub would decide the test's outcome. The file
// guards its Gravity Forms include behind class_exists('GFForms'), false here,
// so it loads standalone.
$formHelper = __DIR__ . '/../../wp-proud-core/modules/proud-form/proud-form.php';
if (!is_readable($formHelper)) {
    fwrite(STDERR, "Cannot read {$formHelper}.\nThese tests need wp-proud-core alongside this plugin.\n");
    exit(1);
}
require_once $formHelper;
