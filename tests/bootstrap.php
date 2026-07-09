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
