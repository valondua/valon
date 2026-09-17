<?php
/**
 * Plugin Name: Valon Cache Maintenance
 * Description: Refresh public page caches after application deployments.
 */
defined('ABSPATH') || exit;

function valon_health_purge_page_cache() {
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }
}

add_action('wppusher_theme_was_updated', 'valon_health_purge_page_cache');
add_action('wppusher_plugin_was_updated', 'valon_health_purge_page_cache');
add_action('upgrader_process_complete', 'valon_health_purge_page_cache');
add_action('switch_theme', 'valon_health_purge_page_cache');
