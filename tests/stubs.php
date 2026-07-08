<?php

/**
 * Minimal WordPress function stubs for wp-proud-admin testing.
 *
 * Covers calls made at file-include time (before Brain\Monkey per-test mocking
 * takes over). Uses if(!function_exists) guards so Patchwork can still patch
 * these definitions on a per-test basis.
 */

namespace {
    if (!function_exists('add_action')) {
        function add_action() { return true; }
    }
    if (!function_exists('add_filter')) {
        function add_filter() { return true; }
    }
    if (!function_exists('wp_next_scheduled')) {
        function wp_next_scheduled() { return false; }
    }
    if (!function_exists('wp_schedule_event')) {
        function wp_schedule_event() { return true; }
    }
    if (!function_exists('get_option')) {
        function get_option($option, $default = false) { return $default; }
    }
    if (!function_exists('update_option')) {
        function update_option() { return true; }
    }
    if (!function_exists('wp_timezone')) {
        function wp_timezone() { return new DateTimeZone('UTC'); }
    }
    if (!function_exists('rocket_clean_domain')) {
        function rocket_clean_domain() {}
    }

    // Stub for ProudSettingsPage so proud-alert-expiration.php can call
    // ProudSettingsPage::clear_cache() without loading the full class.
    if (!class_exists('ProudSettingsPage')) {
        class ProudSettingsPage {
            public static function clear_cache() {}
        }
    }
}
